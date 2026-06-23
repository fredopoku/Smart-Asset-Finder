<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?= $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="fw-bold mb-0">Item Claims</h5>
    <p class="text-muted mb-0" style="font-size:.83rem">Review and action user-submitted ownership claims</p>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:14px">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" id="claims-table">
        <thead class="table-light" style="font-size:.82rem">
          <tr>
            <th class="ps-4">#</th>
            <th>Claimant</th>
            <th>Item</th>
            <th>Contact</th>
            <th>Submitted</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $claims = $conn->query("SELECT ic.*, il.title as item_title, il.item_type FROM item_claims ic LEFT JOIN item_list il ON il.id=ic.item_id ORDER BY ic.created_at DESC");
          $i = 1;
          while($cl=$claims->fetch_assoc()):
            $sc = [0=>'status-pending',1=>'status-published',2=>'status-rejected'];
            $sl = [0=>'Pending',1=>'Approved',2=>'Rejected'];
          ?>
          <tr>
            <td class="ps-4"><?= $i++ ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($cl['fullname']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($cl['email']) ?></div>
            </td>
            <td>
              <a href="<?= base_url ?>admin?page=items/view_item&id=<?= $cl['item_id'] ?>" class="text-decoration-none">
                <?= htmlspecialchars($cl['item_title']??'') ?>
              </a>
              <span class="badge rounded-pill ms-1 <?= $cl['item_type']?'badge-found':'badge-lost' ?>"><?= $cl['item_type']?'Found':'Lost' ?></span>
            </td>
            <td style="font-size:.82rem">
              <?= htmlspecialchars($cl['phone']) ?><br>
              <a href="mailto:<?= htmlspecialchars($cl['email']) ?>" style="font-size:.75rem"><?= htmlspecialchars($cl['email']) ?></a>
            </td>
            <td style="font-size:.8rem"><?= date('M j, Y', strtotime($cl['created_at'])) ?></td>
            <td><span class="badge rounded-pill <?= $sc[$cl['status']] ?>"><?= $sl[$cl['status']] ?></span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="view_claim(<?= $cl['id'] ?>)">
                <i class="bi bi-eye me-1"></i>Review
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Claim detail modal content loaded via AJAX -->
<script>
$(function(){
  const dT = new simpleDatatables.DataTable('#claims-table');
});

function view_claim(id){
  uni_modal('Review Claim', _base_url_+'admin/claims/view_claim.php?id='+id, 'modal-lg');
}
</script>
