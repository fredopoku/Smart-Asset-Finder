<?php if(isset($_SESSION['pub_userdata'])){ redirect('?page=my-items'); exit; } ?>
<div class="container-xl px-4 py-4">
  <div class="row justify-content-center">
    <div class="col-lg-4 col-md-6">
      <div class="text-center mb-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
          style="width:64px;height:64px;background:linear-gradient(135deg,#1a56db,#0ea5e9)">
          <i class="bi bi-key-fill text-white fs-3"></i>
        </div>
        <h2 class="fw-bold" style="color:var(--saf-dark)">Forgot Password?</h2>
        <p class="text-muted">Enter your email and we'll send you a reset link.</p>
      </div>
      <div class="saf-form-card">
        <form id="forgot-frm" novalidate>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="you@example.com" autofocus>
          </div>
          <div id="forgot-alert"></div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-send me-1"></i> Send Reset Link
          </button>
        </form>
        <div class="text-center mt-3" style="font-size:.85rem">
          Remembered it? <a href="<?= base_url ?>?page=login">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
$(function(){
  $('#forgot-frm').submit(function(e){
    e.preventDefault();
    $('#forgot-alert').html('');
    start_loader();
    $.ajax({
      url: _base_url_+'classes/Login.php?f=request_reset',
      data: new FormData(this), cache:false, contentType:false, processData:false,
      method:'POST', dataType:'json',
      error: ()=>{ alert_toast('Error. Try again.','error'); end_loader(); },
      success: function(r){
        end_loader();
        if(r.status==='success'){
          $('#forgot-frm').hide();
          $('#forgot-alert').html('<div class="alert alert-success"><i class="bi bi-envelope-check me-2"></i>'+r.msg+'</div>');
        } else {
          $('#forgot-alert').html('<div class="alert alert-danger">'+r.msg+'</div>');
        }
      }
    });
  });
});
</script>
