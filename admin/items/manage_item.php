<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
  $stmt = $conn->prepare("SELECT * FROM item_list WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $_GET['id']);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if($row) foreach($row as $k=>$v) $$k=$v;
}
?>

<div class="pagetitle mb-3">
  <h1><?= isset($id) ? 'Edit Item' : 'Add Item' ?></h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url ?>admin?page=items">Items</a></li>
    <li class="breadcrumb-item active"><?= isset($id) ? 'Edit' : 'Add' ?></li>
  </ol></nav>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7 col-md-9">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0"><?= isset($id) ? 'Update Item Details' : 'New Item Entry' ?></h5>
      </div>
      <div class="card-body px-4 py-4">
        <form id="items-form">
          <input type="hidden" name="id" value="<?= isset($id) ? (int)$id : '' ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Category</label>
            <select name="category_id" id="category_id" class="form-select" required>
              <option value="" disabled <?= !isset($category_id) ? 'selected' : '' ?>>Select a category</option>
              <?php
              $query = $conn->query("SELECT * FROM category_list WHERE status=1 ORDER BY name ASC");
              while($r = $query->fetch_assoc()):
              ?>
              <option value="<?= $r['id'] ?>" <?= isset($category_id) && $category_id==$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Item Type</label>
            <select name="item_type" id="item_type" class="form-select" required>
              <option value="0" <?= isset($item_type) && $item_type==0 ? 'selected' : '' ?>>Lost</option>
              <option value="1" <?= isset($item_type) && $item_type==1 ? 'selected' : '' ?>>Found</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Reported By (Full Name)</label>
            <input type="text" name="fullname" id="fullname" class="form-control" value="<?= htmlspecialchars(isset($fullname)?$fullname:'') ?>" autofocus required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars(isset($title)?$title:'') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Contact Number</label>
            <input type="text" name="contact" id="contact" class="form-control" value="<?= htmlspecialchars(isset($contact)?$contact:'') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="description" class="form-control" rows="4" required><?= htmlspecialchars(isset($description)?$description:'') ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Item Image</label>
            <input type="file" name="image" id="item-image" class="form-control" accept="image/png,image/jpeg,image/webp" onchange="previewImg(this)">
          </div>

          <?php $imgSrc = validate_image(isset($image_path)?$image_path:''); ?>
          <div class="mb-3 text-center" id="img-preview-wrap" style="<?= $imgSrc ? '' : 'display:none' ?>">
            <img src="<?= $imgSrc ?>" id="cimg" class="img-fluid rounded-3" style="max-height:200px;object-fit:cover">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" id="status" class="form-select" required>
              <option value="0" <?= isset($status)&&$status==0?'selected':'' ?>>Pending</option>
              <option value="1" <?= isset($status)&&$status==1?'selected':'' ?>>Published</option>
              <option value="2" <?= isset($status)&&$status==2?'selected':'' ?>>Claimed</option>
            </select>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
              <i class="bi bi-check-lg me-1"></i>Save Item
            </button>
            <a href="./?page=items" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function previewImg(input){
  if(input.files && input.files[0]){
    var reader = new FileReader();
    reader.onload = function(e){
      document.getElementById('cimg').src = e.target.result;
      document.getElementById('img-preview-wrap').style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

$(function(){
  $('#category_id').select2({ placeholder: 'Select a category', width: '100%' });

  $('#items-form').submit(function(e){
    e.preventDefault();
    var _this = $(this);
    $('.err-msg').remove();
    start_loader();
    $.ajax({
      url: _base_url_+"classes/Master.php?f=save_item",
      data: new FormData(this),
      cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: function(){ alert_toast("An error occurred",'error'); end_loader(); },
      success: function(r){
        if(r && r.status === 'success'){
          location.replace('./?page=items/view_item&id='+r.iid);
        } else if(r && r.status === 'failed' && r.msg){
          var el = $('<div class="alert alert-danger err-msg">').text(r.msg);
          _this.prepend(el);
          el.show('slow');
          $('html,body').scrollTop(0);
          end_loader();
        } else {
          alert_toast("An error occurred",'error');
          end_loader();
        }
      }
    });
  });
});
</script>
