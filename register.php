<?php if(isset($_SESSION['pub_userdata'])){ redirect('?page=my-items'); exit; } ?>
<div class="container-xl px-4 py-4">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
      <div class="text-center mb-4">
        <h2 class="fw-bold" style="color:var(--saf-dark)">Create an Account</h2>
        <p class="text-muted">Join Smart Asset Finder to track your submissions and claims.</p>
      </div>
      <div class="saf-form-card">
        <form id="register-frm" novalidate>
          <input type="hidden" name="ref" value="<?= htmlspecialchars($_SESSION['saf_ref'] ?? $_GET['ref'] ?? '') ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="firstname" class="form-control" required placeholder="John">
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="lastname" class="form-control" required placeholder="Doe">
            </div>
            <div class="col-12">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="you@example.com">
            </div>
            <div class="col-12">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="0XX XXX XXXX">
            </div>
            <div class="col-12">
              <label class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters" minlength="8">
            </div>
            <div class="col-12">
              <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
            </div>
            <div class="col-12">
              <div id="reg-alert"></div>
              <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-person-plus me-1"></i> Create Account
              </button>
            </div>
          </div>
        </form>
        <div class="text-center mt-3" style="font-size:.85rem">
          Already have an account? <a href="<?= base_url ?>?page=login">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
$(function(){
  $('#register-frm').submit(function(e){
    e.preventDefault();
    var p1 = $('[name=password]').val(), p2 = $('[name=confirm_password]').val();
    if(p1 !== p2){ $('#reg-alert').html('<div class="alert alert-danger">Passwords do not match.</div>'); return; }
    $('#reg-alert').html('');
    start_loader();
    $.ajax({
      url: _base_url_ + 'classes/Login.php?f=register',
      data: new FormData(this), cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: ()=>{ alert_toast('Error. Please try again.','error'); end_loader(); },
      success: function(r){
        if(r.status === 'success'){ location.replace(_base_url_+'?page=my-items'); }
        else { $('#reg-alert').html('<div class="alert alert-danger">'+r.msg+'</div>'); end_loader(); }
      }
    });
  });
});
</script>
