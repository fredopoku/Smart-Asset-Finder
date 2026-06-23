<?php
// Inline email verification — uses $conn already available from config.php via index.php
$token  = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$result = ['status' => 'failed', 'msg'  => 'Invalid or missing verification link.'];

if($token){
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email FROM registered_users WHERE verification_token=? AND email_verified=0 LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$user){
        $result = ['status'=>'already','msg'=>'This link has already been used or your email is already confirmed.'];
    } else {
        $upd = $conn->prepare("UPDATE registered_users SET email_verified=1, verification_token=NULL WHERE id=?");
        $upd->bind_param('i', $user['id']);
        $upd->execute();
        $upd->close();

        // Update session if user is currently logged in
        if(isset($_SESSION['pub_userdata']) && (int)$_SESSION['pub_userdata']['id'] === (int)$user['id']){
            $_SESSION['pub_userdata']['email_verified'] = 1;
        }

        // Send welcome email (non-blocking — silently fails if SMTP not configured)
        if(class_exists('Mailer')){
            Mailer::welcome($user['email'], $user['firstname'].' '.$user['lastname']);
        } else {
            @include_once __DIR__.'/classes/Mailer.php';
            if(class_exists('Mailer')) Mailer::welcome($user['email'], $user['firstname'].' '.$user['lastname']);
        }

        $result = ['status'=>'success','msg'=>'Email verified! Your account is now fully active.'];
    }
}
?>
<div class="container-xl px-4 py-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">

      <?php if($result['status'] === 'success'): ?>
      <div class="text-center">
        <div class="mb-4" style="width:80px;height:80px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center">
          <i class="bi bi-check-lg text-white" style="font-size:2rem"></i>
        </div>
        <h2 class="fw-bold mb-2" style="color:var(--saf-dark)">Email Verified!</h2>
        <p class="text-muted mb-4">Your account is now fully active. You can report items, submit claims, and track everything in one place.</p>
        <a href="<?= base_url ?>?page=my-items" class="btn btn-primary px-5 py-2 rounded-pill fw-semibold">
          <i class="bi bi-collection me-1"></i> Go to My Items
        </a>
        <div class="mt-3">
          <a href="<?= base_url ?>" class="text-muted" style="font-size:.85rem">Back to home</a>
        </div>
      </div>

      <?php elseif($result['status'] === 'already'): ?>
      <div class="text-center">
        <div class="mb-4" style="width:80px;height:80px;background:#f1f5f9;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center">
          <i class="bi bi-patch-check-fill" style="font-size:2rem;color:#1a56db"></i>
        </div>
        <h2 class="fw-bold mb-2" style="color:var(--saf-dark)">Already Verified</h2>
        <p class="text-muted mb-4">This link has already been used or your email is already confirmed. You can sign in normally.</p>
        <a href="<?= base_url ?>?page=login" class="btn btn-primary px-5 py-2 rounded-pill fw-semibold">Sign In</a>
      </div>

      <?php else: ?>
      <div class="text-center">
        <div class="mb-4" style="width:80px;height:80px;background:#fef2f2;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center">
          <i class="bi bi-x-circle-fill" style="font-size:2rem;color:#dc2626"></i>
        </div>
        <h2 class="fw-bold mb-2" style="color:var(--saf-dark)">Link Invalid</h2>
        <p class="text-muted mb-4"><?= htmlspecialchars($result['msg']) ?></p>
        <?php if(isset($_SESSION['pub_userdata']) && !($_SESSION['pub_userdata']['email_verified'] ?? 1)): ?>
          <button class="btn btn-primary px-5 py-2 rounded-pill fw-semibold" id="resend-btn">
            <i class="bi bi-envelope-arrow-up me-1"></i> Resend Verification Email
          </button>
          <div id="resend-msg" class="mt-3"></div>
          <script>
          $(function(){
            $('#resend-btn').on('click', function(){
              var $btn = $(this);
              $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending…');
              $.getJSON(_base_url_+'classes/Login.php?f=resend_verification', function(r){
                $btn.remove();
                var cls = r.status==='success' ? 'success' : 'warning';
                $('#resend-msg').html('<div class="alert alert-'+cls+'">'+r.msg+'</div>');
              });
            });
          });
          </script>
        <?php else: ?>
          <a href="<?= base_url ?>?page=login" class="btn btn-outline-primary px-5 py-2 rounded-pill">Sign In</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
