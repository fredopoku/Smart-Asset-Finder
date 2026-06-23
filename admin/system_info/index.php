<div class="pagetitle mb-3">
  <h1>System Settings</h1>
  <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li><li class="breadcrumb-item active">System Settings</li></ol></nav>
</div>

<div class="row g-4">

  <!-- General Info -->
  <div class="col-12">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0">General Information</h5>
        <p class="text-muted mb-0" style="font-size:.83rem">Basic site identity settings</p>
      </div>
      <div class="card-body px-4 py-4">
        <form id="system-frm">
          <div id="msg"></div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">System Name</label>
              <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($_settings->info('name')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Short Name</label>
              <input type="text" class="form-control" name="short_name" value="<?= htmlspecialchars($_settings->info('short_name')) ?>">
            </div>
          </div>

          <!-- Logo -->
          <div class="mb-4">
            <label class="form-label fw-semibold">System Logo</label>
            <input type="file" name="img" class="form-control" accept="image/*" onchange="prevLogo(this)">
            <div class="mt-2">
              <img src="<?= validate_image($_settings->info('logo')) ?>" id="cimg" class="rounded-3 border" style="max-height:80px;max-width:200px;object-fit:contain;background:#f8faff;padding:8px">
            </div>
          </div>

          <!-- Cover -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Website Cover Image</label>
            <input type="file" name="cover" class="form-control" accept="image/*" onchange="prevCover(this)">
            <div class="mt-2">
              <img src="<?= validate_image($_settings->info('cover')) ?>" id="cimg2" class="rounded-3 border w-100" style="max-height:200px;object-fit:cover">
            </div>
          </div>

          <!-- Banners -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Banner Images</label>
            <input type="file" name="banners[]" multiple accept=".png,.jpg,.jpeg,.webp" class="form-control">
            <div class="form-text">Recommended: 1400 × 560 px. Uploading new banners replaces existing ones.</div>
            <?php
            $bpath = "uploads/banner";
            if(is_dir(base_app.$bpath)){
              $files = array_diff(scandir(base_app.$bpath), ['.','..']);
              if($files): ?>
            <div class="row g-2 mt-2">
              <?php foreach($files as $img): ?>
              <div class="col-auto img-item">
                <div class="position-relative" style="width:130px">
                  <img src="<?= base_url.$bpath.'/'.$img ?>?v=<?= time() ?>" class="rounded-3 border" style="width:130px;height:80px;object-fit:cover">
                  <button type="button" class="btn btn-danger btn-sm rounded-circle rem_img position-absolute top-0 end-0" style="width:22px;height:22px;padding:0;font-size:.7rem;line-height:1" data-path="<?= htmlspecialchars(base_app.$bpath.'/'.$img) ?>">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; } ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
              <i class="bi bi-check-lg me-1"></i>Save Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
function prevLogo(input){
  if(input.files && input.files[0]){
    var r = new FileReader();
    r.onload = function(e){ document.getElementById('cimg').src = e.target.result; };
    r.readAsDataURL(input.files[0]);
  }
}
function prevCover(input){
  if(input.files && input.files[0]){
    var r = new FileReader();
    r.onload = function(e){ document.getElementById('cimg2').src = e.target.result; };
    r.readAsDataURL(input.files[0]);
  }
}
function delete_img(path){
  start_loader();
  $.ajax({
    url: _base_url_+'classes/Master.php?f=delete_img',
    data: {path: path}, method: 'POST', dataType: 'json',
    error: function(){ alert_toast("Error deleting image",'error'); end_loader(); },
    success: function(r){
      if(r && r.status === 'success'){
        $('[data-path="'+path+'"]').closest('.img-item').hide('slow',function(){ $(this).remove(); });
        alert_toast("Image deleted",'success');
      } else {
        alert_toast("Error deleting image",'error');
      }
      end_loader();
    }
  });
}
$(function(){
  $('.rem_img').click(function(){
    _conf("Delete this banner image permanently?", 'delete_img', ["'"+$(this).data('path')+"'"]);
  });
  $('#system-frm').submit(function(e){
    e.preventDefault();
    start_loader();
    $.ajax({
      url: _base_url_+'classes/Master.php?f=save_system_info',
      data: new FormData(this),
      cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: function(){ alert_toast("An error occurred",'error'); end_loader(); },
      success: function(r){
        if(r && r.status === 'success'){
          alert_toast(r.msg || 'Settings saved',' success');
          end_loader();
        } else {
          alert_toast(r && r.msg ? r.msg : 'An error occurred','error');
          end_loader();
        }
      }
    });
  });
});
</script>
