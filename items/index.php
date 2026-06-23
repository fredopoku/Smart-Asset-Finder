<?php
$cid      = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : -1;
$page_num = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$offset   = ($page_num - 1) * $per_page;

$cat      = null;
if($cid){
  $cs = $conn->prepare("SELECT * FROM category_list WHERE id=? AND status=1 LIMIT 1");
  $cs->bind_param('i', $cid);
  $cs->execute();
  $cat = $cs->get_result()->fetch_assoc();
  $cs->close();
}

// Build WHERE
$where = "il.status=1";
$params = [];
$types  = '';
if($cid){ $where .= " AND il.category_id=?"; $params[] = $cid; $types .= 'i'; }
if($type_filter >= 0){ $where .= " AND il.item_type=?"; $params[] = $type_filter; $types .= 'i'; }

// Count
$count_sql = "SELECT COUNT(*) c FROM item_list il WHERE $where";
$cs2 = $conn->prepare($count_sql);
if($types) $cs2->bind_param($types, ...$params);
$cs2->execute();
$total_rows = $cs2->get_result()->fetch_assoc()['c'];
$cs2->close();
$total_pages = ceil($total_rows / $per_page);

// Fetch items
$lp = array_merge($params, [$per_page, $offset]);
$lt = $types.'ii';
$items_stmt = $conn->prepare("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE $where ORDER BY il.created_at DESC LIMIT ? OFFSET ?");
// Also fetch geo-tagged items for map view (all pages, no limit)
$map_stmt = $conn->prepare("SELECT il.id, il.title, il.item_type, il.lat, il.lng, il.location, il.is_travelling, il.location_country, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE $where AND il.lat IS NOT NULL AND il.lng IS NOT NULL ORDER BY il.created_at DESC LIMIT 200");
if($types) $map_stmt->bind_param($types, ...$params);
$map_stmt->execute();
$map_items_raw = $map_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$map_stmt->close();
$map_json = json_encode($map_items_raw, JSON_HEX_TAG);
if($lt) $items_stmt->bind_param($lt, ...$lp);
$items_stmt->execute();
$items = $items_stmt->get_result();
$items_stmt->close();

$categories = $conn->query("SELECT cl.*, COUNT(il.id) as item_count FROM category_list cl LEFT JOIN item_list il ON il.category_id=cl.id AND il.status=1 WHERE cl.status=1 GROUP BY cl.id ORDER BY cl.name");
?>

<div class="container-xl px-4 py-4">

  <!-- Page header -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <div class="section-label"><?= $cat ? 'Category' : 'All Items' ?></div>
      <h2 class="mb-0" style="letter-spacing:-.025em">
        <?= $cat ? htmlspecialchars($cat['name']) : 'Browse Everything' ?>
      </h2>
      <p class="text-muted mb-0 mt-1" style="font-size:.86rem">
        <?= number_format($total_rows) ?> item<?= $total_rows!=1?'s':'' ?> <?= $cat ? 'in this category' : 'listed across all categories' ?>
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= base_url ?>?page=found" class="btn btn-sm rounded-pill fw-semibold px-3" style="background:#d1fae5;color:#065f46;border:none"><i class="bi bi-hand-thumbs-up me-1"></i>Found Something</a>
      <a href="<?= base_url ?>?page=lost" class="btn btn-sm rounded-pill fw-semibold px-3" style="background:#fee2e2;color:#991b1b;border:none"><i class="bi bi-question-circle me-1"></i>Lost Something</a>
    </div>
  </div>

  <!-- Category tabs — own full-width row so all categories are visible -->
  <div class="cat-tabs mb-3">
    <a href="<?= base_url ?>?page=items<?= $type_filter>=0?'&type='.$type_filter:'' ?>" class="cat-tab <?= !$cid?'active':'' ?>">All</a>
    <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
    <a href="<?= base_url ?>?page=items&cid=<?= $c['id'] ?><?= $type_filter>=0?'&type='.$type_filter:'' ?>"
       class="cat-tab <?= $cid==$c['id']?'active':'' ?>">
      <?= htmlspecialchars($c['name']) ?><span style="opacity:.55;margin-left:.3rem">(<?= $c['item_count'] ?>)</span>
    </a>
    <?php endwhile; ?>
  </div>

  <!-- Type filter + view toggle -->
  <div class="d-flex gap-2 mb-4 align-items-center flex-wrap">
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span style="font-size:.75rem;color:#94a3b8;font-weight:600">Filter:</span>
      <a href="<?= base_url ?>?page=items<?= $cid?'&cid='.$cid:'' ?>" class="btn btn-sm <?= $type_filter<0?'btn-primary':'btn-outline-secondary' ?> rounded-pill">All</a>
      <a href="<?= base_url ?>?page=items<?= $cid?'&cid='.$cid:'' ?>&type=1" class="btn btn-sm rounded-pill fw-semibold <?= $type_filter===1?'':'btn-outline-success' ?>" style="<?= $type_filter===1?'background:#d1fae5;color:#065f46;border:none':'' ?>">Found</a>
      <a href="<?= base_url ?>?page=items<?= $cid?'&cid='.$cid:'' ?>&type=0" class="btn btn-sm rounded-pill fw-semibold <?= $type_filter===0?'':'btn-outline-danger' ?>" style="<?= $type_filter===0?'background:#fee2e2;color:#991b1b;border:none':'' ?>">Lost</a>
    </div>
    <div class="ms-auto d-flex gap-2 flex-shrink-0">
      <button id="view-grid-btn" class="btn btn-sm btn-primary rounded-pill" title="Grid view"><i class="bi bi-grid-3x3-gap"></i></button>
      <button id="view-map-btn" class="btn btn-sm btn-outline-secondary rounded-pill" title="Map view"><i class="bi bi-map"></i></button>
    </div>
  </div>

  <!-- Map view -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <div id="items-map-wrap" style="display:none;border-radius:16px;overflow:hidden;margin-bottom:1.5rem;border:1px solid #e2e8f0">
    <div id="items-map" style="height:480px"></div>
  </div>

  <!-- Items grid -->
  <div class="row g-3 mb-4 item-grid">
    <?php if($items->num_rows > 0): while($item = $items->fetch_assoc()):
      $raw_path    = !empty($item['image_path']) ? explode('?',$item['image_path'])[0] : '';
      $has_media   = $raw_path && is_file(base_app.$raw_path);
      $is_vid_card = $has_media && preg_match('/\.(mp4|webm|mov|avi)$/i', $raw_path);
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6">
      <a href="<?= base_url ?>?page=items/view&id=<?= $item['id'] ?>" class="text-decoration-none">
        <div class="item-card card">
          <div class="card-img-wrap">
            <?php if($has_media && !$is_vid_card): ?>
              <img src="<?= base_url.$raw_path ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
            <?php elseif($has_media && $is_vid_card): ?>
              <video src="<?= base_url.$raw_path ?>" style="width:100%;height:100%;object-fit:cover" muted></video>
              <div class="video-overlay"><i class="bi bi-play-circle-fill"></i></div>
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center h-100" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0)">
                <i class="bi bi-image" style="font-size:2.5rem;color:#cbd5e1"></i>
              </div>
            <?php endif; ?>
            <span class="item-type-badge <?= $item['item_type'] ? 'badge-found' : 'badge-lost' ?>"><?= $item['item_type'] ? 'Found' : 'Lost' ?></span>
          </div>
          <div class="card-body">
            <div class="card-cat"><?= htmlspecialchars($item['cat_name'] ?? '') ?></div>
            <div class="card-title txt-clamp-2"><?= htmlspecialchars($item['title']) ?></div>
            <?php if(!empty($item['location'])): ?>
            <div class="mt-1 mb-1 d-flex align-items-center gap-1" style="font-size:.74rem;color:var(--saf-muted)"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($item['location']) ?></div>
            <?php endif; ?>
            <div class="card-desc txt-clamp-2"><?= htmlspecialchars(strip_tags($item['description'])) ?></div>
          </div>
          <div class="card-footer">
            <span class="card-date"><i class="bi bi-clock"></i><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
            <span class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.74rem;padding:.22rem .8rem">View</span>
          </div>
        </div>
      </a>
    </div>
    <?php endwhile; else: ?>
    <div class="col-12 text-center py-5">
      <div style="width:80px;height:80px;background:linear-gradient(135deg,rgba(79,70,229,.08),rgba(139,92,246,.08));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
        <i class="bi bi-inbox" style="font-size:2rem;color:var(--saf-primary);opacity:.5"></i>
      </div>
      <h5 class="fw-bold mb-2" style="font-family:'Space Grotesk',sans-serif">No items found</h5>
      <p class="text-muted mb-4">Try a different category, or be the first to report one.</p>
      <a href="<?= base_url ?>?page=found" class="btn btn-gradient rounded-pill px-4">Report a Found Item</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if($total_pages > 1): ?>
  <nav class="d-flex justify-content-center">
    <ul class="pagination">
      <?php if($page_num > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=items<?= $cid?'&cid='.$cid:'' ?><?= $type_filter>=0?'&type='.$type_filter:'' ?>&p=<?= $page_num-1 ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for($i=max(1,$page_num-2);$i<=min($total_pages,$page_num+2);$i++): ?>
      <li class="page-item <?= $i==$page_num?'active':'' ?>">
        <a class="page-link" href="?page=items<?= $cid?'&cid='.$cid:'' ?><?= $type_filter>=0?'&type='.$type_filter:'' ?>&p=<?= $i ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
      <?php if($page_num < $total_pages): ?>
      <li class="page-item"><a class="page-link" href="?page=items<?= $cid?'&cid='.$cid:'' ?><?= $type_filter>=0?'&type='.$type_filter:'' ?>&p=<?= $page_num+1 ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
  var mapData = <?= $map_json ?>;
  var mapInit = false;
  var leafMap;

  function initMap(){
    if(mapInit) return;
    mapInit = true;
    leafMap = L.map('items-map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      attribution:'© OpenStreetMap contributors', maxZoom:19
    }).addTo(leafMap);

    var bounds = [];
    mapData.forEach(function(item){
      var lat = parseFloat(item.lat), lng = parseFloat(item.lng);
      if(isNaN(lat)||isNaN(lng)) return;

      var color = item.item_type == 1 ? '#10b981' : '#ef4444';
      var icon = L.divIcon({
        className:'',
        html:'<div style="width:28px;height:28px;border-radius:50%;background:'+color+';border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center"><i class="bi bi-'+(item.item_type==1?'check':'question')+'-circle-fill" style="color:#fff;font-size:.65rem"></i></div>',
        iconSize:[28,28], iconAnchor:[14,14], popupAnchor:[0,-16]
      });

      var travel = item.is_travelling == 1 ? '<span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;font-size:.7rem;font-weight:600"><i class="bi bi-airplane-fill me-1"></i>Travelling</span>' : '';
      var loc = item.location_country ? item.location_country : (item.location || '');

      var popup = '<div style="font-family:Inter,sans-serif;min-width:180px">'
        + '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:'+(item.item_type==1?'#059669':'#dc2626')+';margin-bottom:4px">'+(item.item_type==1?'Found':'Lost')+' · '+htmlEsc(item.cat_name||'')+'</div>'
        + '<div style="font-weight:600;font-size:.88rem;color:#0f172a;margin-bottom:4px">'+htmlEsc(item.title)+'</div>'
        + (loc ? '<div style="font-size:.73rem;color:#64748b"><i class="bi bi-geo-alt me-1"></i>'+htmlEsc(loc)+'</div>' : '')
        + (travel ? '<div class="mt-1">'+travel+'</div>' : '')
        + '<a href="<?= base_url ?>?page=items/view&id='+item.id+'" style="display:block;margin-top:8px;background:#4f46e5;color:#fff;text-align:center;padding:5px;border-radius:6px;font-size:.78rem;font-weight:600;text-decoration:none">View Item</a>'
        + '</div>';

      L.marker([lat,lng],{icon:icon}).addTo(leafMap).bindPopup(popup);
      bounds.push([lat,lng]);
    });

    if(bounds.length > 1) leafMap.fitBounds(bounds, {padding:[30,30]});
    else if(bounds.length === 1) leafMap.setView(bounds[0], 14);
  }

  function htmlEsc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

  document.getElementById('view-map-btn').addEventListener('click', function(){
    document.getElementById('items-map-wrap').style.display = '';
    document.querySelector('.item-grid').style.display = 'none';
    document.querySelector('.pagination')?.parentElement && (document.querySelector('nav.d-flex').style.display='none');
    this.classList.replace('btn-outline-secondary','btn-primary');
    document.getElementById('view-grid-btn').classList.replace('btn-primary','btn-outline-secondary');
    setTimeout(function(){ initMap(); leafMap && leafMap.invalidateSize(); }, 80);
  });

  document.getElementById('view-grid-btn').addEventListener('click', function(){
    document.getElementById('items-map-wrap').style.display = 'none';
    document.querySelector('.item-grid').style.display = '';
    document.querySelector('nav.d-flex') && (document.querySelector('nav.d-flex').style.display='');
    this.classList.replace('btn-outline-secondary','btn-primary');
    document.getElementById('view-map-btn').classList.replace('btn-primary','btn-outline-secondary');
  });
})();
</script>
