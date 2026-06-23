<?php
$pay_fields = ['pay_mtn_number','pay_mtn_name','pay_vodafone_number','pay_vodafone_name',
               'pay_airteltigo_number','pay_airteltigo_name','pay_bank_name',
               'pay_bank_account_name','pay_bank_account_number','pay_bank_branch','pay_instructions'];
$pay = [];
foreach($pay_fields as $f) $pay[$f] = $_settings->info($f);
?>
<div class="pagetitle mb-4">
  <h1>Payment Settings</h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= base_url ?>admin">Home</a></li>
    <li class="breadcrumb-item active">Payment Settings</li>
  </ol></nav>
</div>

<div class="row g-4">
  <!-- MTN MoMo -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span style="width:36px;height:36px;border-radius:10px;background:#f59e0b22;display:flex;align-items:center;justify-content:center;font-size:1.1rem">📱</span>
          <h6 class="mb-0 fw-bold">MTN Mobile Money</h6>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">MTN MoMo Number</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_mtn_number"
            value="<?= htmlspecialchars($pay['pay_mtn_number']) ?>" placeholder="e.g. 0241234567">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold" style="font-size:.82rem">Account Name</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_mtn_name"
            value="<?= htmlspecialchars($pay['pay_mtn_name']) ?>" placeholder="e.g. Smart Asset Finder">
        </div>
      </div>
    </div>
  </div>

  <!-- Vodafone Cash -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span style="width:36px;height:36px;border-radius:10px;background:#ef444422;display:flex;align-items:center;justify-content:center;font-size:1.1rem">📱</span>
          <h6 class="mb-0 fw-bold">Vodafone Cash</h6>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">Vodafone Number</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_vodafone_number"
            value="<?= htmlspecialchars($pay['pay_vodafone_number']) ?>" placeholder="e.g. 0201234567">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold" style="font-size:.82rem">Account Name</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_vodafone_name"
            value="<?= htmlspecialchars($pay['pay_vodafone_name']) ?>" placeholder="e.g. Smart Asset Finder">
        </div>
      </div>
    </div>
  </div>

  <!-- AirtelTigo -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span style="width:36px;height:36px;border-radius:10px;background:#3b82f622;display:flex;align-items:center;justify-content:center;font-size:1.1rem">📱</span>
          <h6 class="mb-0 fw-bold">AirtelTigo Money</h6>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">AirtelTigo Number</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_airteltigo_number"
            value="<?= htmlspecialchars($pay['pay_airteltigo_number']) ?>" placeholder="e.g. 0271234567">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold" style="font-size:.82rem">Account Name</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_airteltigo_name"
            value="<?= htmlspecialchars($pay['pay_airteltigo_name']) ?>" placeholder="e.g. Smart Asset Finder">
        </div>
      </div>
    </div>
  </div>

  <!-- Bank Transfer -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span style="width:36px;height:36px;border-radius:10px;background:#10b98122;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🏦</span>
          <h6 class="mb-0 fw-bold">Bank Transfer</h6>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">Bank Name</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_bank_name"
            value="<?= htmlspecialchars($pay['pay_bank_name']) ?>" placeholder="e.g. GCB Bank">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">Account Name</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_bank_account_name"
            value="<?= htmlspecialchars($pay['pay_bank_account_name']) ?>" placeholder="e.g. Smart Asset Finder Ltd">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem">Account Number</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_bank_account_number"
            value="<?= htmlspecialchars($pay['pay_bank_account_number']) ?>" placeholder="e.g. 1234567890">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold" style="font-size:.82rem">Branch (optional)</label>
          <input type="text" class="form-control form-control-sm rounded-3" id="pay_bank_branch"
            value="<?= htmlspecialchars($pay['pay_bank_branch']) ?>" placeholder="e.g. Accra Main">
        </div>
      </div>
    </div>
  </div>

  <!-- Custom instructions -->
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="border-radius:14px">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3">Payment Instructions shown to customers</h6>
        <textarea class="form-control rounded-3" id="pay_instructions" rows="3"
          style="font-size:.88rem"><?= htmlspecialchars($pay['pay_instructions']) ?></textarea>
        <div class="text-muted mt-1" style="font-size:.75rem">This text appears above your payment details on the order confirmation screen and in emails.</div>
      </div>
    </div>
  </div>

  <!-- Save -->
  <div class="col-12">
    <button class="btn btn-primary rounded-pill px-4" onclick="savePaymentSettings()">
      <i class="bi bi-save me-2"></i>Save Payment Details
    </button>
  </div>
</div>

<script>
function savePaymentSettings(){
  var fields = ['pay_mtn_number','pay_mtn_name','pay_vodafone_number','pay_vodafone_name',
                'pay_airteltigo_number','pay_airteltigo_name','pay_bank_name',
                'pay_bank_account_name','pay_bank_account_number','pay_bank_branch','pay_instructions'];
  var data = {action:'payment'};
  fields.forEach(function(f){ data[f] = document.getElementById(f).value.trim(); });
  $.post(_base_url_+'classes/Master.php?f=save_payment_settings', data, function(r){
    if(r.status==='success'){ alert_toast('Payment details saved.','success'); }
    else { alert_toast(r.msg || 'Save failed.','error'); }
  }, 'json');
}
</script>
