<div class="container-xl px-4 py-5">
  <div class="row g-5">

    <!-- Contact Info -->
    <div class="col-lg-4">
      <h2 class="fw-bold mb-1">Get in Touch</h2>
      <p class="text-muted mb-4">Questions about an item, a claim, or the platform? We're here to help.</p>
      <div class="d-flex flex-column gap-4">
        <div class="d-flex gap-3 align-items-start">
          <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
            style="width:48px;height:48px;background:linear-gradient(135deg,#1a56db,#0ea5e9)">
            <i class="bi bi-geo-alt-fill text-white"></i>
          </div>
          <div>
            <div class="fw-semibold">Location</div>
            <div class="text-muted small"><?= htmlspecialchars($_settings->info('address') ?: 'Available Worldwide') ?></div>
          </div>
        </div>
        <?php if($_settings->info('phone')): ?>
        <div class="d-flex gap-3 align-items-start">
          <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
            style="width:48px;height:48px;background:linear-gradient(135deg,#059669,#10b981)">
            <i class="bi bi-telephone-fill text-white"></i>
          </div>
          <div>
            <div class="fw-semibold">Phone</div>
            <div class="text-muted small"><?= htmlspecialchars($_settings->info('phone')) ?></div>
          </div>
        </div>
        <?php endif; ?>
        <?php if($_settings->info('email')): ?>
        <div class="d-flex gap-3 align-items-start">
          <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
            style="width:48px;height:48px;background:linear-gradient(135deg,#7c3aed,#a78bfa)">
            <i class="bi bi-envelope-fill text-white"></i>
          </div>
          <div>
            <div class="fw-semibold">Email</div>
            <div class="text-muted small"><a href="mailto:<?= htmlspecialchars($_settings->info('email')) ?>" class="text-decoration-none"><?= htmlspecialchars($_settings->info('email')) ?></a></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Social links -->
      <?php $fb=$_settings->info('social_facebook');$tw=$_settings->info('social_twitter');$ig=$_settings->info('social_instagram');$li=$_settings->info('social_linkedin'); ?>
      <?php if($fb||$tw||$ig||$li): ?>
      <div class="mt-4">
        <div class="fw-semibold mb-2">Follow Us</div>
        <div class="d-flex gap-2">
          <?php if($fb): ?><a href="<?=htmlspecialchars($fb)?>" class="btn btn-sm btn-outline-primary rounded-circle" style="width:38px;height:38px;padding:0;line-height:36px"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if($tw): ?><a href="<?=htmlspecialchars($tw)?>" class="btn btn-sm btn-outline-info rounded-circle" style="width:38px;height:38px;padding:0;line-height:36px"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
          <?php if($ig): ?><a href="<?=htmlspecialchars($ig)?>" class="btn btn-sm btn-outline-danger rounded-circle" style="width:38px;height:38px;padding:0;line-height:36px"><i class="bi bi-instagram"></i></a><?php endif; ?>
          <?php if($li): ?><a href="<?=htmlspecialchars($li)?>" class="btn btn-sm btn-outline-primary rounded-circle" style="width:38px;height:38px;padding:0;line-height:36px"><i class="bi bi-linkedin"></i></a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Contact Form -->
    <div class="col-lg-8">
      <div class="saf-form-card">
        <h5 class="fw-bold mb-4">Send Us a Message</h5>
        <form id="contact-frm" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Your Name <span class="text-danger">*</span></label>
              <input type="text" name="fullname" class="form-control" required placeholder="John Doe"
                value="<?= isset($_SESSION['pub_userdata']) ? htmlspecialchars($_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname']) : '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="you@example.com"
                value="<?= isset($_SESSION['pub_userdata']) ? htmlspecialchars($_SESSION['pub_userdata']['email']) : '' ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Phone <span class="text-muted small">(optional)</span></label>
              <input type="tel" name="contact" class="form-control" placeholder="+1 234 567 890"
                value="<?= isset($_SESSION['pub_userdata']) ? htmlspecialchars($_SESSION['pub_userdata']['phone'] ?? '') : '' ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Message <span class="text-danger">*</span></label>
              <textarea name="message" class="form-control" rows="5" required placeholder="Tell us about your lost or found item, or ask us anything…"></textarea>
            </div>
            <div class="col-12">
              <div id="contact-alert"></div>
              <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold">
                <i class="bi bi-send me-1"></i> Send Message
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
<script>
$(function(){
  $('#contact-frm').submit(function(e){
    e.preventDefault();
    $('#contact-alert').html(''); start_loader();
    $.ajax({
      url: _base_url_+'classes/Master.php?f=save_inquiry',
      data: new FormData(this), cache:false, contentType:false, processData:false,
      method:'POST', dataType:'json',
      error:()=>{alert_toast('Error. Try again.','error');end_loader();},
      success:function(r){
        end_loader();
        if(r.status==='success'){
          $('#contact-frm')[0].reset();
          $('#contact-alert').html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>'+r.msg+'</div>');
        } else {
          $('#contact-alert').html('<div class="alert alert-danger">'+r.msg+'</div>');
        }
      }
    });
  });
});
</script>
