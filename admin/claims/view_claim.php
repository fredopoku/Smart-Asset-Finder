<?php require_once('../../config.php');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT ic.*, il.title as item_title, il.item_type, il.description as item_desc FROM item_claims ic LEFT JOIN item_list il ON il.id=ic.item_id WHERE ic.id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$cl = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$cl){ echo '<p class="text-danger p-3">Claim not found.</p>'; exit; }
$sc=[0=>'status-pending',1=>'status-published',2=>'status-rejected'];
$sl=[0=>'Pending',1=>'Approved',2=>'Rejected'];
?>
<div class="p-1">
  <div class="row g-3">
    <div class="col-md-6">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Item</h6>
      <p class="mb-0 fw-semibold"><?= htmlspecialchars($cl['item_title']??'') ?></p>
      <p class="text-muted mb-0" style="font-size:.8rem"><?= htmlspecialchars(strip_tags($cl['item_desc']??'')) ?></p>
    </div>
    <div class="col-md-6">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Current Status</h6>
      <span class="badge rounded-pill <?= $sc[$cl['status']] ?>"><?= $sl[$cl['status']] ?></span>
    </div>
    <div class="col-md-6">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Claimant</h6>
      <p class="mb-0 fw-semibold"><?= htmlspecialchars($cl['fullname']) ?></p>
      <p class="text-muted mb-0" style="font-size:.8rem">
        <a href="mailto:<?= htmlspecialchars($cl['email']) ?>"><?= htmlspecialchars($cl['email']) ?></a> &bull;
        <a href="tel:<?= htmlspecialchars($cl['phone']) ?>"><?= htmlspecialchars($cl['phone']) ?></a>
      </p>
    </div>
    <div class="col-md-6">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Submitted</h6>
      <p class="mb-0"><?= date('F j, Y g:i A', strtotime($cl['created_at'])) ?></p>
    </div>
    <!-- AI verification score -->
    <?php if($cl['security_score'] !== null): ?>
    <div class="col-md-6">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">AI Ownership Score</h6>
      <?php
        $score = (float)$cl['security_score'];
        $sc_color = $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#ef4444');
        $sc_label = $score >= 70 ? 'Strong match' : ($score >= 40 ? 'Partial match' : 'Low confidence');
      ?>
      <div style="display:flex;align-items:center;gap:.75rem">
        <div style="font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:800;color:<?= $sc_color ?>"><?= number_format($score, 1) ?>%</div>
        <span class="badge rounded-pill" style="background:<?= $sc_color ?>22;color:<?= $sc_color ?>;font-size:.72rem"><?= $sc_label ?></span>
      </div>
      <div style="height:6px;background:#f1f5f9;border-radius:3px;margin-top:.4rem;overflow:hidden">
        <div style="height:100%;width:<?= min(100,$score) ?>%;background:<?= $sc_color ?>;border-radius:3px;transition:width .6s"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Verification status -->
    <div class="col-md-<?= $cl['security_score'] !== null ? '6' : '6' ?>">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Verification Status</h6>
      <?php
        $vs = $cl['verification_status'] ?? 'pending';
        $vs_map = ['pending'=>['bg-warning-subtle text-warning','Pending review'], 'verified'=>['bg-success-subtle text-success','AI Verified'], 'flagged'=>['bg-danger-subtle text-danger','Flagged — review carefully']];
        [$vs_cls, $vs_lbl] = $vs_map[$vs] ?? $vs_map['pending'];
      ?>
      <span class="badge rounded-pill <?= $vs_cls ?> px-3 py-2"><?= $vs_lbl ?></span>
    </div>

    <div class="col-12">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Claimant's Message / Evidence</h6>
      <div class="bg-light rounded p-3" style="font-size:.88rem;line-height:1.7"><?= nl2br(htmlspecialchars($cl['message'])) ?></div>
    </div>

    <!-- Proof image -->
    <?php if(!empty($cl['proof_image'])): ?>
    <div class="col-12">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Proof of Ownership</h6>
      <?php
        $ext = strtolower(pathinfo($cl['proof_image'], PATHINFO_EXTENSION));
        if($ext === 'pdf'):
      ?>
      <a href="<?= base_url.$cl['proof_image'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
        <i class="bi bi-file-earmark-pdf me-1"></i>View PDF proof
      </a>
      <?php else: ?>
      <a href="<?= base_url.$cl['proof_image'] ?>" target="_blank">
        <img src="<?= base_url.$cl['proof_image'] ?>" class="rounded-3 border" style="max-height:180px;max-width:100%;object-fit:contain;background:#f8faff;padding:4px">
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="col-12">
      <h6 class="fw-bold text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Admin Note (optional)</h6>
      <textarea id="admin-note" class="form-control form-control-sm" rows="2" placeholder="Reason for approval or rejection…"><?= htmlspecialchars($cl['admin_note']??'') ?></textarea>
    </div>
  </div>
</div>
<script>
$('#uni_modal #submit').off('click').on('click', function(){});
$('#uni_modal .modal-footer').html(`
  <button class="btn btn-success rounded-pill" onclick="action_claim(<?=$id?>,1)"><i class="bi bi-check2-circle me-1"></i>Approve</button>
  <button class="btn btn-danger rounded-pill" onclick="action_claim(<?=$id?>,2)"><i class="bi bi-x-circle me-1"></i>Reject</button>
  <button class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
`);
function action_claim(id, status){
  start_loader();
  $.ajax({
    url: _base_url_+'classes/Master.php?f=update_claim_status',
    method:'POST', dataType:'json',
    data:{id:id, status:status, admin_note:$('#admin-note').val()},
    success:function(r){
      if(r.status==='success'){
        $('#uni_modal').modal('hide');
        alert_toast(r.msg,'success');
        setTimeout(()=>location.reload(),800);
      } else { alert_toast('Error.','error'); end_loader(); }
    }
  });
}
</script>
