<?php
$status_filter = $_GET['status'] ?? '';
$where = $status_filter ? "WHERE order_status='" . $conn->real_escape_string($status_filter) . "'" : '';

$orders = $conn->query(
    "SELECT * FROM orders {$where} ORDER BY created_at DESC LIMIT 200"
)->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query(
    "SELECT order_status, COUNT(*) c, SUM(total) t FROM orders GROUP BY order_status"
)->fetch_all(MYSQLI_ASSOC);
$stat_map = [];
foreach($stats as $s) $stat_map[$s['order_status']] = $s;

$status_cfg = [
    'pending'    => ['label'=>'Pending',    'cls'=>'bg-warning text-dark'],
    'processing' => ['label'=>'Processing', 'cls'=>'bg-info text-dark'],
    'shipped'    => ['label'=>'Shipped',    'cls'=>'bg-primary text-white'],
    'delivered'  => ['label'=>'Delivered',  'cls'=>'bg-success text-white'],
    'cancelled'  => ['label'=>'Cancelled',  'cls'=>'bg-danger text-white'],
];
$pay_cfg = [
    'pending' => ['label'=>'Unpaid',  'cls'=>'text-warning'],
    'paid'    => ['label'=>'Paid',    'cls'=>'text-success'],
    'failed'  => ['label'=>'Failed',  'cls'=>'text-danger'],
];
?>
<div class="pagetitle mb-4">
  <h1>Shop Orders</h1>
  <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url ?>admin">Home</a></li><li class="breadcrumb-item active">Orders</li></ol></nav>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php
  $stat_cards = [
    ['pending',    'bi-clock-fill',      '#f59e0b', 'Pending'],
    ['processing', 'bi-gear-fill',       '#0ea5e9', 'Processing'],
    ['shipped',    'bi-truck',           '#4f46e5', 'Shipped'],
    ['delivered',  'bi-check-circle-fill','#10b981','Delivered'],
  ];
  foreach($stat_cards as $sc):
    $d = $stat_map[$sc[0]] ?? ['c'=>0,'t'=>0];
  ?>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3" style="border-radius:14px">
      <div class="d-flex align-items-center gap-3">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $sc[2] ?>22;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:<?= $sc[2] ?>">
          <i class="bi <?= $sc[1] ?>"></i>
        </div>
        <div>
          <div class="fw-bold" style="font-size:1.4rem;line-height:1"><?= number_format($d['c']) ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= $sc[3] ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <a href="?page=orders" class="btn btn-sm rounded-pill <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
  <?php foreach($status_cfg as $k => $v): ?>
  <a href="?page=orders&status=<?= $k ?>" class="btn btn-sm rounded-pill <?= $status_filter===$k ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <?= $v['label'] ?> <?php if(isset($stat_map[$k])): ?><span class="badge bg-white text-dark ms-1"><?= $stat_map[$k]['c'] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Orders table -->
<div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Ref</th><th>Customer</th><th>Product</th>
          <th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($orders)): ?>
        <tr><td colspan="8" class="text-center py-5 text-muted">No orders yet.</td></tr>
        <?php else: foreach($orders as $o):
          $items  = json_decode($o['items'], true);
          $first  = $items[0] ?? [];
          $sc     = $status_cfg[$o['order_status']] ?? ['label'=>$o['order_status'],'cls'=>'bg-secondary text-white'];
          $pc     = $pay_cfg[$o['payment_status']]  ?? ['label'=>$o['payment_status'],'cls'=>'text-muted'];
        ?>
        <tr>
          <td><span class="fw-bold" style="font-size:.8rem;color:var(--saf-primary)">#<?= htmlspecialchars($o['order_ref']) ?></span></td>
          <td>
            <div class="fw-semibold" style="font-size:.83rem"><?= htmlspecialchars($o['customer_name']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($o['customer_phone']) ?></div>
          </td>
          <td>
            <div style="font-size:.82rem;font-weight:600"><?= htmlspecialchars($first['name'] ?? '') ?></div>
            <div class="text-muted" style="font-size:.72rem">qty <?= (int)($first['qty'] ?? 1) ?></div>
          </td>
          <td class="fw-bold" style="font-size:.88rem">GHS <?= number_format($o['total'],2) ?></td>
          <td>
            <span class="fw-semibold <?= $pc['cls'] ?>" style="font-size:.78rem"><?= $pc['label'] ?></span>
            <div class="text-muted" style="font-size:.68rem"><?= ucwords(str_replace('_',' ',$o['payment_method'])) ?></div>
          </td>
          <td><span class="badge rounded-pill <?= $sc['cls'] ?>" style="font-size:.72rem"><?= $sc['label'] ?></span></td>
          <td style="font-size:.75rem;color:#94a3b8"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                <li><h6 class="dropdown-header" style="font-size:.7rem">Update Status</h6></li>
                <?php foreach($status_cfg as $k => $v): if($k === $o['order_status']) continue; ?>
                <li><a class="dropdown-item" style="font-size:.82rem" href="#"
                  onclick="updateOrderStatus(<?= $o['id'] ?>, '<?= $k ?>', '<?= $v['label'] ?>'); return false">
                  Mark as <?= $v['label'] ?></a></li>
                <?php endforeach; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" style="font-size:.82rem" href="#"
                  onclick="updatePayStatus(<?= $o['id'] ?>, 'paid'); return false">Mark as Paid</a></li>
                <li>
                  <a class="dropdown-item text-muted" style="font-size:.78rem;word-break:break-word;white-space:normal;max-width:220px"
                    href="#"><?= htmlspecialchars($o['delivery_address']) ?></a>
                </li>
              </ul>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function updateOrderStatus(id, status, label){
  _conf('Mark order as <strong>'+label+'</strong>?', 'doUpdateOrderStatus', [id, '"'+status+'"']);
}
function doUpdateOrderStatus(id, status){
  $.post(_base_url_+'classes/Master.php?f=update_order_status', {id:id, status:status}, function(r){
    if(r.status==='success'){ location.reload(); }
    else { alert_toast(r.msg,'error'); }
  }, 'json');
}
function updatePayStatus(id, status){
  $.post(_base_url_+'classes/Master.php?f=update_order_payment', {id:id, status:status}, function(r){
    if(r.status==='success'){ location.reload(); }
    else { alert_toast(r.msg,'error'); }
  }, 'json');
}
</script>
