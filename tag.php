<?php
$code = preg_replace('/[^A-Z0-9a-z]/', '', strtoupper($_GET['code'] ?? ''));
if(!$code){
    echo '<script>location.replace("'.base_url.'")</script>'; exit;
}

$stmt = $conn->prepare(
    "SELECT qt.*, ru.firstname, ru.lastname, ru.email
     FROM qr_tags qt JOIN registered_users ru ON ru.id=qt.user_id
     WHERE qt.tag_code=? AND qt.status=1 AND ru.status=1 LIMIT 1"
);
$stmt->bind_param('s', $code);
$stmt->execute();
$tag = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="container px-4 py-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">

<?php if(!$tag): ?>
      <!-- Invalid / deactivated tag -->
      <div class="text-center">
        <div class="mb-4" style="width:80px;height:80px;background:#fef2f2;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center">
          <i class="bi bi-qr-code text-danger" style="font-size:2rem"></i>
        </div>
        <h3 class="fw-bold mb-2">Tag Not Recognised</h3>
        <p class="text-muted mb-4">This QR code is not linked to an active Smart Asset Finder account. It may have been deactivated or printed incorrectly.</p>
        <a href="<?= base_url ?>" class="btn btn-primary rounded-pill px-4">Go to Smart Asset Finder</a>
      </div>

<?php else: ?>
      <!-- Valid tag — found item report form -->
      <div class="text-center mb-4">
        <div class="mb-3" style="width:72px;height:72px;background:linear-gradient(135deg,var(--saf-primary),var(--saf-accent));border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center">
          <i class="bi bi-qr-code-scan text-white" style="font-size:1.8rem"></i>
        </div>
        <h3 class="fw-bold mb-1">You Found Someone's Item!</h3>
        <?php if(!empty($tag['label'])): ?>
        <div class="mb-2">
          <span class="badge rounded-pill px-3 py-2" style="background:#ede9fe;color:#5b21b6;font-size:.9rem">
            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($tag['label']) ?>
          </span>
        </div>
        <?php endif; ?>
        <p class="text-muted" style="font-size:.88rem">
          This item is protected by Smart Asset Finder. Fill in the form below to notify the owner — they'll contact you to arrange the return.
        </p>
      </div>

      <div class="saf-form-card">
        <div id="tag-alert"></div>
        <form id="tag-report-frm" novalidate>
          <input type="hidden" name="tag_code" value="<?= htmlspecialchars($code) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Your Name <span class="text-danger">*</span></label>
              <input type="text" name="finder_name" class="form-control" required placeholder="Your full name">
            </div>
            <div class="col-md-6">
              <label class="form-label">Your Phone / Email <span class="text-danger">*</span></label>
              <input type="text" name="finder_contact" class="form-control" required placeholder="So the owner can reach you">
            </div>
            <div class="col-12">
              <label class="form-label">Where did you find it?</label>
              <input type="text" name="found_location" class="form-control" placeholder="e.g. Table 4 at the café, Gate B3 at the airport...">
            </div>
            <div class="col-12">
              <label class="form-label">Message to Owner</label>
              <textarea name="message" class="form-control" rows="3" placeholder="Describe the item or any other details that will help the owner identify it..."></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold">
                <i class="bi bi-send me-1"></i> Notify the Owner
              </button>
            </div>
          </div>
        </form>
        <div class="text-center mt-3" style="font-size:.78rem;color:#94a3b8">
          <i class="bi bi-shield-check me-1"></i>
          The owner's personal details are kept private. They'll contact you directly to arrange the return.
        </div>
      </div>

      <div class="text-center mt-4">
        <a href="<?= base_url ?>" class="text-muted text-decoration-none" style="font-size:.8rem">
          <i class="bi bi-house me-1"></i>Smart Asset Finder
        </a>
      </div>

<script>
$(function(){
  $('#tag-report-frm').submit(function(e){
    e.preventDefault();
    $('#tag-alert').html('');
    start_loader();
    $.ajax({
      url: _base_url_ + 'classes/Master.php?f=tag_found_report',
      data: new FormData(this), cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
      error: function(){ alert_toast('Error submitting report. Please try again.', 'error'); end_loader(); },
      success: function(r){
        end_loader();
        if(r.status === 'success'){
          $('#tag-report-frm').html(
            '<div class="text-center py-4">'
          + '<div style="width:64px;height:64px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center">'
          + '<i class="bi bi-check-lg text-white" style="font-size:1.8rem"></i></div>'
          + '<h5 class="fw-bold">Message Sent!</h5>'
          + '<p class="text-muted mb-0">' + r.msg + '</p>'
          + '</div>'
          );
        } else {
          $('#tag-alert').html('<div class="alert alert-danger">' + r.msg + '</div>');
        }
      }
    });
  });
});
</script>
<?php endif; ?>

    </div>
  </div>
</div>
