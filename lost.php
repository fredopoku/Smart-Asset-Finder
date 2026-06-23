<?php
$prefill_name    = isset($_SESSION['pub_userdata']) ? $_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname'] : '';
$prefill_contact = isset($_SESSION['pub_userdata']) ? $_SESSION['pub_userdata']['phone'] : '';
$cats = $conn->query("SELECT * FROM category_list WHERE status=1 ORDER BY name ASC");

$countries = [
  'Afghanistan','Albania','Algeria','Argentina','Australia','Austria','Bangladesh',
  'Belgium','Brazil','Cambodia','Canada','Chile','China','Colombia','Croatia',
  'Czech Republic','Denmark','Egypt','Ethiopia','Finland','France','Germany','Ghana',
  'Greece','Hungary','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy',
  'Japan','Jordan','Kenya','Malaysia','Mexico','Morocco','Netherlands','New Zealand',
  'Nigeria','Norway','Pakistan','Peru','Philippines','Poland','Portugal','Romania',
  'Russia','Saudi Arabia','Senegal','Singapore','South Africa','South Korea','Spain',
  'Sri Lanka','Sweden','Switzerland','Taiwan','Tanzania','Thailand','Turkey','UAE',
  'Uganda','Ukraine','United Kingdom','United States','Vietnam','Zimbabwe','Other',
];
?>
<div class="container-xl px-3 px-md-4 py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <div class="mb-4">
        <h2 class="fw-bold" style="color:var(--saf-dark)"><i class="bi bi-question-circle-fill text-danger me-2"></i>Report a Lost Item</h2>
        <p class="text-muted">Lost something? Describe it here and we'll alert finders who may have it. The more detail you provide, the better your chances.</p>
      </div>

      <div class="saf-form-card">
        <form id="item-form" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="founder" value="1">
          <input type="hidden" name="item_type" value="0">
          <input type="hidden" name="id" value="">
          <input type="hidden" name="lat" id="f-lat">
          <input type="hidden" name="lng" id="f-lng">

          <div class="row g-3">

            <div class="col-12">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="" disabled selected>Select a category</option>
                <?php while($c=$cats->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-6">
              <label class="form-label">Your Name <span class="text-danger">*</span></label>
              <input type="text" name="fullname" class="form-control" required placeholder="Full name" value="<?= htmlspecialchars($prefill_name) ?>">
            </div>

            <div class="col-6">
              <label class="form-label">Contact Number <span class="text-danger">*</span></label>
              <input type="text" name="contact" class="form-control" required placeholder="Phone number" value="<?= htmlspecialchars($prefill_contact) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Item Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required placeholder="e.g. Black Samsung Galaxy S21, Silver Keys with keychain">
            </div>

            <div class="col-6">
              <label class="form-label">Date Lost</label>
              <input type="date" name="date_lost_found" class="form-control" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-6">
              <label class="form-label">Serial Number / IMEI <span class="text-muted fw-normal">(optional)</span></label>
              <input type="text" name="serial_number" class="form-control" placeholder="e.g. IMEI: 123456789">
            </div>

            <div class="col-12">
              <label class="form-label">Description <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="4" required placeholder="Describe the item — colour, brand, model, any unique markings…"></textarea>
            </div>

            <!-- ── Location + Map ─────────────────────────────────────────── -->
            <div class="col-12">
              <label class="form-label fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Where did you lose it?</label>
              <div class="input-group mb-2">
                <input type="text" name="location" id="f-location" class="form-control" placeholder="Type a place or click the map below">
                <button type="button" class="btn btn-outline-secondary" id="geolocate-btn" title="Use my current location">
                  <i class="bi bi-crosshair"></i>
                </button>
              </div>
              <div id="lost-map" style="height:220px;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden"></div>
              <div class="form-text">Click the map to pin the exact location. This helps nearby finders search around the right area.</div>
            </div>

            <!-- ── Travelling toggle ──────────────────────────────────────── -->
            <div class="col-12">
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f8faff;border:1px solid #e2e8f0">
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" name="is_travelling" id="is_travelling" value="1" style="width:2.2em;height:1.2em;cursor:pointer">
                  <label class="form-check-label fw-semibold ms-2" for="is_travelling">
                    <i class="bi bi-airplane-fill text-primary me-1"></i>I lost this while travelling
                  </label>
                </div>
              </div>
              <div id="travel-fields" style="display:none" class="mt-2">
                <label class="form-label fw-semibold">Country / City where it was lost</label>
                <select name="location_country" class="form-select">
                  <option value="">Select country/city</option>
                  <?php foreach($countries as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text mt-1">Your report will be visible to finders in that country too.</div>
              </div>
            </div>

            <!-- ── Media upload ───────────────────────────────────────────── -->
            <div class="col-12">
              <label class="form-label">Photos &amp; Videos <span class="text-muted fw-normal">(optional — up to 5 files)</span></label>
              <input type="file" id="media-gallery" accept="image/*,video/*" multiple class="d-none">
              <input type="file" id="media-camera" accept="image/*,video/*" capture="environment" class="d-none">
              <div id="drop-zone" class="media-drop-zone" onclick="document.getElementById('media-gallery').click()">
                <i class="bi bi-cloud-arrow-up" style="font-size:2.2rem;color:#94a3b8"></i>
                <p class="mb-1 fw-semibold" style="color:#475569">Drag &amp; drop here, or</p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                  <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="event.stopPropagation();document.getElementById('media-gallery').click()">
                    <i class="bi bi-images me-1"></i>Choose Files
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 d-md-none" onclick="event.stopPropagation();document.getElementById('media-camera').click()">
                    <i class="bi bi-camera me-1"></i>Use Camera
                  </button>
                </div>
                <p class="text-muted mb-0 mt-2" style="font-size:.75rem">Images (JPG, PNG, WebP) &middot; Videos (MP4, MOV) &middot; Max 5 MB/image · 30 MB/video</p>
              </div>
              <div id="media-preview" class="media-preview-grid mt-2" style="display:none"></div>
              <div id="media-count" class="text-muted mt-1" style="font-size:.78rem;display:none"></div>
            </div>

            <!-- ── Security Questions (private — only owner sees these) ───── -->
            <div class="col-12">
              <div class="p-3 rounded-3" style="background:#fff7ed;border:1px solid #fed7aa">
                <div class="d-flex align-items-start gap-2 mb-3">
                  <i class="bi bi-shield-lock-fill text-warning mt-1"></i>
                  <div>
                    <div class="fw-bold" style="font-size:.9rem">Ownership Verification Questions <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem">Private</span></div>
                    <div class="text-muted" style="font-size:.78rem">Set 1–3 questions only YOU would know the answer to. When someone claims this item, they must answer these. <strong>Never shown publicly.</strong></div>
                  </div>
                </div>
                <div id="security-qa-list">
                  <div class="security-qa-row row g-2 mb-2">
                    <div class="col-md-5">
                      <select name="sec_q[]" class="form-select form-select-sm">
                        <option value="">— Pick a question —</option>
                        <option>What colour is the inside lining or case?</option>
                        <option>What stickers, marks, or scratches does it have?</option>
                        <option>What was the last app or thing you used it for?</option>
                        <option>What is the wallpaper or screen saver?</option>
                        <option>Describe something inside the bag or wallet</option>
                        <option>What is the serial number or unique ID?</option>
                        <option>What accessories or cables came with it?</option>
                        <option value="__custom__">Write my own question…</option>
                      </select>
                    </div>
                    <div class="col-md-5 custom-q-wrap" style="display:none">
                      <input type="text" name="sec_q_custom[]" class="form-control form-control-sm" placeholder="Type your question">
                    </div>
                    <div class="col-md-4">
                      <input type="text" name="sec_a[]" class="form-control form-control-sm" placeholder="Your answer (private)">
                    </div>
                    <div class="col-auto d-flex align-items-center">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-qa-row d-none"><i class="bi bi-x"></i></button>
                    </div>
                  </div>
                </div>
                <button type="button" id="add-qa-row" class="btn btn-sm btn-outline-warning rounded-pill mt-1">
                  <i class="bi bi-plus me-1"></i>Add another question
                </button>
              </div>
            </div>

            <div class="col-12">
              <div class="mb-2">
                <label class="form-label fw-semibold">Proof of Ownership Notes <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="ownership_notes" class="form-control" placeholder="e.g. Initials engraved, custom case, purchase receipt details">
                <div class="form-text">Private — only shown to admin to verify ownership.</div>
              </div>
            </div>

            <div class="col-12">
              <div id="form-alert"></div>
              <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold" style="font-size:1rem">
                <i class="bi bi-send me-1"></i> Submit Lost Report
              </button>
              <p class="text-muted text-center mt-2" style="font-size:.8rem">Your report will be reviewed and published shortly.</p>
            </div>
          </div>
        </form>
      </div>

    </div>

    <div class="col-lg-4 d-none d-lg-block">
      <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius:12px;background:#fff5f5">
        <h6 class="fw-bold text-danger"><i class="bi bi-lightbulb me-1"></i>Tips for a better report</h6>
        <ul class="text-muted mb-0" style="font-size:.83rem;padding-left:1.2rem">
          <li>Include serial numbers or unique identifiers</li>
          <li>Pin the exact location on the map</li>
          <li>Note exactly where and when you last had it</li>
          <li>Mention any accessories or case colours</li>
          <li>Upload a photo or video if you have one</li>
          <li>Set security questions — they protect you from false claims</li>
        </ul>
      </div>
      <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius:12px;background:#f0f9ff">
        <h6 class="fw-bold text-primary"><i class="bi bi-airplane me-1"></i>Lost while travelling?</h6>
        <p class="text-muted mb-0" style="font-size:.82rem">Toggle "I lost this while travelling" and select the country where it happened. Your report becomes visible to finders across borders — wherever you lost it in the world.</p>
      </div>
      <div class="card border-0 shadow-sm p-3" style="border-radius:12px">
        <h6 class="fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i>What happens next?</h6>
        <p class="text-muted mb-0" style="font-size:.82rem">After review, your report goes live. Our AI scans all found reports for a match and notifies you instantly. If someone claims your item, they must answer your security questions first.</p>
      </div>
    </div>
  </div>
</div>

<!-- Leaflet (loaded only if not already on page) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
$(function(){
  // ── Map setup ──────────────────────────────────────────────────────────────
  var map = L.map('lost-map').setView([20, 0], 2);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap contributors', maxZoom:19
  }).addTo(map);

  var marker = null;
  function setPin(lat, lng, label){
    if(marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map);
    $('#f-lat').val(lat);
    $('#f-lng').val(lng);
    if(label) $('#f-location').val(label);
  }

  map.on('click', function(e){
    setPin(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  $('#geolocate-btn').on('click', function(){
    if(!navigator.geolocation){ alert('Geolocation not supported'); return; }
    navigator.geolocation.getCurrentPosition(function(pos){
      var lat = pos.coords.latitude, lng = pos.coords.longitude;
      map.setView([lat,lng], 15);
      setPin(lat, lng);
      reverseGeocode(lat, lng);
    }, function(){ alert('Could not get your location. Please click the map instead.'); });
  });

  function reverseGeocode(lat, lng){
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng)
      .then(r=>r.json()).then(function(d){
        if(d && d.display_name){
          var parts = d.display_name.split(',');
          $('#f-location').val(parts.slice(0,3).join(',').trim());
        }
      }).catch(function(){});
  }

  // ── Travel toggle ──────────────────────────────────────────────────────────
  $('#is_travelling').on('change', function(){
    $('#travel-fields').toggle(this.checked);
  });

  // ── Security Q&A ──────────────────────────────────────────────────────────
  var maxRows = 3;

  function bindQaRow($row){
    $row.find('select[name="sec_q[]"]').on('change', function(){
      var $custom = $row.find('.custom-q-wrap');
      if($(this).val() === '__custom__'){
        $custom.show(); $custom.find('input').attr('required', true);
      } else {
        $custom.hide(); $custom.find('input').removeAttr('required');
      }
    });
    $row.find('.remove-qa-row').on('click', function(){
      $row.remove();
      updateRemoveButtons();
    });
  }

  function updateRemoveButtons(){
    var $rows = $('#security-qa-list .security-qa-row');
    $rows.find('.remove-qa-row').toggleClass('d-none', $rows.length <= 1);
    $('#add-qa-row').toggle($rows.length < maxRows);
  }

  bindQaRow($('#security-qa-list .security-qa-row'));

  $('#add-qa-row').on('click', function(){
    var $existing = $('#security-qa-list .security-qa-row:first').clone(false);
    $existing.find('select').val('');
    $existing.find('input[type="text"]').val('');
    $existing.find('.custom-q-wrap').hide();
    $existing.find('.remove-qa-row').removeClass('d-none');
    $('#security-qa-list').append($existing);
    bindQaRow($existing);
    updateRemoveButtons();
  });

  // ── Media upload ───────────────────────────────────────────────────────────
  var allFiles = [];
  function addFiles(fileList){
    for(var i=0;i<fileList.length;i++){
      if(allFiles.length >= 5) break;
      allFiles.push(fileList[i]);
    }
    renderPreviews();
  }
  function renderPreviews(){
    var $grid = $('#media-preview'), $count = $('#media-count');
    if(!allFiles.length){ $grid.hide(); $count.hide(); return; }
    $grid.empty().show();
    $count.text(allFiles.length+' file'+(allFiles.length>1?'s':'')+' selected (max 5)').show();
    allFiles.forEach(function(f,idx){
      var isVideo = f.type.startsWith('video/');
      var $wrap = $('<div class="media-preview-item position-relative"></div>');
      var $rm = $('<button type="button" class="media-remove-btn"><i class="bi bi-x"></i></button>');
      $rm.on('click', function(e){ e.stopPropagation(); allFiles.splice(idx,1); renderPreviews(); });
      if(isVideo){
        $wrap.append('<video src="'+URL.createObjectURL(f)+'" class="media-thumb" muted playsinline></video>');
        $wrap.append('<span class="media-type-tag"><i class="bi bi-play-circle-fill me-1"></i>Video</span>');
      } else {
        var reader = new FileReader();
        reader.onload = (function($w){ return function(e){ $w.prepend('<img src="'+e.target.result+'" class="media-thumb">'); }; })($wrap);
        reader.readAsDataURL(f);
      }
      $wrap.append('<div class="media-file-name">'+f.name.substring(0,22)+(f.name.length>22?'…':'')+'</div>').append($rm);
      $grid.append($wrap);
    });
  }
  $('#media-gallery,#media-camera').on('change', function(){ addFiles(this.files); $(this).val(''); });
  var $dz = $('#drop-zone');
  $dz.on('dragover', function(e){ e.preventDefault(); $(this).addClass('drag-over'); });
  $dz.on('dragleave drop', function(){ $(this).removeClass('drag-over'); });
  $dz.on('drop', function(e){ e.preventDefault(); addFiles(e.originalEvent.dataTransfer.files); });

  // ── Form submit ────────────────────────────────────────────────────────────
  $('#item-form').submit(function(e){
    e.preventDefault();
    $('#form-alert').html('');
    var fd = new FormData(this);
    allFiles.forEach(function(f){ fd.append('media[]', f); });
    start_loader();
    $.ajax({
      url: _base_url_ + 'classes/Master.php?f=save_item',
      data: fd, cache:false, contentType:false, processData:false,
      method:'POST', dataType:'json',
      error: ()=>{ alert_toast('An error occurred. Please try again.','error'); end_loader(); },
      success: function(resp){
        if(resp.status === 'success'){
          alert_toast(resp.msg, 'success');
          setTimeout(()=>{ location.replace(_base_url_+'?page=items'); }, 1500);
        } else {
          $('#form-alert').html('<div class="alert alert-danger">'+resp.msg+'</div>');
          end_loader();
        }
      }
    });
  });
});
</script>
