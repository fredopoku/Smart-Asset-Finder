<?php
/**
 * AiMatcher — auto-match lost vs found items, score claim answers.
 * Uses Claude Haiku when CLAUDE_API_KEY is set; falls back to PHP
 * text-similarity functions so the platform works without any API key.
 */
class AiMatcher {

    private $conn;
    private string $api_key;
    private string $model = 'claude-haiku-4-5-20251001';

    public function __construct($conn) {
        $this->conn    = $conn;
        $this->api_key = saf_env('CLAUDE_API_KEY', '');
    }

    // ── Public: score claim answers against stored security Q&A ─────────────

    /**
     * Returns 0–100. 100 = perfect match. <45 = likely fraudulent.
     * If the item has no security questions, returns 100 (nothing to check).
     */
    public function scoreClaimAnswers(int $item_id, array $submitted_answers): float {
        $stmt = $this->conn->prepare(
            "SELECT question, answer_normalized FROM item_security_qa
             WHERE item_id=? ORDER BY sort_order"
        );
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $qs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if(empty($qs)) return 100.0;

        $total = 0.0;
        foreach($qs as $i => $q) {
            $submitted = isset($submitted_answers[$i])
                ? $this->normalize($submitted_answers[$i]) : '';

            if($submitted === '') {
                // No answer submitted for this question
                $total += 0;
                continue;
            }

            $score = $this->api_key
                ? $this->scoreWithClaude($q['question'], $q['answer_normalized'], $submitted)
                : $this->scoreLocally($q['answer_normalized'], $submitted);

            $total += $score;
        }

        return count($qs) > 0 ? round($total / count($qs), 1) : 0.0;
    }

    // ── Public: find matching lost items for a newly reported found item ─────

    /**
     * Returns up to 3 [id, score, user_id, title] arrays sorted by score desc.
     * Notifies matched owners via the notifications table.
     */
    public function findAndNotifyMatches(int $found_item_id): array {
        $stmt = $this->conn->prepare(
            "SELECT title, description, category_id FROM item_list WHERE id=? LIMIT 1"
        );
        $stmt->bind_param('i', $found_item_id);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$found) return [];

        // Search lost items in same category reported in last 90 days
        $stmt = $this->conn->prepare(
            "SELECT id, title, description, user_id
             FROM item_list
             WHERE item_type=0 AND status=1 AND category_id=? AND id!=?
               AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        $stmt->bind_param('ii', $found['category_id'], $found_item_id);
        $stmt->execute();
        $lost_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $found_text = $found['title'].' '.$found['description'];
        $matches = [];

        foreach($lost_items as $lost) {
            $lost_text = $lost['title'].' '.$lost['description'];
            $score = $this->api_key
                ? $this->matchWithClaude($found_text, $lost_text)
                : $this->scoreLocally($found_text, $lost_text);

            if($score >= 38) {
                $matches[] = [
                    'id'      => (int)$lost['id'],
                    'score'   => $score,
                    'user_id' => (int)$lost['user_id'],
                    'title'   => $lost['title'],
                ];
            }
        }

        usort($matches, fn($a,$b) => $b['score'] <=> $a['score']);
        $top = array_slice($matches, 0, 3);

        // Store score on the found item (best match)
        if(!empty($top)) {
            $best = $top[0]['score'];
            $u = $this->conn->prepare(
                "UPDATE item_list SET ai_match_score=? WHERE id=?"
            );
            $u->bind_param('di', $best, $found_item_id);
            $u->execute();
            $u->close();
        }

        // Notify matched owners — in-app + email + WhatsApp
        $app_url = defined('base_url') ? base_url : (saf_env('APP_URL','http://localhost/Smart-Asset-Finder/'));
        foreach($top as $m) {
            if(!$m['user_id']) continue;

            $item_url   = $app_url.'?page=items/view&id='.$found_item_id;
            $browse_url = '?page=items/view&id='.$found_item_id;
            $notif_msg  = "AI match: a found item may match your lost report \"{$m['title']}\". View it now.";

            // In-app notification
            $ins = $this->conn->prepare(
                "INSERT INTO notifications (user_id, message, link, type)
                 VALUES (?, ?, ?, 'success')"
            );
            $ins->bind_param('iss', $m['user_id'], $notif_msg, $browse_url);
            $ins->execute();
            $ins->close();

            // Fetch owner contact details for email + WhatsApp
            $usr = $this->conn->prepare("SELECT firstname, lastname, email, phone FROM registered_users WHERE id=? LIMIT 1");
            $usr->bind_param('i', $m['user_id']);
            $usr->execute();
            $owner = $usr->get_result()->fetch_assoc();
            $usr->close();
            if(!$owner) continue;

            $owner_name = $owner['firstname'].' '.$owner['lastname'];
            Mailer::itemMatched($owner['email'], $owner_name, $m['title'], $found['title'], $item_url);
            if(!empty($owner['phone'])){
                Whatsapp::itemMatched($owner['phone'], $owner['firstname'], $m['title'], $found['title'], $item_url);
            }
        }

        return $top;
    }

    // ── Public: detect suspicious/spam item reports ──────────────────────────

    /**
     * Returns ['ok'=>bool, 'reason'=>string].
     * Flags reports with no description, same-user spam, or AI-detected fakes.
     */
    public function checkItemReport(array $item_data, ?int $user_id): array {
        // Rule-based checks (always run, no API needed)
        $desc = trim($item_data['description'] ?? '');
        $title = trim($item_data['title'] ?? '');

        if(strlen($desc) < 15) {
            return ['ok' => false, 'reason' => 'Description is too short. Please describe the item properly.'];
        }

        if($user_id) {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) as cnt FROM item_list
                 WHERE user_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if((int)$row['cnt'] >= 5) {
                return ['ok' => false, 'reason' => 'Too many reports in a short time. Please wait before submitting again.'];
            }
        }

        if($this->api_key && strlen($desc) > 20) {
            $check = $this->fraudCheckWithClaude($title, $desc);
            if(!$check['ok']) return $check;
        }

        return ['ok' => true, 'reason' => ''];
    }

    // ── Private: Claude API calls ────────────────────────────────────────────

    private function scoreWithClaude(string $question, string $expected, string $submitted): float {
        $prompt = "You are verifying lost-item ownership. Be strict — this prevents theft.
Question asked: \"{$question}\"
Owner's original answer: \"{$expected}\"
Claimant's submitted answer: \"{$submitted}\"

Score 0–100: how well does the submitted answer match the expected answer?
- 90–100: clear match (synonyms, minor typos OK)
- 60–89: partial match, key details present
- 30–59: vague or only partially correct
- 0–29: wrong or no meaningful match

Reply with ONLY a number, nothing else.";

        $resp = $this->callClaude($prompt, 5);
        $n = (float) preg_replace('/[^0-9.]/', '', $resp);
        return max(0.0, min(100.0, $n));
    }

    private function matchWithClaude(string $found_text, string $lost_text): float {
        $prompt = "Compare these two item descriptions to decide if they could be the same physical item.
Found item: \"{$found_text}\"
Lost item: \"{$lost_text}\"

Score 0–100 for likelihood they are the same item.
- 80–100: very likely the same
- 50–79: possibly the same
- 20–49: some similarities
- 0–19: different items

Reply with ONLY a number, nothing else.";

        $resp = $this->callClaude($prompt, 5);
        $n = (float) preg_replace('/[^0-9.]/', '', $resp);
        return max(0.0, min(100.0, $n));
    }

    private function fraudCheckWithClaude(string $title, string $description): array {
        $prompt = "Is this a legitimate lost/found item report or likely spam/fake?
Title: \"{$title}\"
Description: \"{$description}\"

Reply with exactly: OK or FLAG:reason
Examples: OK / FLAG:appears to be an advertisement / FLAG:gibberish text";

        $resp = trim($this->callClaude($prompt, 20));
        if(stripos($resp, 'FLAG:') === 0) {
            $reason = trim(substr($resp, 5));
            return ['ok' => false, 'reason' => 'Report flagged: '.$reason.'. Please provide a genuine description.'];
        }
        return ['ok' => true, 'reason' => ''];
    }

    private function callClaude(string $prompt, int $max_tokens = 10): string {
        if(empty($this->api_key) || $this->api_key === 'your-claude-api-key-here') return '0';

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: '.$this->api_key,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => $this->model,
                'max_tokens' => $max_tokens,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
        ]);
        $resp = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);

        if($err) return '0';
        $data = json_decode($resp, true);
        return $data['content'][0]['text'] ?? '0';
    }

    // ── Private: local fallback (no API needed) ──────────────────────────────

    private function scoreLocally(string $a, string $b): float {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        if($a === '' || $b === '') return 0.0;
        similar_text($a, $b, $pct);

        // Boost score if key words from $a appear in $b
        $words_a = array_filter(explode(' ', $a), fn($w) => strlen($w) > 3);
        $hits = 0;
        foreach($words_a as $w) {
            if(strpos($b, $w) !== false) $hits++;
        }
        $keyword_boost = count($words_a) > 0 ? ($hits / count($words_a)) * 20 : 0;

        return min(100.0, round($pct + $keyword_boost, 1));
    }

    // ── Public: normalize an answer for storage / comparison ─────────────────

    public function normalize(string $s): string {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9 ]/i', ' ', $s);
        return preg_replace('/\s+/', ' ', $s);
    }
}
