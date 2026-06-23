<?php
$stat_active_cats  = $conn->query("SELECT COUNT(*) c FROM category_list WHERE status=1")->fetch_assoc()['c'];
$stat_total_items  = $conn->query("SELECT COUNT(*) c FROM item_list")->fetch_assoc()['c'];
$stat_pending      = $conn->query("SELECT COUNT(*) c FROM item_list WHERE status=0")->fetch_assoc()['c'];
$stat_published    = $conn->query("SELECT COUNT(*) c FROM item_list WHERE status=1")->fetch_assoc()['c'];
$stat_claimed      = $conn->query("SELECT COUNT(*) c FROM item_list WHERE status=2")->fetch_assoc()['c'];
$stat_unread_msgs  = $conn->query("SELECT COUNT(*) c FROM inquiry_list WHERE status=0")->fetch_assoc()['c'];
$stat_claims       = $conn->query("SELECT COUNT(*) c FROM item_claims WHERE status=0")->fetch_assoc()['c'];
$stat_users        = $conn->query("SELECT COUNT(*) c FROM registered_users WHERE status=1")->fetch_assoc()['c'];

$recent_items  = $conn->query("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id ORDER BY il.created_at DESC LIMIT 8");
$recent_claims = $conn->query("SELECT ic.*, il.title as item_title FROM item_claims ic LEFT JOIN item_list il ON il.id=ic.item_id ORDER BY ic.created_at DESC LIMIT 5");

// Order stats
$stat_orders_pending    = $conn->query("SELECT COUNT(*) c FROM orders WHERE order_status='pending'")->fetch_assoc()['c'];
$stat_orders_processing = $conn->query("SELECT COUNT(*) c FROM orders WHERE order_status='processing'")->fetch_assoc()['c'];
$stat_orders_total      = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$stat_orders_revenue_r  = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE payment_status='paid'");
$stat_orders_revenue    = $stat_orders_revenue_r->fetch_assoc()['s'];
$recent_orders          = $conn->query("SELECT o.*, ru.firstname, ru.lastname FROM orders o LEFT JOIN registered_users ru ON ru.id=o.user_id ORDER BY o.created_at DESC LIMIT 5");
?>

<section class="section dashboard py-3">

  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #f59e0b">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-warning"><?= number_format($stat_pending) ?></div>
            <div class="stat-label">Pending Review</div>
          </div>
          <div class="stat-icon text-warning"><i class="bi bi-hourglass-split"></i></div>
        </div>
        <?php if($stat_pending > 0): ?>
        <a href="<?= base_url ?>admin?page=items" class="btn btn-sm btn-warning rounded-pill mt-2 w-100">Review Now</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #16a34a">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-success"><?= number_format($stat_published) ?></div>
            <div class="stat-label">Published Items</div>
          </div>
          <div class="stat-icon text-success"><i class="bi bi-check2-circle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #7c3aed">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value" style="color:#7c3aed"><?= number_format($stat_claimed) ?></div>
            <div class="stat-label">Claimed / Reunited</div>
          </div>
          <div class="stat-icon" style="color:#7c3aed"><i class="bi bi-trophy"></i></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #0ea5e9">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-info"><?= number_format($stat_total_items) ?></div>
            <div class="stat-label">Total Items</div>
          </div>
          <div class="stat-icon text-info"><i class="bi bi-collection"></i></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #dc2626">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-danger"><?= number_format($stat_unread_msgs) ?></div>
            <div class="stat-label">Unread Messages</div>
          </div>
          <div class="stat-icon text-danger"><i class="bi bi-envelope"></i></div>
        </div>
        <?php if($stat_unread_msgs > 0): ?>
        <a href="<?= base_url ?>admin?page=inquiries" class="btn btn-sm btn-danger rounded-pill mt-2 w-100">View Messages</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #d97706">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-warning"><?= number_format($stat_claims) ?></div>
            <div class="stat-label">Pending Claims</div>
          </div>
          <div class="stat-icon text-warning"><i class="bi bi-patch-question"></i></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid var(--saf-primary)">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-primary"><?= number_format($stat_users) ?></div>
            <div class="stat-label">Registered Users</div>
          </div>
          <div class="stat-icon text-primary"><i class="bi bi-people"></i></div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="admin-stat-card" style="border-left:4px solid #64748b">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="stat-value text-secondary"><?= number_format($stat_active_cats) ?></div>
            <div class="stat-label">Active Categories</div>
          </div>
          <div class="stat-icon text-secondary"><i class="bi bi-tag"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Orders summary strip -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div style="height:3px;background:linear-gradient(90deg,var(--saf-primary),#7c3aed,var(--saf-gold))"></div>
        <div class="card-body py-3 px-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="fw-bold" style="font-family:'Space Grotesk',sans-serif;font-size:.95rem">
              <i class="bi bi-bag-check-fill text-primary me-2"></i>Shop Orders
            </div>
            <div class="d-flex gap-4 flex-wrap">
              <div class="text-center">
                <div class="fw-bold fs-5 text-warning"><?= number_format($stat_orders_pending) ?></div>
                <div class="text-muted" style="font-size:.72rem">Pending</div>
              </div>
              <div class="text-center">
                <div class="fw-bold fs-5 text-primary"><?= number_format($stat_orders_processing) ?></div>
                <div class="text-muted" style="font-size:.72rem">Processing</div>
              </div>
              <div class="text-center">
                <div class="fw-bold fs-5"><?= number_format($stat_orders_total) ?></div>
                <div class="text-muted" style="font-size:.72rem">Total Orders</div>
              </div>
              <div class="text-center">
                <div class="fw-bold fs-5 text-success">GHS <?= number_format($stat_orders_revenue, 2) ?></div>
                <div class="text-muted" style="font-size:.72rem">Paid Revenue</div>
              </div>
            </div>
            <a href="<?= base_url ?>admin?page=orders" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
              Manage Orders <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Recent items -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm" style="border-radius:14px">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4" style="border-radius:14px 14px 0 0">
          <h6 class="fw-bold mb-0">Recent Items</h6>
          <a href="<?= base_url ?>admin?page=items" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light" style="font-size:.8rem">
                <tr>
                  <th class="ps-4">Item</th>
                  <th>Type</th>
                  <th>Reported</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($it=$recent_items->fetch_assoc()): ?>
                <tr>
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-2">
                      <?php if(!empty($it['image_path']) && is_file(base_app.explode('?',$it['image_path'])[0])): ?>
                      <img src="<?= base_url.explode('?',$it['image_path'])[0] ?>" style="width:36px;height:36px;object-fit:cover;border-radius:8px">
                      <?php else: ?>
                      <div style="width:36px;height:36px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-image text-muted" style="font-size:.8rem"></i></div>
                      <?php endif; ?>
                      <div>
                        <div class="fw-semibold" style="font-size:.87rem"><?= htmlspecialchars($it['title']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($it['cat_name']??'') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge rounded-pill <?= $it['item_type']?'badge-found':'badge-lost' ?>"><?= $it['item_type']?'Found':'Lost' ?></span></td>
                  <td style="font-size:.8rem"><?= date('M j, Y', strtotime($it['created_at'])) ?></td>
                  <td>
                    <?php
                    $sc = [0=>'status-pending',1=>'status-published',2=>'status-claimed'];
                    $sl = [0=>'Pending',1=>'Published',2=>'Claimed'];
                    ?>
                    <span class="badge rounded-pill px-2 <?= $sc[$it['status']] ?>"><?= $sl[$it['status']] ?></span>
                  </td>
                  <td class="text-center">
                    <?php if($it['status'] == 0): ?>
                    <button class="btn btn-xs btn-success rounded-pill me-1" onclick="publish_item(<?= $it['id'] ?>)" title="Publish" style="font-size:.75rem;padding:.2rem .6rem">
                      <i class="bi bi-check2"></i> Publish
                    </button>
                    <?php endif; ?>
                    <a href="<?= base_url ?>admin?page=items/view_item&id=<?= $it['id'] ?>" class="btn btn-xs btn-outline-primary rounded-pill" style="font-size:.75rem;padding:.2rem .6rem">View</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent claims + Recent orders -->
    <div class="col-lg-4">
      <!-- Recent Orders -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4" style="border-radius:14px 14px 0 0">
          <h6 class="fw-bold mb-0"><i class="bi bi-bag text-primary me-1"></i>Recent Orders</h6>
          <a href="<?= base_url ?>admin?page=orders" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
        </div>
        <div class="card-body p-0">
          <?php if($recent_orders->num_rows > 0): while($ord=$recent_orders->fetch_assoc()): ?>
          <div class="d-flex gap-3 align-items-center px-4 py-3 border-bottom">
            <div style="flex-shrink:0">
              <?php $os_colors=['pending'=>'status-pending','processing'=>'status-published','shipped'=>'text-bg-info','delivered'=>'text-bg-success','cancelled'=>'text-bg-danger']; ?>
              <span class="badge rounded-pill <?= $os_colors[$ord['order_status']] ?? 'status-pending' ?>" style="font-size:.67rem"><?= ucfirst($ord['order_status']) ?></span>
            </div>
            <div style="min-width:0;flex:1">
              <div class="fw-semibold text-truncate" style="font-size:.83rem"><?= htmlspecialchars($ord['product_name'] ?? '') ?></div>
              <div class="text-muted" style="font-size:.73rem"><?= htmlspecialchars(($ord['firstname']??'').' '.($ord['lastname']??'')) ?></div>
            </div>
            <div class="text-end" style="flex-shrink:0">
              <div class="fw-bold" style="font-size:.82rem;color:var(--saf-primary)">GHS <?= number_format($ord['total'],2) ?></div>
              <div class="text-muted" style="font-size:.7rem"><?= date('M j', strtotime($ord['created_at'])) ?></div>
            </div>
          </div>
          <?php endwhile; else: ?>
          <div class="text-center py-4 text-muted" style="font-size:.85rem"><i class="bi bi-bag d-block mb-1" style="font-size:1.5rem;opacity:.3"></i>No orders yet</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Claims -->
      <div class="card border-0 shadow-sm" style="border-radius:14px">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4" style="border-radius:14px 14px 0 0">
          <h6 class="fw-bold mb-0">Recent Claims</h6>
          <a href="<?= base_url ?>admin?page=claims" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
        </div>
        <div class="card-body p-0">
          <?php if($recent_claims->num_rows > 0): while($cl=$recent_claims->fetch_assoc()): ?>
          <div class="d-flex gap-3 align-items-start px-4 py-3 border-bottom">
            <div style="width:36px;height:36px;min-width:36px;border-radius:50%;background:linear-gradient(135deg,var(--saf-primary),var(--saf-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem">
              <?= strtoupper(substr($cl['fullname'],0,1)) ?>
            </div>
            <div style="min-width:0">
              <div class="fw-semibold text-truncate" style="font-size:.85rem"><?= htmlspecialchars($cl['fullname']) ?></div>
              <div class="text-muted text-truncate" style="font-size:.75rem">re: <?= htmlspecialchars($cl['item_title']??'') ?></div>
              <div class="mt-1">
                <?php $cc=[0=>'status-pending',1=>'status-published',2=>'status-rejected']; $cl_=[0=>'Pending',1=>'Approved',2=>'Rejected']; ?>
                <span class="badge rounded-pill <?= $cc[$cl['status']] ?>" style="font-size:.7rem"><?= $cl_[$cl['status']] ?></span>
              </div>
            </div>
            <div class="ms-auto text-muted" style="font-size:.72rem;white-space:nowrap"><?= date('M j', strtotime($cl['created_at'])) ?></div>
          </div>
          <?php endwhile; else: ?>
          <div class="text-center py-4 text-muted" style="font-size:.85rem">No claims yet</div>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /col-lg-4 -->

  </div>
</section>

<script>
function publish_item(id){
  start_loader();
  $.ajax({
    url: _base_url_+'classes/Master.php?f=update_item_status',
    method: 'POST', dataType: 'json',
    data: {id: id, status: 1},
    success: function(r){
      if(r.status==='success'){ alert_toast(r.msg,'success'); setTimeout(()=>location.reload(),800); }
      else { alert_toast('Error.','error'); end_loader(); }
    }
  });
}
</script>
