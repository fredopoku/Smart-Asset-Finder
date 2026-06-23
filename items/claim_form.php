<?php
require_once('../config.php');
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$item_id){ echo '<p class="text-danger">Invalid item.</p>'; exit; }

$stmt = $conn->prepare("SELECT id, title, item_type FROM item_list WHERE id=? AND status=1 LIMIT 1");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$item){ echo '<p class="text-danger">Item not found.</p>'; exit; }

$pf_name  = isset($_SESSION['pub_userdata']) ? $_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname'] : '';
$pf_email = isset($_SESSION['pub_userdata']) ? $_SESSION['pub_userdata']['email'] : '';
$pf_phone = isset($_SESSION['pub_userdata']) ? $_SESSION['pub_userdata']['phone'] : '';
$is_found = (int)$item['item_type'] === 1;

// Fetch security questions for this item (answers NOT sent to frontend)
$sq_stmt = $conn->prepare("SELECT id, question FROM item_security_qa WHERE item_id=? ORDER BY sort_order");
$sq_stmt->bind_param('i', $item_id);
$sq_stmt->execute();
$security_qs = $sq_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sq_stmt->close();
$has_security_qs = !empty($security_qs);
?>
<form id="claim-form" novalidate enctype="multipart/form-data">
  <input type="hidden" name="item_id" value="<?= $item_id ?>">
  <div class="mb-1 text-muted" style="font-size:.82rem">
    <?= $is_found ? 'Claiming ownership of:' : 'Reporting a find for:' ?>
    <strong><?= htmlspecialchars($item['title']) ?></strong>
  </div>
  <hr class="my-2">

  <div class="row g-2">
    <div class="col-12">
      <label class="form-label">Your Full Name <span class="text-danger">*</span></label>
      <input type="text" name="fullname" class="form-control form-control-sm" required value="<?= htmlspecialchars($pf_name) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email <span class="text-danger">*</span></label>
      <input type="email" name="email" class="form-control form-control-sm" required value="<?= htmlspecialchars($pf_email) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone Number <span class="text-danger">*</span></label>
      <input type="text" name="phone" class="form-control form-control-sm" required value="<?= htmlspecialchars($pf_phone) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">
        <?= $is_found ? 'How can you prove this is yours?' : 'Describe how/where you found it' ?>
        <span class="text-danger">*</span>
      </label>
      <textarea name="message" class="form-control form-control-sm" rows="3" required
        placeholder="<?= $is_found ? 'e.g. serial number, purchase receipt, identifying features you know…' : 'e.g. I found it at the library on Monday morning…' ?>"></textarea>
    </div>

    <?php if($is_found && $has_security_qs): ?>
    <!-- ── Security verification questions ──────────────────────────────── -->
    <div class="col-12">
      <div class="p-2 rounded-3" style="background:#fef3c7;border:1px solid #fcd34d">
        <div class="fw-semibold mb-2" style="font-size:.82rem"><i class="bi bi-shield-lock-fill text-warning me-1"></i>Ownership Verification</div>
        <p class="text-muted mb-2" style="font-size:.75rem">The owner set private questions to protect this item. Answer them honestly — our AI will score your answers. Wrong answers will flag your claim.</p>
        <?php foreach($security_qs as $i => $q): ?>
        <div class="mb-2">
          <label class="form-label" style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($q['question']) ?></label>
          <input type="text" name="sec_answer[]" class="form-control form-control-sm"
            placeholder="Your answer" required>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if($is_found): ?>
    <!-- ── Proof of ownership upload ──────────────────────────────────── -->
    <div class="col-12">
      <label class="form-label" style="font-size:.82rem"><i class="bi bi-file-earmark-image me-1 text-primary"></i>Proof of Ownership <span class="text-muted">(optional but recommended)</span></label>
      <input type="file" name="proof_image" class="form-control form-control-sm" accept="image/*,.pdf" id="proof-file">
      <div class="form-text" style="font-size:.72rem">Upload a receipt, photo with the item, or screenshot showing you own it. Speeds up verification.</div>
      <div id="proof-preview" class="mt-1" style="display:none">
        <img id="proof-img" src="" class="rounded" style="max-height:80px;max-width:120px;object-fit:cover;border:1px solid #e2e8f0">
      </div>
    </div>
    <?php endif; ?>

    <div class="col-12">
      <div id="claim-alert"></div>
    </div>
  </div>
</form>

<script>
$('#proof-file').on('change', function(){
  var f = this.files[0];
  if(f && f.type.startsWith('image/')){
    var r = new FileReader();
    r.onload = function(e){ $('#proof-img').attr('src',e.target.result); $('#proof-preview').show(); };
    r.readAsDataURL(f);
  } else {
    $('#proof-preview').hide();
  }
});

$('#uni_modal #submit').off('click').on('click', function(){
  $('#claim-alert').html('');
  var form = $('#claim-form')[0];
  if(!form.checkValidity()){ form.reportValidity(); return; }
  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Master.php?f=save_claim',
    data: new FormData(form),
    cache: false, contentType: false, processData: false,
    method: 'POST', dataType: 'json',
    error: () => { alert_toast('Error submitting claim.','error'); end_loader(); },
    success: function(r){
      if(r.status === 'success'){
        $('#uni_modal').modal('hide');
        alert_toast(r.msg, 'success');
      } else {
        $('#claim-alert').html('<div class="alert alert-danger py-2 mb-0 mt-1">'+r.msg+'</div>');
        end_loader();
      }
    }
  });
});
</script>
