<?php if(isset($_SESSION['pub_userdata'])){ redirect('?page=my-items'); exit; } ?>
<?php
$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$valid = false;
if($token){
  $chk = $conn->prepare("SELECT email FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1");
  $chk->bind_param('s', $token);
  $chk->execute();
  $valid = $chk->get_result()->num_rows > 0;
  $chk->close();
}
?>
<div class="container-xl px-4 py-4">
  <div class="row justify-content-center">
    <div class="col-lg-4 col-md-6">
      <?php if(!$token || !$valid): ?>
        <div class="text-center py-5">
          <i class="bi bi-x-octagon text-danger" style="font-size:4rem"></i>
          <h3 class="fw-bold mt-3 text-danger">Invalid or Expired Link</h3>
          <p class="text-muted">This password reset link is no longer valid. Links expire after 60 minutes.</p>
          <a href="<?= base_url ?>?page=forgot-password" class="btn btn-primary rounded-pill mt-2">Request a New Link</a>
        </div>
      <?php else: ?>
        <div class="text-center mb-4">
          <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
            style="width:64px;height:64px;background:linear-gradient(135deg,#059669,#10b981)">
            <i class="bi bi-shield-lock-fill text-white fs-3"></i>
          </div>
          <h2 class="fw-bold" style="color:var(--saf-dark)">Set New Password</h2>
          <p class="text-muted">Choose a strong password of at least 8 characters.</p>
        </div>
        <div class="saf-form-card">
          <form id="reset-frm" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <div class="input-group">
                <input type="password" name="password" id="new-pw" class="form-control" required placeholder="Minimum 8 characters" minlength="8">
                <button type="button" class="btn btn-outline-secondary" onclick="var f=$('#new-pw');f.attr('type',f.attr('type')==='password'?'text':'password')">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <!-- Password strength bar -->
              <div class="mt-2"><div id="pw-bar" style="height:4px;border-radius:4px;background:#e2e8f0;transition:.3s"><div id="pw-fill" style="height:100%;width:0;border-radius:4px;background:#dc2626;transition:.3s"></div></div></div>
              <div id="pw-label" class="text-muted mt-1" style="font-size:.78rem"></div>
            </div>
            <div class="mb-4">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
            </div>
            <div id="reset-alert"></div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> Reset Password
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
$(function(){
  // Strength meter
  $('#new-pw').on('input', function(){
    var v=this.value, s=0;
    if(v.length>=8)s++;if(v.length>=12)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    var pct=[0,20,40,65,85,100][s], col=['#dc2626','#f97316','#eab308','#3b82f6','#059669'][Math.max(0,s-1)]||'#dc2626';
    var lbl=['','Very Weak','Weak','Fair','Strong','Very Strong'][s]||'';
    $('#pw-fill').css({width:pct+'%',background:col});$('#pw-label').text(lbl).css('color',col);
  });
  $('#reset-frm').submit(function(e){
    e.preventDefault();
    if($('[name=password]').val()!==$('[name=confirm_password]').val()){
      $('#reset-alert').html('<div class="alert alert-danger">Passwords do not match.</div>'); return;
    }
    $('#reset-alert').html(''); start_loader();
    $.ajax({
      url:_base_url_+'classes/Login.php?f=do_reset',
      data:new FormData(this),cache:false,contentType:false,processData:false,
      method:'POST',dataType:'json',
      error:()=>{alert_toast('Error. Try again.','error');end_loader();},
      success:function(r){
        end_loader();
        if(r.status==='success'){
          $('#reset-frm').hide();
          $('#reset-alert').html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>'+r.msg+' <a href="<?= base_url ?>?page=login">Sign in now</a></div>');
        } else {
          $('#reset-alert').html('<div class="alert alert-danger">'+r.msg+'</div>');
        }
      }
    });
  });
});
</script>
