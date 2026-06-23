<?php
$search = trim($_GET['s'] ?? '');
$where  = $search ? "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ?)" : "";
$sql    = "SELECT * FROM registered_users $where ORDER BY created_at DESC";

if($search){
  $like = "%$search%";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('sss', $like, $like, $like);
  $stmt->execute();
  $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  $users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
$total_users = count($users);
?>
<style>
.usr-av { width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0; }
.usr-av-init {
  width:40px;height:40px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:.88rem;font-weight:700;color:#fff;flex-shrink:0;
}
/* Detail modal */
.ud-hero {
  background: linear-gradient(135deg,#1a56db,#7c3aed);
  border-radius: 16px 16px 0 0; padding: 1.75rem 1.5rem 2.5rem;
  text-align: center; position: relative;
}
.ud-avatar {
  width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,.4);
  object-fit:cover; margin-bottom:.75rem;
}
.ud-avatar-init {
  width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,.3);
  background:rgba(255,255,255,.18);display:inline-flex;align-items:center;
  justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;
  font-family:'Space Grotesk',sans-serif;margin-bottom:.75rem;
}
.ud-stat { text-align:center;padding:.6rem .3rem; }
.ud-stat-num { font-family:'Space Grotesk',sans-serif;font-size:1.3rem;font-weight:800;color:#1e293b;line-height:1; }
.ud-stat-lbl { font-size:.68rem;color:#94a3b8;margin-top:.2rem; }
</style>

<div class="pagetitle mb-3">
  <h1>Registered Users</h1>
  <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li><li class="breadcrumb-item active">Registered Users</li></ol></nav>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="fw-bold mb-0">All Registered Users <span class="badge bg-primary ms-2"><?= $total_users ?></span></h5>
    <form class="d-flex gap-2" method="GET" action="">
      <input type="hidden" name="page" value="registered_users">
      <input type="text" name="s" class="form-control form-control-sm" placeholder="Search name or email…" value="<?= htmlspecialchars($search) ?>" style="width:220px">
      <button class="btn btn-sm btn-primary rounded-pill px-3">Search</button>
      <?php if($search): ?><a href="?page=registered_users" class="btn btn-sm btn-outline-secondary rounded-pill">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th class="ps-4">#</th>
            <th>User</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Items</th>
            <th>Claims</th>
            <th>Orders</th>
            <th>Joined</th>
            <th>Status</th>
            <th class="pe-4">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if($total_users === 0): ?>
          <tr><td colspan="10" class="text-center py-5 text-muted">No users found.</td></tr>
        <?php else: $i=1; foreach($users as $u):
          $uid = (int)$u['id'];
          $item_count  = (int)$conn->query("SELECT COUNT(*) c FROM item_list WHERE user_id={$uid}")->fetch_assoc()['c'];
          $claim_count = (int)$conn->query("SELECT COUNT(*) c FROM item_claims WHERE user_id={$uid}")->fetch_assoc()['c'];
          $order_count = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE user_id={$uid}")->fetch_assoc()['c'];
          $points      = (int)($conn->query("SELECT COALESCE(SUM(points),0) p FROM point_transactions WHERE user_id={$uid}")->fetch_assoc()['p'] ?? 0);
          $av_url      = !empty($u['avatar']) && is_file(base_app.$u['avatar']) ? base_url.$u['avatar'] : '';
          $initials    = strtoupper(substr($u['firstname'],0,1).substr($u['lastname'],0,1));
          $full_name   = ucwords(strtolower(trim($u['firstname'].' '.$u['lastname'])));
          // gradient for initials (deterministic, based on uid)
          $grad_colors = ['#1a56db,#0ea5e9','#7c3aed,#c084fc','#059669,#34d399','#b45309,#f59e0b','#be123c,#fb7185'];
          $grad = $grad_colors[$uid % count($grad_colors)];
        ?>
          <tr>
            <td class="ps-4 text-muted" style="font-size:.82rem"><?= $i++ ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if($av_url): ?>
                <img src="<?= $av_url ?>" class="usr-av" alt="<?= htmlspecialchars($initials) ?>">
                <?php else: ?>
                <div class="usr-av-init" style="background:linear-gradient(135deg,<?= $grad ?>)"><?= $initials ?></div>
                <?php endif; ?>
                <div>
                  <div class="fw-semibold" style="font-size:.9rem"><?= htmlspecialchars($full_name) ?></div>
                  <?php if($u['email_verified']): ?>
                  <span class="badge bg-success-subtle text-success" style="font-size:.65rem">Verified</span>
                  <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning" style="font-size:.65rem">Unverified</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="font-size:.83rem"><a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($u['email']) ?></a></td>
            <td style="font-size:.83rem"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
            <td><span class="badge bg-primary-subtle text-primary"><?= $item_count ?></span></td>
            <td><span class="badge bg-secondary-subtle text-secondary"><?= $claim_count ?></span></td>
            <td><span class="badge bg-success-subtle text-success"><?= $order_count ?></span></td>
            <td style="font-size:.8rem;color:#64748b;white-space:nowrap"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if($u['status'] == 1): ?>
                <span class="badge rounded-pill bg-success-subtle text-success">Active</span>
              <?php else: ?>
                <span class="badge rounded-pill bg-danger-subtle text-danger">Banned</span>
              <?php endif; ?>
            </td>
            <td class="pe-4">
              <div class="d-flex gap-1 flex-wrap">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-2"
                  onclick="viewUser(<?= htmlspecialchars(json_encode([
                    'id'         => $uid,
                    'name'       => $full_name,
                    'email'      => $u['email'],
                    'phone'      => $u['phone'] ?: '',
                    'initials'   => $initials,
                    'av_url'     => $av_url,
                    'grad'       => $grad,
                    'verified'   => (bool)$u['email_verified'],
                    'status'     => (int)$u['status'],
                    'items'      => $item_count,
                    'claims'     => $claim_count,
                    'orders'     => $order_count,
                    'points'     => $points,
                    'joined'     => date('F j, Y', strtotime($u['created_at'])),
                  ]), ENT_QUOTES) ?>)" title="View profile">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm <?= $u['status']==1?'btn-outline-danger':'btn-outline-success' ?> rounded-pill px-2"
                  onclick="toggleUser(<?= $uid ?>, <?= $u['status'] ?>)" title="<?= $u['status']==1?'Ban':'Activate' ?>">
                  <i class="bi bi-<?= $u['status']==1 ? 'slash-circle' : 'check-circle' ?>"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── User Detail Modal ── -->
<div class="modal fade" id="user-detail-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

      <div class="ud-hero" id="ud-hero">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
        <div id="ud-avatar-wrap"></div>
        <h5 class="fw-bold text-white mb-0" id="ud-name" style="font-family:'Space Grotesk',sans-serif"></h5>
        <div class="text-white-50" id="ud-email" style="font-size:.8rem;margin-top:.2rem"></div>
        <div class="d-flex gap-2 justify-content-center mt-2 flex-wrap" id="ud-badges"></div>
      </div>

      <div class="modal-body p-4">
        <!-- Stats strip -->
        <div class="row g-2 mb-3 text-center">
          <div class="col-3 ud-stat">
            <div class="ud-stat-num text-warning" id="ud-pts"></div>
            <div class="ud-stat-lbl">Points</div>
          </div>
          <div class="col-3 ud-stat">
            <div class="ud-stat-num text-primary" id="ud-items"></div>
            <div class="ud-stat-lbl">Reports</div>
          </div>
          <div class="col-3 ud-stat">
            <div class="ud-stat-num text-purple" id="ud-claims" style="color:#7c3aed"></div>
            <div class="ud-stat-lbl">Claims</div>
          </div>
          <div class="col-3 ud-stat">
            <div class="ud-stat-num text-success" id="ud-orders"></div>
            <div class="ud-stat-lbl">Orders</div>
          </div>
        </div>

        <hr class="my-2">

        <!-- Details list -->
        <ul class="list-unstyled mb-0" style="font-size:.84rem">
          <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="bi bi-telephone me-2"></i>Phone</span>
            <span id="ud-phone" class="fw-semibold"></span>
          </li>
          <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="bi bi-patch-check me-2"></i>Email</span>
            <span id="ud-verified" class="fw-semibold"></span>
          </li>
          <li class="d-flex align-items-center justify-content-between py-1">
            <span class="text-muted"><i class="bi bi-calendar3 me-2"></i>Joined</span>
            <span id="ud-joined" class="fw-semibold"></span>
          </li>
        </ul>

        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1" id="ud-toggle-btn"></button>
          <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var _udModal = null;
var _udCurrentId = null;
var _udCurrentStatus = null;

function viewUser(u){
  _udCurrentId = u.id;
  _udCurrentStatus = u.status;

  // Avatar
  var avWrap = document.getElementById('ud-avatar-wrap');
  if(u.av_url){
    avWrap.innerHTML = '<img src="'+u.av_url+'" class="ud-avatar" alt="">';
  } else {
    avWrap.innerHTML = '<div class="ud-avatar-init" style="background:linear-gradient(135deg,'+u.grad+')">'+u.initials+'</div>';
  }

  // Hero gradient (per user)
  document.getElementById('ud-hero').style.background = 'linear-gradient(135deg,'+u.grad+')';

  document.getElementById('ud-name').textContent    = u.name;
  document.getElementById('ud-email').textContent   = u.email;
  document.getElementById('ud-pts').textContent     = u.points.toLocaleString();
  document.getElementById('ud-items').textContent   = u.items;
  document.getElementById('ud-claims').textContent  = u.claims;
  document.getElementById('ud-orders').textContent  = u.orders;
  document.getElementById('ud-phone').textContent   = u.phone || '—';
  document.getElementById('ud-joined').textContent  = u.joined;
  document.getElementById('ud-verified').innerHTML  = u.verified
    ? '<span class="badge bg-success-subtle text-success">Verified</span>'
    : '<span class="badge bg-warning-subtle text-warning">Not verified</span>';

  var tBtn = document.getElementById('ud-toggle-btn');
  if(u.status === 1){
    tBtn.textContent = 'Ban User';
    tBtn.className = 'btn btn-sm btn-outline-danger rounded-pill flex-grow-1';
  } else {
    tBtn.textContent = 'Activate User';
    tBtn.className = 'btn btn-sm btn-outline-success rounded-pill flex-grow-1';
  }
  tBtn.onclick = function(){ toggleUser(_udCurrentId, _udCurrentStatus); };

  if(!_udModal) _udModal = new bootstrap.Modal(document.getElementById('user-detail-modal'));
  _udModal.show();
}

function toggleUser(id, currentStatus){
  var msg = currentStatus==1
    ? 'This user will be <strong>banned</strong> and will not be able to log in.'
    : 'This user will be <strong>activated</strong> and can log in again.';
  _conf(msg, '_doToggleUser', [id, currentStatus]);
}
function _doToggleUser(id, currentStatus){
  bootstrap.Modal.getInstance(document.getElementById('confirm_modal'))?.hide();
  if(_udModal) _udModal.hide();
  start_loader();
  $.ajax({
    url: _base_url_+'classes/Master.php?f=toggle_user_status',
    method:'POST', dataType:'json',
    data: {id:id, status:currentStatus},
    success:function(r){
      if(r.status==='success'){ alert_toast(r.msg,'success'); setTimeout(()=>location.reload(),800); }
      else { alert_toast(r.msg||'Error.','error'); end_loader(); }
    },
    error: function(){ alert_toast('An error occurred.','error'); end_loader(); }
  });
}
</script>
