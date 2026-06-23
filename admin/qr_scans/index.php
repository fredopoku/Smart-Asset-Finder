<?php
$scan_item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$scan_item_title = '';
if($scan_item_id){
    $r = $conn->prepare("SELECT title FROM item_list WHERE id=? LIMIT 1");
    $r->bind_param('i', $scan_item_id);
    $r->execute();
    $scan_item_title = $r->get_result()->fetch_assoc()['title'] ?? '';
    $r->close();
}

// Summary stats
$total_scans = (int)$conn->query("SELECT COUNT(*) c FROM qr_scans")->fetch_assoc()['c'];
$scans_today = (int)$conn->query("SELECT COUNT(*) c FROM qr_scans WHERE DATE(scanned_at)=CURDATE()")->fetch_assoc()['c'];
$items_scanned = (int)$conn->query("SELECT COUNT(DISTINCT item_id) c FROM qr_scans")->fetch_assoc()['c'];
$geo_scans = (int)$conn->query("SELECT COUNT(*) c FROM qr_scans WHERE lat IS NOT NULL")->fetch_assoc()['c'];

// Items with scan counts for the sidebar list
$items_list = $conn->query(
    "SELECT il.id, il.title, il.item_type, COUNT(qs.id) as scan_count, MAX(qs.scanned_at) as last_scan
     FROM item_list il JOIN qr_scans qs ON qs.item_id=il.id
     GROUP BY il.id ORDER BY scan_count DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
#qr-map { height: 460px; border-radius: 12px; z-index: 0; }
.scan-item-row { cursor: pointer; transition: background .15s; }
.scan-item-row:hover, .scan-item-row.active { background: #eff6ff !important; }
.scan-stat-card { border-radius: 12px; border: 0; }
</style>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card scan-stat-card shadow-sm p-3 text-center">
      <div class="fw-bold fs-3 text-primary"><?= number_format($total_scans) ?></div>
      <div class="text-muted small">Total Scans</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card scan-stat-card shadow-sm p-3 text-center">
      <div class="fw-bold fs-3 text-success"><?= number_format($scans_today) ?></div>
      <div class="text-muted small">Scans Today</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card scan-stat-card shadow-sm p-3 text-center">
      <div class="fw-bold fs-3 text-warning"><?= number_format($items_scanned) ?></div>
      <div class="text-muted small">Items Tracked</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card scan-stat-card shadow-sm p-3 text-center">
      <div class="fw-bold fs-3 text-info"><?= number_format($geo_scans) ?></div>
      <div class="text-muted small">With Location</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Item list sidebar -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius:12px;max-height:560px;overflow-y:auto">
      <div class="card-header bg-white fw-bold border-bottom" style="border-radius:12px 12px 0 0">
        <i class="bi bi-qr-code me-2 text-primary"></i>Scanned Items
        <span class="badge bg-secondary ms-2"><?= count($items_list) ?></span>
      </div>
      <?php if(empty($items_list)): ?>
      <div class="p-4 text-center text-muted small">
        <i class="bi bi-qr-code-scan fs-2 d-block mb-2 opacity-25"></i>
        No QR scans recorded yet.
      </div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach($items_list as $il): ?>
        <li class="list-group-item px-3 py-2 scan-item-row <?= $scan_item_id==$il['id']?'active':'' ?>"
            data-id="<?= $il['id'] ?>" data-title="<?= htmlspecialchars($il['title']) ?>">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold" style="font-size:.85rem;line-height:1.2"><?= htmlspecialchars($il['title']) ?></div>
              <div class="text-muted" style="font-size:.72rem">
                <?= $il['item_type'] ? '<span class="text-success">Found</span>' : '<span class="text-danger">Lost</span>' ?>
                &middot; Last: <?= $il['last_scan'] ? date('M j, g:ia', strtotime($il['last_scan'])) : '—' ?>
              </div>
            </div>
            <span class="badge bg-primary rounded-pill ms-2"><?= $il['scan_count'] ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Map + scan log -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
      <div class="card-header bg-white d-flex align-items-center justify-content-between border-bottom" style="border-radius:12px 12px 0 0">
        <span class="fw-bold"><i class="bi bi-geo-alt-fill me-2 text-danger"></i><span id="map-title"><?= $scan_item_title ? htmlspecialchars($scan_item_title) : 'Select an item to view scan map' ?></span></span>
        <span class="text-muted small" id="map-count"></span>
      </div>
      <div class="card-body p-2">
        <div id="qr-map"></div>
        <div id="map-placeholder" class="text-center py-5 text-muted <?= $scan_item_id ? 'd-none' : '' ?>">
          <i class="bi bi-map fs-1 d-block mb-2 opacity-25"></i>
          <p class="small mb-0">Click an item on the left to see where its QR tag was scanned.</p>
        </div>
      </div>
    </div>

    <!-- Scan log table -->
    <div class="card border-0 shadow-sm" style="border-radius:12px">
      <div class="card-header bg-white fw-bold border-bottom" style="border-radius:12px 12px 0 0">
        <i class="bi bi-list-ul me-2"></i>Scan Log <span class="text-muted fw-normal small" id="log-subtitle"></span>
      </div>
      <div class="table-responsive" style="max-height:260px;overflow-y:auto">
        <table class="table table-sm table-hover align-middle mb-0" id="scan-log-table">
          <thead class="table-light sticky-top">
            <tr>
              <th style="font-size:.78rem">Time</th>
              <th style="font-size:.78rem">Location</th>
              <th style="font-size:.78rem">Coordinates</th>
              <th style="font-size:.78rem">IP</th>
            </tr>
          </thead>
          <tbody id="scan-log-body">
            <tr><td colspan="4" class="text-center text-muted py-3 small">Select an item to load scan log.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
var _map = null;
var _markers = [];
var _base = '<?= base_url ?>';

function initMap(){
    if(_map) return;
    _map = L.map('qr-map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(_map);
}

function clearMarkers(){
    _markers.forEach(function(m){ _map.removeLayer(m); });
    _markers = [];
}

function loadScans(itemId, itemTitle){
    $('#map-title').text(itemTitle);
    $('#map-placeholder').addClass('d-none');
    $('#map-count').text('Loading…');
    $('#log-subtitle').text('— ' + itemTitle);
    initMap();
    clearMarkers();

    $.getJSON(_base + 'classes/Master.php?f=get_qr_scans&item_id=' + itemId, function(r){
        if(!r.data || r.data.length === 0){
            $('#map-count').text('No scans yet');
            $('#scan-log-body').html('<tr><td colspan="4" class="text-center text-muted py-3 small">No scans recorded for this item.</td></tr>');
            return;
        }
        var geoScans = r.data.filter(function(s){ return s.lat && s.lng; });
        $('#map-count').text(r.data.length + ' scan' + (r.data.length !== 1 ? 's' : '') + (geoScans.length ? ', ' + geoScans.length + ' with location' : ''));

        var bounds = [];
        geoScans.forEach(function(s){
            var lat = parseFloat(s.lat), lng = parseFloat(s.lng);
            var label = s.location_label || (lat.toFixed(4) + ', ' + lng.toFixed(4));
            var time  = s.scanned_at ? new Date(s.scanned_at).toLocaleString() : '';
            var marker = L.circleMarker([lat, lng], {
                radius: 8, fillColor: '#ef4444', color: '#fff', weight: 2,
                opacity: 1, fillOpacity: 0.85
            }).bindPopup('<strong>' + $('<div>').text(s.item_title || itemTitle).html() + '</strong><br>'
                       + $('<div>').text(label).html() + '<br>'
                       + '<small class="text-muted">' + time + '</small>');
            marker.addTo(_map);
            _markers.push(marker);
            bounds.push([lat, lng]);
        });

        if(bounds.length){ _map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 }); }

        // Build log table
        var html = r.data.map(function(s){
            var time = s.scanned_at ? new Date(s.scanned_at).toLocaleString() : '—';
            var loc  = s.location_label || '—';
            var coords = (s.lat && s.lng) ? parseFloat(s.lat).toFixed(4)+', '+parseFloat(s.lng).toFixed(4) : '—';
            var ip   = s.ip_address || '—';
            return '<tr>'
                + '<td style="font-size:.78rem;white-space:nowrap">'+time+'</td>'
                + '<td style="font-size:.78rem">'+$('<div>').text(loc).html()+'</td>'
                + '<td style="font-size:.78rem;font-family:monospace">'+coords+'</td>'
                + '<td style="font-size:.78rem;font-family:monospace">'+$('<div>').text(ip).html()+'</td>'
                + '</tr>';
        }).join('');
        $('#scan-log-body').html(html);
    });
}

$(function(){
    // Item row click
    $(document).on('click', '.scan-item-row', function(){
        $('.scan-item-row').removeClass('active');
        $(this).addClass('active');
        loadScans($(this).data('id'), $(this).data('title'));
    });

    // Auto-load if item_id in URL
    <?php if($scan_item_id && $scan_item_title): ?>
    loadScans(<?= $scan_item_id ?>, <?= json_encode($scan_item_title) ?>);
    <?php endif; ?>
});
</script>
