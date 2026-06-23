<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?= $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<div class="pagetitle mb-3">
  <h1>Items</h1>
  <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li><li class="breadcrumb-item active">Items</li></ol></nav>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="fw-bold mb-0">All Items</h5>
    <a href="<?= base_url ?>admin?page=items/manage_item" class="btn btn-primary rounded-pill px-4 fw-semibold">
      <i class="bi bi-plus-lg me-1"></i>Add Item
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="list">
        <thead class="table-light" style="font-size:.82rem">
          <tr>
            <th class="ps-4">#</th>
            <th>Date</th>
            <th>Reported By</th>
            <th>Title</th>
            <th>Type</th>
            <th>Status</th>
            <th class="pe-4 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $qry = $conn->query("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id ORDER BY il.created_at DESC");
          while($row = $qry->fetch_assoc()):
          ?>
          <tr>
            <td class="ps-4 text-muted" style="font-size:.82rem"><?= $i++ ?></td>
            <td style="font-size:.8rem;color:#64748b;white-space:nowrap"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
            <td style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($row['fullname']) ?></td>
            <td>
              <div style="font-size:.87rem;font-weight:600;max-width:260px" class="text-truncate"><?= htmlspecialchars($row['title']) ?></div>
              <?php if(!empty($row['cat_name'])): ?>
              <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($row['cat_name']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if(isset($row['item_type'])): ?>
              <span class="badge rounded-pill <?= $row['item_type'] ? 'badge-found' : 'badge-lost' ?>" style="font-size:.72rem">
                <?= $row['item_type'] ? 'Found' : 'Lost' ?>
              </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($row['status'] == 1): ?>
                <span class="badge rounded-pill bg-primary-subtle text-primary">Published</span>
              <?php elseif($row['status'] == 2): ?>
                <span class="badge rounded-pill bg-success-subtle text-success">Claimed</span>
              <?php else: ?>
                <span class="badge rounded-pill bg-secondary-subtle text-secondary">Pending</span>
              <?php endif; ?>
            </td>
            <td class="pe-4 text-center">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" data-bs-toggle="dropdown">
                  Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="font-size:.84rem">
                  <li><a class="dropdown-item" href="./?page=items/view_item&id=<?= $row['id'] ?>"><i class="bi bi-eye me-2 text-primary"></i>View</a></li>
                  <li><a class="dropdown-item" href="./?page=items/manage_item&id=<?= $row['id'] ?>"><i class="bi bi-pencil me-2 text-info"></i>Edit</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger delete_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>"><i class="bi bi-trash me-2"></i>Delete</a></li>
                </ul>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(function(){
  $('.delete_data').click(function(){
    _conf("Are you sure you want to delete this item permanently?","delete_item",[$(this).data('id')])
  });
  new simpleDatatables.DataTable('#list');
});
function delete_item(id){
  start_loader();
  $.ajax({
    url: _base_url_+"classes/Master.php?f=delete_item",
    method: "POST", dataType: "json", data: {id: id},
    error: function(){ alert_toast("An error occurred.",'error'); end_loader(); },
    success: function(r){
      if(r.status === 'success') location.reload();
      else { alert_toast("An error occurred.",'error'); end_loader(); }
    }
  });
}
</script>
