<?php if(isset($_SESSION['pub_userdata'])){ redirect('?page=my-items'); exit; } ?>
<div class="container-xl px-4 py-4">
  <div class="row justify-content-center">
    <div class="col-lg-4 col-md-6">
      <div class="text-center mb-4">
        <h2 class="fw-bold" style="color:var(--saf-dark)">Sign In</h2>
        <p class="text-muted">Welcome back! Sign in to manage your submissions.</p>
      </div>
      <div class="saf-form-card">
        <form id="login-user-frm" novalidate>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="you@example.com">
          </div>
          <div class="mb-4">
            <div class="d-flex justify-content-between">
              <label class="form-label">Password</label>
              <a href="<?= base_url ?>?page=forgot-password" class="text-muted" style="font-size:.82rem">Forgot password?</a>
            </div>
            <input type="password" name="password" class="form-control" required placeholder="Your password">
          </div>
          <div id="login-alert"></div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </button>
        </form>
        <div class="text-center mt-3" style="font-size:.85rem">
          Don't have an account? <a href="<?= base_url ?>?page=register">Register here</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
$(function(){
  $('#login-user-frm').submit(function(e){
    e.preventDefault();
    $('#login-alert').html('');
    start_loader();
    $.ajax({
      url: _base_url_ + 'classes/Login.php?f=login_user',
      data: new FormData(this), cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: ()=>{ alert_toast('Error. Please try again.','error'); end_loader(); },
      success: function(r){
        if(r.status === 'success'){ location.replace(_base_url_+'?page=my-items'); }
        else { $('#login-alert').html('<div class="alert alert-danger">'+r.msg+'</div>'); end_loader(); }
      }
    });
  });
});
</script>
