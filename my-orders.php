<?php
if(!isset($_SESSION['pub_userdata'])){
    header('Location: '.base_url.'?page=login'); exit;
}
$uid = (int)$_SESSION['pub_userdata']['id'];

$orders = $conn->query(
    "SELECT * FROM orders WHERE user_id={$uid} ORDER BY created_at DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);

// Payment details for the "pay now" reminder block
$_pay = [
  'mtn_number'          => $_settings->info('pay_mtn_number'),
  'mtn_name'            => $_settings->info('pay_mtn_name'),
  'vodafone_number'     => $_settings->info('pay_vodafone_number'),
  'vodafone_name'       => $_settings->info('pay_vodafone_name'),
  'airteltigo_number'   => $_settings->info('pay_airteltigo_number'),
  'airteltigo_name'     => $_settings->info('pay_airteltigo_name'),
  'bank_account_number' => $_settings->info('pay_bank_account_number'),
  'bank_name'           => $_settings->info('pay_bank_name'),
  'bank_account_name'   => $_settings->info('pay_bank_account_name'),
  'instructions'        => $_settings->info('pay_instructions'),
];

$status_cfg = [
  'pending'    => ['label'=>'Pending',    'cls'=>'bg-warning text-dark',  'icon'=>'bi-clock'],
  'processing' => ['label'=>'Processing', 'cls'=>'bg-info text-dark',     'icon'=>'bi-gear'],
  'shipped'    => ['label'=>'Shipped',    'cls'=>'bg-primary text-white', 'icon'=>'bi-truck'],
  'delivered'  => ['label'=>'Delivered',  'cls'=>'bg-success text-white', 'icon'=>'bi-check-circle-fill'],
  'cancelled'  => ['label'=>'Cancelled',  'cls'=>'bg-danger text-white',  'icon'=>'bi-x-circle'],
];
$pay_cfg = [
  'pending' => ['label'=>'Payment Pending', 'cls'=>'text-warning fw-semibold'],
  'paid'    => ['label'=>'Paid',            'cls'=>'text-success fw-semibold'],
  'failed'  => ['label'=>'Payment Failed',  'cls'=>'text-danger fw-semibold'],
];
?>
<div class="container-xl px-3 px-md-4 py-4">

  <div class="mb-4">
    <h2 class="fw-bold" style="color:var(--saf-dark);font-family:'Space Grotesk',sans-serif">
      <i class="bi bi-bag-check me-2 text-primary"></i>My Orders
    </h2>
    <p class="text-muted mb-0" style="font-size:.9rem">Track your SAF hardware orders and payment status.</p>
  </div>

  <?php if(empty($orders)): ?>
  <div class="text-center py-5">
    <div style="width:72px;height:72px;border-radius:50%;background:rgba(79,70,229,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:2rem;color:#4f46e5">
      <i class="bi bi-bag"></i>
    </div>
    <h5 class="fw-bold mb-1">No orders yet</h5>
    <p class="text-muted mb-3" style="font-size:.88rem">You haven't placed any orders. Get your physical SAF tags from the shop.</p>
    <a href="<?= base_url ?>?page=shop" class="btn btn-primary rounded-pill px-5 fw-semibold">
      <i class="bi bi-bag me-2"></i>Visit the Shop
    </a>
  </div>
  <?php else: ?>

  <div class="row g-3">
    <?php foreach($orders as $o):
      $items  = json_decode($o['items'], true);
      $first  = $items[0] ?? [];
      $sc     = $status_cfg[$o['order_status']] ?? ['label'=>$o['order_status'],'cls'=>'bg-secondary text-white','icon'=>'bi-circle'];
      $pc     = $pay_cfg[$o['payment_status']]  ?? ['label'=>$o['payment_status'],'cls'=>'text-muted'];
      $pending_pay = $o['payment_status'] === 'pending';
    ?>
    <div class="col-12">
      <div class="card border-0 shadow-sm" style="border-radius:16px;<?= $pending_pay ? 'border-left:3px solid #f59e0b!important' : '' ?>">
        <div class="card-body p-4">

          <!-- Order header -->
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
              <div class="fw-bold" style="font-size:.78rem;color:var(--saf-primary);letter-spacing:.04em">ORDER #<?= htmlspecialchars($o['order_ref']) ?></div>
              <div class="fw-semibold" style="font-size:1rem"><?= htmlspecialchars($first['name'] ?? 'Order') ?></div>
              <div class="text-muted" style="font-size:.78rem"><?= date('M j, Y', strtotime($o['created_at'])) ?> &middot; Qty: <?= (int)($first['qty'] ?? 1) ?></div>
            </div>
            <div class="text-end">
              <span class="badge rounded-pill <?= $sc['cls'] ?> mb-1" style="font-size:.72rem"><i class="bi <?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?></span><br>
              <span class="fw-bold" style="font-size:1.1rem">GHS <?= number_format($o['total'],2) ?></span>
            </div>
          </div>

          <!-- Payment status -->
          <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded-3" style="background:#f8fafc;font-size:.82rem">
            <i class="bi bi-credit-card text-muted"></i>
            <span class="<?= $pc['cls'] ?>"><?= $pc['label'] ?></span>
            <span class="text-muted">&middot; <?= ucwords(str_replace('_',' ',$o['payment_method'])) ?></span>
            <span class="text-muted ms-auto">Delivery: GHS <?= number_format($o['delivery_fee'],2) ?></span>
          </div>

          <!-- Delivery address -->
          <div style="font-size:.8rem;color:#64748b"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($o['delivery_address']) ?></div>

          <!-- Pay Now reminder if unpaid -->
          <?php if($pending_pay): ?>
          <div class="mt-3 p-3 rounded-3" style="background:#fefce8;border:1px solid #fde68a">
            <div class="fw-semibold mb-1" style="font-size:.82rem;color:#92400e"><i class="bi bi-exclamation-circle me-1"></i>Payment required to process your order</div>
            <div style="font-size:.78rem;color:#78350f;margin-bottom:10px"><?= htmlspecialchars($_pay['instructions'] ?: 'Send your payment to any of the details below and send your receipt to us.') ?></div>
            <div style="font-size:.78rem;color:#78350f;margin-bottom:10px">Use <strong>#<?= htmlspecialchars($o['order_ref']) ?></strong> as your payment reference.</div>
            <div class="d-flex flex-wrap gap-2">
              <?php if($_pay['mtn_number']): ?>
              <div class="pay-chip d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background:#fff;border:1px solid #e2e8f0">
                <span style="font-size:.75rem">📱 MTN</span>
                <strong style="font-size:.82rem;letter-spacing:.04em"><?= htmlspecialchars($_pay['mtn_number']) ?></strong>
                <button class="btn btn-xs border-0 p-0" style="font-size:.7rem;color:#4f46e5;background:none"
                  onclick="copyNum('<?= htmlspecialchars($_pay['mtn_number']) ?>', this)">Copy</button>
              </div>
              <?php endif; ?>
              <?php if($_pay['vodafone_number']): ?>
              <div class="pay-chip d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background:#fff;border:1px solid #e2e8f0">
                <span style="font-size:.75rem">📱 Voda</span>
                <strong style="font-size:.82rem;letter-spacing:.04em"><?= htmlspecialchars($_pay['vodafone_number']) ?></strong>
                <button class="btn btn-xs border-0 p-0" style="font-size:.7rem;color:#4f46e5;background:none"
                  onclick="copyNum('<?= htmlspecialchars($_pay['vodafone_number']) ?>', this)">Copy</button>
              </div>
              <?php endif; ?>
              <?php if($_pay['airteltigo_number']): ?>
              <div class="pay-chip d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background:#fff;border:1px solid #e2e8f0">
                <span style="font-size:.75rem">📱 AT</span>
                <strong style="font-size:.82rem;letter-spacing:.04em"><?= htmlspecialchars($_pay['airteltigo_number']) ?></strong>
                <button class="btn btn-xs border-0 p-0" style="font-size:.7rem;color:#4f46e5;background:none"
                  onclick="copyNum('<?= htmlspecialchars($_pay['airteltigo_number']) ?>', this)">Copy</button>
              </div>
              <?php endif; ?>
              <?php if($_pay['bank_account_number']): ?>
              <div class="pay-chip d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background:#fff;border:1px solid #e2e8f0">
                <span style="font-size:.75rem">🏦 <?= htmlspecialchars($_pay['bank_name'] ?: 'Bank') ?></span>
                <strong style="font-size:.82rem;letter-spacing:.04em"><?= htmlspecialchars($_pay['bank_account_number']) ?></strong>
                <button class="btn btn-xs border-0 p-0" style="font-size:.7rem;color:#4f46e5;background:none"
                  onclick="copyNum('<?= htmlspecialchars($_pay['bank_account_number']) ?>', this)">Copy</button>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<script>
function copyNum(text, btn){
  navigator.clipboard.writeText(text).then(function(){
    var orig = btn.textContent;
    btn.textContent = 'Copied!';
    btn.style.color = '#10b981';
    setTimeout(function(){ btn.textContent = orig; btn.style.color = '#4f46e5'; }, 1800);
  });
}
</script>
