<?php if(!isset($_SESSION['pub_userdata'])){ redirect('?page=login'); exit; } ?>
<?php
$uid = (int)$_SESSION['pub_userdata']['id'];

// Points balance
$bal = $conn->prepare("SELECT points FROM registered_users WHERE id=? LIMIT 1");
$bal->bind_param('i', $uid);
$bal->execute();
$balance = (int)($bal->get_result()->fetch_assoc()['points'] ?? 0);
$bal->close();

// Point transaction history (last 20)
$hist = $conn->prepare(
    "SELECT * FROM point_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20"
);
$hist->bind_param('i', $uid);
$hist->execute();
$transactions = $hist->get_result()->fetch_all(MYSQLI_ASSOC);
$hist->close();

// Total earned / spent
$totals = $conn->prepare(
    "SELECT
       SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as earned,
       SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END) as spent
     FROM point_transactions WHERE user_id=?"
);
$totals->bind_param('i', $uid);
$totals->execute();
$t = $totals->get_result()->fetch_assoc();
$totals->close();
$total_earned = (int)($t['earned'] ?? 0);
$total_spent  = (int)($t['spent']  ?? 0);

// All badges with earned status
$all_badges = $conn->prepare(
    "SELECT b.*, ub.earned_at
     FROM badges b
     LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ?
     ORDER BY b.id ASC"
);
$all_badges->bind_param('i', $uid);
$all_badges->execute();
$badges = $all_badges->get_result()->fetch_all(MYSQLI_ASSOC);
$all_badges->close();

$earned_count = count(array_filter($badges, fn($b) => $b['earned_at'] !== null));

// How to earn more (static rules)
$earn_rules = [
    ['icon'=>'bi-person-plus-fill',      'color'=>'#1a56db', 'label'=>'Create account',            'pts'=>10,  'action'=>'register'],
    ['icon'=>'bi-envelope-check-fill',   'color'=>'#10b981', 'label'=>'Verify email address',       'pts'=>10,  'action'=>'email_verified'],
    ['icon'=>'bi-file-earmark-plus-fill','color'=>'#0ea5e9', 'label'=>'Submit an item report',      'pts'=>10,  'action'=>'item_submitted'],
    ['icon'=>'bi-qr-code-scan',          'color'=>'#7c3aed', 'label'=>'Scan & report a QR tag',     'pts'=>5,   'action'=>'qr_scan_log'],
    ['icon'=>'bi-heart-fill',            'color'=>'#ef4444', 'label'=>'Return an item to its owner','pts'=>50,  'action'=>'item_returned'],
    ['icon'=>'bi-patch-check-fill',      'color'=>'#f59e0b', 'label'=>'Verified item return',        'pts'=>100, 'action'=>'item_returned_verified'],
    ['icon'=>'bi-people-fill',           'color'=>'#059669', 'label'=>'Refer a friend who joins',   'pts'=>25,  'action'=>'referral'],
];

$redeem_rules = [
    ['icon'=>'bi-sticky-fill',       'color'=>'#f59e0b', 'label'=>'Free QR sticker 3-pack (delivered)', 'pts'=>100],
    ['icon'=>'bi-calendar-check',    'color'=>'#1a56db', 'label'=>'1 month Standard plan',              'pts'=>200],
    ['icon'=>'bi-tag-fill',          'color'=>'#0ea5e9', 'label'=>'10% off any hardware order',         'pts'=>150],
    ['icon'=>'bi-cash-coin',         'color'=>'#10b981', 'label'=>'Cash — mobile money or PayPal',      'pts'=>500, 'note'=>'500 pts = GHS 10 / USD 1'],
    ['icon'=>'bi-shield-fill-check', 'color'=>'#7c3aed', 'label'=>'Insurance partner discount',         'pts'=>300],
];
?>

<div class="container-xl px-4 py-4">

  <!-- Header -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <h2 class="fw-bold mb-0" style="color:var(--saf-dark)"><i class="bi bi-trophy-fill text-warning me-2"></i>Rewards</h2>
      <p class="text-muted">Earn points by helping the community. Redeem for hardware, discounts, or cash.</p>
    </div>
  </div>

  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm p-4 text-center" style="border-radius:16px;background:linear-gradient(135deg,#1a56db,#0ea5e9)">
        <div class="text-white mb-1" style="font-size:.8rem;text-transform:uppercase;letter-spacing:1px;opacity:.85">Current Balance</div>
        <div class="text-white fw-bold" style="font-size:2.8rem;line-height:1"><?= number_format($balance) ?></div>
        <div class="text-white" style="font-size:.8rem;opacity:.75">points</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm p-4 text-center" style="border-radius:16px">
        <div class="text-muted mb-1" style="font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Total Earned</div>
        <div class="fw-bold text-success" style="font-size:2rem;line-height:1"><?= number_format($total_earned) ?></div>
        <div class="text-muted" style="font-size:.8rem">points all time</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm p-4 text-center" style="border-radius:16px">
        <div class="text-muted mb-1" style="font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Badges Earned</div>
        <div class="fw-bold" style="font-size:2rem;line-height:1;color:var(--saf-primary)"><?= $earned_count ?><span class="text-muted fw-normal" style="font-size:1rem"> / <?= count($badges) ?></span></div>
        <div class="text-muted" style="font-size:.8rem">achievements</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Left column: badges + history -->
    <div class="col-lg-7">

      <!-- Badges -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-patch-check me-1 text-primary"></i>Badges</h5>
          <div class="row g-3">
            <?php foreach($badges as $b):
              $earned = !empty($b['earned_at']);
            ?>
            <div class="col-6 col-md-4">
              <div class="text-center p-3 rounded-3 <?= $earned ? '' : 'opacity-40' ?>"
                style="background:<?= $earned ? '#f8faff' : '#f1f5f9' ?>;border:1px solid <?= $earned ? '#dbeafe' : '#e2e8f0' ?>">
                <div style="width:48px;height:48px;border-radius:50%;background:<?= $earned ? $b['color'] : '#94a3b8' ?>;margin:0 auto 8px;display:flex;align-items:center;justify-content:center">
                  <i class="bi <?= htmlspecialchars($b['icon']) ?> text-white" style="font-size:1.2rem"></i>
                </div>
                <div class="fw-semibold" style="font-size:.78rem;color:<?= $earned ? '#0f172a' : '#94a3b8' ?>"><?= htmlspecialchars($b['name']) ?></div>
                <?php if($earned): ?>
                <div class="text-muted" style="font-size:.68rem;margin-top:2px"><?= date('M j, Y', strtotime($b['earned_at'])) ?></div>
                <?php else: ?>
                <div style="font-size:.68rem;margin-top:2px;color:#94a3b8"><?= htmlspecialchars($b['description']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Transaction history -->
      <div class="card border-0 shadow-sm" style="border-radius:16px">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-1 text-primary"></i>Points History</h5>
          <?php if(empty($transactions)): ?>
          <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size:2rem;opacity:.3"></i>
            <p class="mt-2 mb-0" style="font-size:.85rem">No transactions yet. Start earning by tagging your items!</p>
          </div>
          <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach($transactions as $tx):
              $positive = $tx['points'] > 0;
            ?>
            <div class="list-group-item px-0 d-flex justify-content-between align-items-start border-0 border-bottom py-3">
              <div class="d-flex gap-3 align-items-center">
                <div style="width:36px;height:36px;border-radius:50%;background:<?= $positive ? '#dcfce7' : '#fee2e2' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <i class="bi bi-<?= $positive ? 'plus-circle-fill text-success' : 'dash-circle-fill text-danger' ?>"></i>
                </div>
                <div>
                  <div style="font-size:.85rem;font-weight:600;color:#0f172a"><?= htmlspecialchars($tx['description'] ?: ucwords(str_replace('_',' ',$tx['action']))) ?></div>
                  <div style="font-size:.72rem;color:#94a3b8"><?= date('M j, Y · g:i A', strtotime($tx['created_at'])) ?></div>
                </div>
              </div>
              <span class="fw-bold <?= $positive ? 'text-success' : 'text-danger' ?>" style="font-size:.9rem;flex-shrink:0">
                <?= $positive ? '+' : '' ?><?= number_format($tx['points']) ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Right column: how to earn + redeem -->
    <div class="col-lg-5">

      <!-- How to earn -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>How to Earn Points</h5>
          <div class="d-flex flex-column gap-2">
            <?php foreach($earn_rules as $r): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $r['icon'] ?>" style="color:<?= $r['color'] ?>;font-size:1rem;width:20px;text-align:center"></i>
                <span style="font-size:.83rem"><?= $r['label'] ?></span>
              </div>
              <span class="badge rounded-pill" style="background:#f0fdf4;color:#166534;font-size:.75rem">+<?= $r['pts'] ?> pts</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- How to redeem -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-gift-fill text-danger me-1"></i>Redeem Points</h5>
          <div class="d-flex flex-column gap-2">
            <?php foreach($redeem_rules as $r): ?>
            <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $r['icon'] ?>" style="color:<?= $r['color'] ?>;font-size:1rem;width:20px;text-align:center"></i>
                <div>
                  <div style="font-size:.83rem"><?= $r['label'] ?></div>
                  <?php if(!empty($r['note'])): ?><div style="font-size:.7rem;color:#64748b"><?= $r['note'] ?></div><?php endif; ?>
                </div>
              </div>
              <button class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0 ms-2"
                style="font-size:.72rem;padding:2px 10px"
                onclick="redeemPoints(<?= $r['pts'] ?>, '<?= addslashes($r['label']) ?>', <?= $balance ?>)">
                <?= number_format($r['pts']) ?> pts
              </button>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Referral -->
      <div class="card border-0 shadow-sm" style="border-radius:16px;background:linear-gradient(135deg,#fefce8,#fef9c3)">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-1"><i class="bi bi-share-fill me-1 text-warning"></i>Refer a Friend</h5>
          <p class="text-muted mb-3" style="font-size:.83rem">Share your link. When they register, you both earn 25 points.</p>
          <div class="input-group input-group-sm">
            <input type="text" id="ref-link" class="form-control rounded-start"
              value="<?= base_url ?>?page=register&ref=<?= base64_encode($uid) ?>"
              readonly style="font-size:.78rem;background:#fff">
            <button class="btn btn-warning btn-sm" onclick="copyRef()" style="font-size:.78rem">
              <i class="bi bi-copy me-1"></i>Copy
            </button>
          </div>
          <div id="copy-msg" class="text-success mt-1" style="font-size:.75rem;display:none">Copied to clipboard!</div>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- Redeem modal -->
<div class="modal fade" id="redeem-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-bold">Redeem Points</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="redeem-body"></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-primary rounded-pill w-100" id="redeem-confirm-btn">Confirm Redemption</button>
      </div>
    </div>
  </div>
</div>

<script>
function redeemPoints(cost, label, balance){
  if(balance < cost){
    alert_toast('You need ' + cost + ' points for this reward. You have ' + balance + '.', 'warning');
    return;
  }
  $('#redeem-body').html(
    '<p style="font-size:.85rem">You are redeeming <strong>' + cost + ' points</strong> for:</p>'
  + '<div class="alert alert-primary py-2 mb-0" style="font-size:.83rem">' + label + '</div>'
  + '<p class="mt-2 mb-0 text-muted" style="font-size:.78rem">Our team will contact you at your registered email within 48 hours to fulfil this reward.</p>'
  );
  var m = new bootstrap.Modal(document.getElementById('redeem-modal'));
  m.show();
  $('#redeem-confirm-btn').off('click').on('click', function(){
    var $btn = $(this).prop('disabled', true).text('Processing…');
    $.ajax({
      url: _base_url_ + 'classes/Master.php?f=redeem_points',
      method: 'POST',
      dataType: 'json',
      data: { cost: cost, reward: label },
      success: function(r){
        m.hide();
        if(r.status === 'success'){
          alert_toast(r.msg, 'success');
          setTimeout(function(){ location.reload(); }, 1200);
        } else {
          alert_toast(r.msg || 'Could not redeem. Please try again.', 'error');
          $btn.prop('disabled', false).text('Confirm Redemption');
        }
      },
      error: function(){ alert_toast('An error occurred.', 'error'); $btn.prop('disabled', false).text('Confirm Redemption'); }
    });
  });
}

function copyRef(){
  var el = document.getElementById('ref-link');
  el.select();
  document.execCommand('copy');
  $('#copy-msg').fadeIn(200).delay(2000).fadeOut(400);
}
</script>
