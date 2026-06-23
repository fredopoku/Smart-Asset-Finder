<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?= $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<div class="pagetitle mb-3">
  <h1>Inquiries</h1>
  <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li><li class="breadcrumb-item active">Inquiries</li></ol></nav>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
    <h5 class="fw-bold mb-0">Contact Messages</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="list">
        <thead class="table-light" style="font-size:.82rem">
          <tr>
            <th class="ps-4">#</th>
            <th>Sender</th>
            <th>Date</th>
            <th>Status</th>
            <th class="pe-4 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $qry = $conn->query("SELECT * FROM inquiry_list ORDER BY status ASC, created_at DESC");
          while($row = $qry->fetch_assoc()):
          ?>
          <tr>
            <td class="ps-4 text-muted" style="font-size:.82rem"><?= $i++ ?></td>
            <td>
              <div class="fw-semibold" style="font-size:.87rem"><?= htmlspecialchars($row['fullname']) ?></div>
              <?php if(!empty($row['email'])): ?>
              <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($row['email']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:#94a3b8;white-space:nowrap"><?= date('M j, Y · g:i a', strtotime($row['created_at'])) ?></td>
            <td>
              <?php if($row['status'] == 1): ?>
                <span class="badge rounded-pill bg-success-subtle text-success">Read</span>
              <?php else: ?>
                <span class="badge rounded-pill bg-warning-subtle text-warning">Unread</span>
              <?php endif; ?>
            </td>
            <td class="pe-4 text-center">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" data-bs-toggle="dropdown">
                  Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="font-size:.84rem">
                  <li><a class="dropdown-item" href="./?page=inquiries/view_inquiry&id=<?= $row['id'] ?>"><i class="bi bi-envelope-open me-2 text-primary"></i>View</a></li>
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
    _conf("Are you sure you want to delete this inquiry permanently?","delete_inquiry",[$(this).data('id')])
  });
  new simpleDatatables.DataTable('#list');
});
function delete_inquiry(id){
  start_loader();
  $.ajax({
    url: _base_url_+"classes/Master.php?f=delete_inquiry",
    method: "POST", dataType: "json", data: {id: id},
    error: function(){ alert_toast("An error occurred.",'error'); end_loader(); },
    success: function(r){
      if(r.status === 'success') location.reload();
      else { alert_toast("An error occurred.",'error'); end_loader(); }
    }
  });
}
</script>
