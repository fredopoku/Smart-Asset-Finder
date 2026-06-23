<?php
require_once('../../config.php');
if(!isset($_SESSION['admin_userdata'])) { header('Location: '.base_url.'admin/'); exit; }

$feedback = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])){
    $subject  = trim($_POST['subject'] ?? '');
    $html     = trim($_POST['body_html'] ?? '');
    $wa_msg   = trim($_POST['wa_message'] ?? '');
    $channels = $_POST['channels'] ?? [];
    $segment  = $_POST['segment'] ?? 'all';

    if(!$subject || !$html){
        $feedback = '<div class="alert alert-danger">Subject and email body are required.</div>';
    } else {
        // Build recipient query
        $where = "status=1 AND email_verified=1";
        if($segment === 'verified') $where .= " AND email_verified=1";

        $users = $conn->query("SELECT firstname, lastname, email, phone FROM registered_users WHERE {$where} ORDER BY id ASC");
        $sent_email = 0; $sent_wa = 0; $errors = 0;

        while($u = $users->fetch_assoc()){
            $name = $u['firstname'].' '.$u['lastname'];
            if(in_array('email', $channels)){
                $ok = Mailer::broadcast($u['email'], $name, $subject, $html);
                $ok ? $sent_email++ : $errors++;
            }
            if(in_array('whatsapp', $channels) && !empty($wa_msg) && !empty($u['phone'])){
                Whatsapp::broadcast($u['phone'], $u['firstname'], $wa_msg);
                $sent_wa++;
            }
        }
        $feedback = "<div class='alert alert-success'><strong>Broadcast sent!</strong> Email: {$sent_email} sent. WhatsApp: {$sent_wa} sent." . ($errors ? " <span class='text-danger'>{$errors} email errors — check SMTP config.</span>" : '') . "</div>";
    }
}

// Preview count
$total_recipients = (int)$conn->query("SELECT COUNT(*) c FROM registered_users WHERE status=1 AND email_verified=1")->fetch_assoc()['c'];
$total_all        = (int)$conn->query("SELECT COUNT(*) c FROM registered_users WHERE status=1")->fetch_assoc()['c'];
?>

<div class="col-12">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0">Broadcast</h4>
      <p class="text-muted mb-0" style="font-size:.85rem">Send email or WhatsApp to your users</p>
    </div>
  </div>

  <?= $feedback ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
          <form method="POST">
            <input type="hidden" name="send_broadcast" value="1">

            <!-- Channels -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Send via</label>
              <div class="d-flex gap-3 flex-wrap">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="ch-email" checked>
                  <label class="form-check-label" for="ch-email"><i class="bi bi-envelope-fill text-primary me-1"></i>Email</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="ch-wa">
                  <label class="form-check-label" for="ch-wa"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp</label>
                </div>
              </div>
            </div>

            <!-- Segment -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Recipients</label>
              <select name="segment" class="form-select" style="max-width:320px">
                <option value="all">All verified users (<?= $total_recipients ?>)</option>
                <option value="verified">Same — verified accounts only</option>
              </select>
            </div>

            <!-- Email Subject -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Email Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" required placeholder="e.g. Important update from Smart Asset Finder">
              <div class="form-text">Keep it short and specific — under 60 characters.</div>
            </div>

            <!-- Email Body -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Email Body (HTML) <span class="text-danger">*</span></label>
              <textarea name="body_html" class="form-control font-monospace" rows="12" required
                placeholder="<p>Hi {{first}},</p>&#10;<p>Your message here...</p>&#10;&#10;Use {{name}} for full name, {{first}} for first name."
                style="font-size:.82rem;line-height:1.6"></textarea>
              <div class="form-text">Use <code>{{first}}</code> for first name, <code>{{name}}</code> for full name. Full HTML is supported.</div>
            </div>

            <!-- WhatsApp Message -->
            <div class="mb-4" id="wa-section" style="display:none">
              <label class="form-label fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp Message</label>
              <textarea name="wa_message" class="form-control" rows="5"
                placeholder="Hi {{name}}, &#10;&#10;Your message here...&#10;&#10;— Smart Asset Finder"
                style="font-size:.88rem"></textarea>
              <div class="form-text">Plain text only. Use <code>{{name}}</code> for personalisation. Max ~1000 chars recommended.</div>
            </div>

            <div class="d-flex gap-3 align-items-center">
              <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"
                onclick="return confirm('Send this broadcast to <?= $total_recipients ?> users?')">
                <i class="bi bi-send-fill me-2"></i>Send Broadcast
              </button>
              <span class="text-muted" style="font-size:.8rem">Will send to <?= $total_recipients ?> verified users</span>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-people-fill text-primary me-2"></i>Audience</h6>
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted" style="font-size:.85rem">Total registered</span>
            <strong><?= $total_all ?></strong>
          </div>
          <div class="d-flex justify-content-between py-2">
            <span class="text-muted" style="font-size:.85rem">Verified (can receive email)</span>
            <strong class="text-success"><?= $total_recipients ?></strong>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4 mb-3" style="background:#fffbeb">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-2"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp setup</h6>
          <p class="text-muted mb-2" style="font-size:.82rem">Add to <code>.env</code> to enable:</p>
          <code style="font-size:.75rem;display:block;background:#f1f5f9;padding:8px;border-radius:6px;line-height:1.8">ULTRAMSG_INSTANCE=instance12345<br>ULTRAMSG_TOKEN=your_token</code>
          <p class="text-muted mt-2 mb-0" style="font-size:.78rem">Get credentials at <strong>ultramsg.com</strong></p>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-1"></i>Broadcast tips</h6>
          <ul class="text-muted mb-0" style="font-size:.82rem;padding-left:1.1rem;line-height:2">
            <li>Personalise with <code>{{first}}</code></li>
            <li>One clear call-to-action per email</li>
            <li>Test by sending to yourself first</li>
            <li>Keep WhatsApp under 300 words</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('ch-wa').addEventListener('change', function(){
  document.getElementById('wa-section').style.display = this.checked ? '' : 'none';
});
</script>
