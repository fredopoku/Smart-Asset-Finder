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
        <h2 class="fw-bold" style="color:var(--saf-dark)"><i class="bi bi-hand-thumbs-up-fill text-success me-2"></i>Report a Found Item</h2>
        <p class="text-muted">Did you find something that belongs to someone else? Fill in the details and we'll help reunite it with its owner.</p>
      </div>

      <div class="saf-form-card">
        <form id="item-form" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="founder" value="1">
          <input type="hidden" name="item_type" value="1">
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
              <label class="form-label">Date Found</label>
              <input type="date" name="date_lost_found" class="form-control" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-6">
              <label class="form-label">Serial Number / IMEI <span class="text-muted fw-normal">(if visible)</span></label>
              <input type="text" name="serial_number" class="form-control" placeholder="Check back of device">
            </div>

            <!-- ── Location + Map ─────────────────────────────────────────── -->
            <div class="col-12">
              <label class="form-label fw-semibold"><i class="bi bi-geo-alt-fill text-success me-1"></i>Where did you find it?</label>
              <div class="input-group mb-2">
                <input type="text" name="location" id="f-location" class="form-control" placeholder="Type a place or click the map below">
                <button type="button" class="btn btn-outline-secondary" id="geolocate-btn" title="Use my current location">
                  <i class="bi bi-crosshair"></i>
                </button>
              </div>
              <div id="found-map" style="height:220px;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden"></div>
              <div class="form-text">Pin the exact spot — this helps the owner search nearby and helps our AI match faster.</div>
            </div>

            <!-- ── Travelling toggle ──────────────────────────────────────── -->
            <div class="col-12">
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" name="is_travelling" id="is_travelling" value="1" style="width:2.2em;height:1.2em;cursor:pointer">
                  <label class="form-check-label fw-semibold ms-2" for="is_travelling">
                    <i class="bi bi-airplane-fill text-success me-1"></i>I found this while travelling / passing through
                  </label>
                </div>
              </div>
              <div id="travel-fields" style="display:none" class="mt-2">
                <label class="form-label fw-semibold">Country / City where you found it</label>
                <select name="location_country" class="form-select">
                  <option value="">Select country/city</option>
                  <?php foreach($countries as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Description <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="4" required placeholder="Describe the item — colour, brand, distinguishing features…"></textarea>
            </div>


            <!-- ── Media upload ───────────────────────────────────────── -->
            <div class="col-12">
              <label class="form-label">Photos &amp; Videos <span class="text-muted fw-normal">(optional — up to 5 files)</span></label>

              <!-- Hidden file inputs -->
              <input type="file" id="media-gallery" accept="image/*,video/*" multiple class="d-none">
              <input type="file" id="media-camera"  accept="image/*,video/*" capture="environment" class="d-none">

              <!-- Drop zone -->
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

              <!-- Preview grid -->
              <div id="media-preview" class="media-preview-grid mt-2" style="display:none"></div>
              <div id="media-count" class="text-muted mt-1" style="font-size:.78rem;display:none"></div>
            </div>
            <!-- ── /Media upload ──────────────────────────────────────── -->

            <div class="col-12">
              <div id="form-alert"></div>
              <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" style="font-size:1rem">
                <i class="bi bi-send me-1"></i> Submit Report
              </button>
              <p class="text-muted text-center mt-2" style="font-size:.8rem">Your report will be reviewed and published shortly.</p>
            </div>
          </div>
        </form>
      </div>

    </div>

    <!-- Sidebar tip — desktop only -->
    <div class="col-lg-4 d-none d-lg-block">
      <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius:12px;background:#f0fdf4">
        <h6 class="fw-bold text-success"><i class="bi bi-lightbulb me-1"></i>Tips for a good report</h6>
        <ul class="text-muted mb-0" style="font-size:.83rem;padding-left:1.2rem">
          <li>Be as descriptive as possible</li>
          <li>Include brand, colour, and unique markings</li>
          <li>Add a clear photo — it helps a lot</li>
          <li>Share exactly where and when you found it</li>
          <li>Make sure your contact number is correct</li>
        </ul>
      </div>
      <div class="card border-0 shadow-sm p-3" style="border-radius:12px">
        <h6 class="fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i>Safe &amp; Private</h6>
        <p class="text-muted mb-0" style="font-size:.82rem">Your contact is only shared with the verified owner — never shown publicly.</p>
      </div>
    </div>
  </div>
</div>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
$(function(){
  // ── Map ────────────────────────────────────────────────────────────────────
  var map = L.map('found-map').setView([20, 0], 2);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap contributors', maxZoom:19
  }).addTo(map);
  var marker = null;
  function setPin(lat, lng, label){
    if(marker) map.removeLayer(marker);
    marker = L.marker([lat,lng]).addTo(map);
    $('#f-lat').val(lat); $('#f-lng').val(lng);
    if(label) $('#f-location').val(label);
  }
  map.on('click', function(e){ setPin(e.latlng.lat, e.latlng.lng); reverseGeocode(e.latlng.lat, e.latlng.lng); });
  $('#geolocate-btn').on('click', function(){
    if(!navigator.geolocation){ alert('Geolocation not supported'); return; }
    navigator.geolocation.getCurrentPosition(function(pos){
      map.setView([pos.coords.latitude, pos.coords.longitude], 15);
      setPin(pos.coords.latitude, pos.coords.longitude);
      reverseGeocode(pos.coords.latitude, pos.coords.longitude);
    }, function(){ alert('Could not get your location.'); });
  });
  function reverseGeocode(lat, lng){
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng)
      .then(r=>r.json()).then(function(d){
        if(d && d.display_name) $('#f-location').val(d.display_name.split(',').slice(0,3).join(',').trim());
      }).catch(function(){});
  }

  // ── Travel toggle ──────────────────────────────────────────────────────────
  $('#is_travelling').on('change', function(){ $('#travel-fields').toggle(this.checked); });

  var allFiles = []; // master file list

  function addFiles(fileList){
    for(var i=0;i<fileList.length;i++){
      if(allFiles.length >= 5) break;
      allFiles.push(fileList[i]);
    }
    renderPreviews();
  }

  function renderPreviews(){
    var $grid = $('#media-preview');
    var $count = $('#media-count');
    if(allFiles.length === 0){ $grid.hide(); $count.hide(); return; }
    $grid.empty().show();
    $count.text(allFiles.length + ' file' + (allFiles.length>1?'s':'') + ' selected (max 5)').show();
    allFiles.forEach(function(f, idx){
      var isVideo = f.type.startsWith('video/');
      var $wrap = $('<div class="media-preview-item position-relative"></div>');
      var $remove = $('<button type="button" class="media-remove-btn" title="Remove"><i class="bi bi-x"></i></button>');
      $remove.on('click', function(e){ e.stopPropagation(); allFiles.splice(idx,1); renderPreviews(); });
      if(isVideo){
        var url = URL.createObjectURL(f);
        $wrap.append('<video src="'+url+'" class="media-thumb" muted playsinline></video>');
        $wrap.append('<span class="media-type-tag"><i class="bi bi-play-circle-fill me-1"></i>Video</span>');
      } else {
        var reader = new FileReader();
        reader.onload = (function($w){ return function(e){ $w.prepend('<img src="'+e.target.result+'" class="media-thumb">'); }; })($wrap);
        reader.readAsDataURL(f);
      }
      $wrap.append('<div class="media-file-name">'+f.name.substring(0,22)+(f.name.length>22?'…':'')+'</div>');
      $wrap.append($remove);
      $grid.append($wrap);
    });
  }

  // File input events
  $('#media-gallery, #media-camera').on('change', function(){ addFiles(this.files); $(this).val(''); });

  // Drag-and-drop
  var $dz = $('#drop-zone');
  $dz.on('dragover', function(e){ e.preventDefault(); $(this).addClass('drag-over'); });
  $dz.on('dragleave drop', function(){ $(this).removeClass('drag-over'); });
  $dz.on('drop', function(e){ e.preventDefault(); addFiles(e.originalEvent.dataTransfer.files); });

  // Submit
  $('#item-form').submit(function(e){
    e.preventDefault();
    $('#form-alert').html('');
    var fd = new FormData(this);
    allFiles.forEach(function(f){ fd.append('media[]', f); });
    start_loader();
    $.ajax({
      url: _base_url_ + 'classes/Master.php?f=save_item',
      data: fd, cache: false, contentType: false, processData: false,
      method: 'POST', dataType: 'json',
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
