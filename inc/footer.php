<script>
  $(document).ready(function(){
     window.viewer_modal = function($src = ''){
      start_loader()
      var ext = $src.split('.').pop().split('?')[0].toLowerCase()
      var isVideo = ['mp4','webm','mov','avi','mpg','mpeg'].indexOf(ext) !== -1
      if(isVideo){
        var view = $("<video src='"+$src+"' controls autoplay playsinline style='max-width:100%;max-height:80vh;border-radius:8px'></video>")
      }else{
        var view = $("<img src='"+$src+"' />")
      }
      $('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove();
      $('#viewer_modal .modal-content').append(view);
      var vm = new bootstrap.Modal(document.getElementById('viewer_modal'), { backdrop: true, keyboard: true });
      vm.show();
      end_loader();

  }
    window.uni_modal = function($title, $url, $size) {
        $title = $title || ''; $url = $url || ''; $size = $size || '';
        start_loader();
        $.ajax({
            url: $url,
            error: function() {
                alert_toast('Failed to load content. Please try again.', 'error');
                end_loader();
            },
            success: function(resp) {
                if (resp) {
                    $('#uni_modal .modal-title').html($title);
                    $('#uni_modal .modal-body').html(resp);
                    var dlg = $('#uni_modal .modal-dialog');
                    if ($size) {
                        dlg.addClass($size + ' modal-dialog-centered');
                    } else {
                        dlg.attr('class', 'modal-dialog modal-md modal-dialog-centered');
                    }
                    var m = new bootstrap.Modal(document.getElementById('uni_modal'), { backdrop: 'static', keyboard: false });
                    m.show();
                    end_loader();
                }
            }
        });
    }
    window._conf = function($msg, $func, $params) {
        $msg = $msg || ''; $func = $func || ''; $params = $params || [];
        $('#confirm_modal #confirm').attr('onclick', $func + '(' + $params.join(',') + ')');
        $('#confirm_modal .modal-body').html(
            '<div class="d-flex gap-3 align-items-start">'
          + '<i class="bi bi-exclamation-triangle-fill text-warning fs-4 flex-shrink-0"></i>'
          + '<div>' + $msg + '</div>'
          + '</div>'
        );
        var m = new bootstrap.Modal(document.getElementById('confirm_modal'));
        m.show();
    }
  })
</script>
<!-- ======= Footer ======= -->
<footer id="footer">
  <div class="container-xl px-4">
    <div class="row g-5">

      <!-- Brand column -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="<?= validate_image($_settings->info('logo')) ?>" alt="SAF" style="height:32px;border-radius:7px">
          <span class="footer-logo-text">Smart Asset Finder</span>
        </div>
        <p class="footer-tagline">
          The global platform for lost &amp; found items — free QR tags, GPS tracking, and AI-powered matching that reunites people with what they lost.
        </p>
        <div class="footer-social d-flex gap-2 mt-4">
          <a href="#" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
          <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
          <a href="https://github.com/fredopoku/Smart-Asset-Finder" target="_blank" title="GitHub"><i class="bi bi-github"></i></a>
        </div>
      </div>

      <!-- Platform links -->
      <div class="col-6 col-lg-2 col-md-3">
        <div class="footer-col-head">Platform</div>
        <a href="<?= base_url ?>?page=items" class="footer-link">Browse Items</a>
        <a href="<?= base_url ?>?page=found" class="footer-link">Report Found</a>
        <a href="<?= base_url ?>?page=lost" class="footer-link">Report Lost</a>
        <a href="<?= base_url ?>?page=shop" class="footer-link">Shop SAF Tags</a>
        <a href="<?= base_url ?>?page=register" class="footer-link">Get Free QR Tag</a>
      </div>

      <!-- Company links -->
      <div class="col-6 col-lg-2 col-md-3">
        <div class="footer-col-head">Company</div>
        <a href="<?= base_url ?>?page=about" class="footer-link">About Us</a>
        <a href="<?= base_url ?>?page=contact" class="footer-link">Contact</a>
        <a href="<?= base_url ?>?page=login" class="footer-link">Sign In</a>
        <a href="<?= base_url ?>?page=register" class="footer-link">Register</a>
      </div>

      <!-- CTA -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-col-head">Get Protected Today</div>
        <div class="footer-cta">
          <p>Register for free and get your first QR tag instantly. Tag your valuables — phone, laptop, keys, bag — and never lose them again.</p>
          <a href="<?= base_url ?>?page=register" class="btn btn-sm btn-gradient rounded-pill px-4" style="font-weight:700">
            <i class="bi bi-person-plus me-1"></i>Create Free Account
          </a>
        </div>
      </div>

    </div>

    <hr class="footer-divider">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 footer-bottom">
      <span>&copy; <?= date('Y') ?> Smart Asset Finder. All rights reserved.</span>
      <span>Built by <span style="color:rgba(255,255,255,.5)">Frederick Opoku Afriyie</span></span>
    </div>
  </div>
</footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
   
<!-- Vendor JS Files -->
<script src="<?= base_url ?>assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="<?= base_url ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url ?>assets/vendor/chart.js/chart.umd.js"></script>
<script src="<?= base_url ?>assets/vendor/echarts/echarts.min.js"></script>
<script src="<?= base_url ?>assets/vendor/quill/quill.min.js"></script>
<script src="<?= base_url ?>assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="<?= base_url ?>assets/vendor/tinymce/tinymce.min.js"></script>
<script src="<?= base_url ?>assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="<?= base_url ?>assets/js/main.js"></script>
<!-- Paystack Inline (only loaded on shop/checkout pages) -->
<?php if(in_array($page??'', ['shop','my-orders'])): ?>
<script src="https://js.paystack.co/v1/inline.js"></script>
<?php endif; ?>