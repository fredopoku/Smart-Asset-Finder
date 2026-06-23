<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
  $stmt = $conn->prepare("SELECT * FROM category_list WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $_GET['id']);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if($row) foreach($row as $k=>$v) $$k=$v;
}
?>

<div class="pagetitle mb-3">
  <h1><?= isset($id) ? 'Edit Category' : 'Add Category' ?></h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url ?>admin?page=categories">Categories</a></li>
    <li class="breadcrumb-item active"><?= isset($id) ? 'Edit' : 'Add' ?></li>
  </ol></nav>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7 col-md-9">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0"><?= isset($id) ? 'Update Category' : 'New Category' ?></h5>
      </div>
      <div class="card-body px-4 py-4">
        <form id="category-form">
          <input type="hidden" name="id" value="<?= isset($id) ? (int)$id : '' ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Category Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars(isset($name)?$name:'') ?>" autofocus required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="description" class="form-control tinymce-editor" rows="4" required><?= isset($description) ? $description : '' ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" id="status" class="form-select" required>
              <option value="1" <?= isset($status)&&$status==1?'selected':'' ?>>Active</option>
              <option value="0" <?= isset($status)&&$status==0?'selected':'' ?>>Inactive</option>
            </select>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
              <i class="bi bi-check-lg me-1"></i>Save Category
            </button>
            <a href="./?page=categories" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  $('#category-form').submit(function(e){
    e.preventDefault();
    var _this = $(this);
    $('.err-msg').remove();
    start_loader();
    $.ajax({
      url: _base_url_+"classes/Master.php?f=save_category",
      data: new FormData(this),
      cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: function(){ alert_toast("An error occurred",'error'); end_loader(); },
      success: function(r){
        if(r && r.status === 'success'){
          location.replace('./?page=categories/view_category&id='+r.sid);
        } else if(r && r.status === 'failed' && r.msg){
          var el = $('<div class="alert alert-danger err-msg">').text(r.msg);
          _this.prepend(el); el.show('slow');
          $('html,body').scrollTop(0); end_loader();
        } else {
          alert_toast("An error occurred",'error'); end_loader();
        }
      }
    });
  });
});
</script>
