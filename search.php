<?php
$q           = trim($_GET['q'] ?? '');
$cid         = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : -1;
$categories  = $conn->query("SELECT * FROM category_list WHERE status=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Preload initial results (also used for AJAX endpoint)
$results = []; $total = 0;
if(!empty($q) || $cid){
  $like   = '%'.$q.'%';
  $params = [$like,$like,$like]; $types = 'sss';
  $where  = "(il.title LIKE ? OR il.description LIKE ? OR il.fullname LIKE ?)";
  if($cid)             { $where .= " AND il.category_id=?"; $params[] = $cid;         $types .= 'i'; }
  if($type_filter >= 0){ $where .= " AND il.item_type=?";   $params[] = $type_filter; $types .= 'i'; }

  $cnt = $conn->prepare("SELECT COUNT(*) c FROM item_list il WHERE il.status=1 AND $where");
  $cnt->bind_param($types,...$params); $cnt->execute();
  $total = $cnt->get_result()->fetch_assoc()['c']; $cnt->close();

  $stmt = $conn->prepare("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE il.status=1 AND $where ORDER BY il.created_at DESC LIMIT 48");
  $stmt->bind_param($types,...$params); $stmt->execute();
  $res = $stmt->get_result();
  while($r = $res->fetch_assoc()) $results[] = $r;
  $stmt->close();
}
?>

<style>
/* ── Search page ── */
.search-hero {
  background: var(--saf-dark);
  position: relative; overflow: hidden;
  padding: 3rem 0 2rem;
}
.search-hero::before {
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 50% -10%, rgba(79,70,229,.6) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 90% 90%, rgba(139,92,246,.25) 0%, transparent 55%);
}
.search-hero::after {
  content:'';position:absolute;inset:0;
  background-image:radial-gradient(circle,rgba(255,255,255,.05) 1px,transparent 1px);
  background-size:28px 28px;
}
.search-hero-content { position:relative;z-index:1; }

.search-bar-wrap {
  background: rgba(255,255,255,.08);
  border: 1.5px solid rgba(255,255,255,.18);
  backdrop-filter: blur(24px);
  border-radius: 60px;
  padding: .4rem .4rem .4rem 1.4rem;
  display: flex; align-items: center; gap: .75rem;
  transition: border-color .2s, box-shadow .2s;
}
.search-bar-wrap:focus-within {
  border-color: rgba(129,140,248,.6);
  box-shadow: 0 0 0 3px rgba(79,70,229,.2);
}
.search-bar-wrap input {
  flex: 1; background: none; border: none; outline: none;
  color: #fff; font-size: 1rem; font-family: 'Inter',sans-serif;
}
.search-bar-wrap input::placeholder { color: rgba(255,255,255,.38); }
.search-bar-wrap button {
  background: linear-gradient(135deg,var(--saf-primary),#7c3aed);
  border: none; color: #fff; border-radius: 50px;
  padding: .55rem 1.5rem; font-size: .88rem; font-weight: 700;
  cursor: pointer; transition: transform .2s, box-shadow .2s;
  white-space: nowrap;
}
.search-bar-wrap button:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(79,70,229,.4); }

/* Filter chips */
.filter-row { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
.filter-chip {
  display:inline-flex; align-items:center; gap:.35rem;
  padding:.35rem .9rem; border-radius:50px; font-size:.79rem; font-weight:600;
  cursor:pointer; transition:all .18s ease; user-select:none;
  border:1.5px solid var(--saf-border); background:#fff; color:var(--saf-muted);
}
.filter-chip:hover { border-color:var(--saf-primary); color:var(--saf-primary); }
.filter-chip.active {
  background:var(--saf-primary); border-color:var(--saf-primary);
  color:#fff; box-shadow:0 2px 12px rgba(79,70,229,.3);
}
.filter-chip.active-found { background:#059669; border-color:#059669; color:#fff; }
.filter-chip.active-lost  { background:#dc2626; border-color:#dc2626; color:#fff; }

/* Results meta bar */
.results-meta {
  display:flex; align-items:center; justify-content:space-between;
  font-size:.83rem; color:var(--saf-muted); flex-wrap:wrap; gap:.5rem;
}

/* Skeleton */
.skel-card { border-radius:16px; overflow:hidden; background:#fff; box-shadow:var(--saf-shadow-sm); }
.skel { background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size:200% 100%; animation:skelShimmer 1.4s infinite; }
@keyframes skelShimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* Item card hover lift */
.item-card:hover { transform:translateY(-4px); box-shadow:var(--saf-shadow-md)!important; }
.item-card { transition:transform .22s ease, box-shadow .22s ease; }

/* Stagger reveal */
.result-item { opacity:0; transform:translateY(20px); }
.result-item.in { opacity:1; transform:translateY(0); transition:opacity .4s ease, transform .4s ease; }
</style>

<!-- ── Search hero ── -->
<div class="search-hero">
  <div class="container-xl px-4 search-hero-content">
    <p class="text-center mb-2" style="color:rgba(255,255,255,.5);font-size:.8rem;letter-spacing:.08em;text-transform:uppercase">Smart Asset Finder</p>
    <h2 class="text-white text-center fw-bold mb-4" style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.4rem,3vw,2rem);letter-spacing:-.03em">
      Search Lost &amp; Found Items
    </h2>
    <div class="search-bar-wrap mx-auto" style="max-width:640px" id="main-search-wrap">
      <i class="bi bi-search" style="color:rgba(255,255,255,.45);font-size:.95rem;flex-shrink:0"></i>
      <input type="text" id="live-search-input" placeholder="Search by name, description, location…"
        value="<?= htmlspecialchars($q) ?>" autocomplete="off" autofocus>
      <button onclick="doSearch()"><i class="bi bi-search me-1"></i>Search</button>
    </div>
  </div>
</div>

<!-- ── Results area ── -->
<div class="container-xl px-4 py-4">

  <!-- Filter row -->
  <div class="filter-row mb-4" id="filter-row">
    <!-- Type filters -->
    <span class="filter-chip <?= $type_filter < 0 ? 'active' : '' ?>" data-type="-1" onclick="setFilter('type','-1',this)">
      <i class="bi bi-grid"></i> All Types
    </span>
    <span class="filter-chip <?= $type_filter === 1 ? 'active-found' : '' ?>" data-type="1" onclick="setFilter('type','1',this)" style="border-color:<?= $type_filter===1?'#059669':'var(--saf-border)' ?>">
      <i class="bi bi-hand-thumbs-up"></i> Found
    </span>
    <span class="filter-chip <?= $type_filter === 0 ? 'active-lost' : '' ?>" data-type="0" onclick="setFilter('type','0',this)" style="border-color:<?= $type_filter===0?'#dc2626':'var(--saf-border)' ?>">
      <i class="bi bi-question-circle"></i> Lost
    </span>

    <div class="vr mx-1 d-none d-md-block" style="opacity:.2"></div>

    <!-- Category filters -->
    <span class="filter-chip <?= !$cid ? 'active' : '' ?>" data-cid="0" onclick="setFilter('cid','0',this)">All Categories</span>
    <?php foreach($categories as $c): ?>
    <span class="filter-chip <?= $cid==$c['id'] ? 'active' : '' ?>" data-cid="<?= $c['id'] ?>" onclick="setFilter('cid','<?= $c['id'] ?>',this)"><?= htmlspecialchars($c['name']) ?></span>
    <?php endforeach; ?>
  </div>

  <!-- Results meta -->
  <div class="results-meta mb-3" id="results-meta">
    <?php if(!empty($q) || $cid): ?>
    <span id="results-count">
      <?= $total > 0
        ? '<strong>'.number_format($total).'</strong> result'.($total!=1?'s':'').' found'.(!empty($q)?' for <strong>"'.htmlspecialchars($q).'"</strong>':'')
        : 'No results'.(!empty($q)?' for <strong>"'.htmlspecialchars($q).'"</strong>':'') ?>
    </span>
    <?php else: ?>
    <span id="results-count"></span>
    <?php endif; ?>
    <span id="search-status" class="text-muted" style="font-size:.78rem"></span>
  </div>

  <!-- Results grid -->
  <div class="row g-3" id="results-grid">
    <?php if(count($results)): foreach($results as $i => $item): ?>
    <?= renderItemCard($item, $i) ?>
    <?php endforeach; elseif(!empty($q) || $cid): ?>
    <?= emptyState($q) ?>
    <?php else: ?>
    <?= defaultState() ?>
    <?php endif; ?>
  </div>

</div>

<?php
function renderItemCard($item, $idx = 0){
  global $conn;
  $src  = !empty($item['image_path']) && is_file(base_app.explode('?',$item['image_path'])[0])
          ? base_url.explode('?',$item['image_path'])[0] : '';
  return '<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 result-item" style="transition-delay:'.($idx%8*50).'ms">
    <a href="'.base_url.'?page=items/view&id='.$item['id'].'" class="text-decoration-none">
      <div class="item-card card h-100" style="border-radius:16px">
        <div class="card-img-wrap" style="border-radius:16px 16px 0 0">
          '.($src ? '<img src="'.$src.'" alt="'.htmlspecialchars($item['title']).'" loading="lazy">'
               : '<div class="d-flex align-items-center justify-content:center h-100 bg-light text-muted" style="justify-content:center"><i class="bi bi-image" style="font-size:2.5rem;opacity:.18"></i></div>').'
          <span class="item-type-badge '.($item['item_type']?'badge-found':'badge-lost').'">'.($item['item_type']?'Found':'Lost').'</span>
        </div>
        <div class="card-body">
          <div class="card-cat">'.htmlspecialchars($item['cat_name']??'').'</div>
          <div class="card-title txt-clamp-2">'.htmlspecialchars($item['title']).'</div>
          '.(!empty($item['location']) ? '<div class="text-muted mb-1" style="font-size:.73rem"><i class="bi bi-geo-alt me-1"></i>'.htmlspecialchars($item['location']).'</div>' : '').'
          <div class="card-desc txt-clamp-2">'.htmlspecialchars(strip_tags($item['description'])).'</div>
        </div>
        <div class="card-footer">
          <span class="card-date"><i class="bi bi-clock me-1"></i>'.date('M j, Y',strtotime($item['created_at'])).'</span>
          <span class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;padding:.2rem .65rem">View</span>
        </div>
      </div>
    </a>
  </div>';
}

function emptyState($q){
  return '<div class="col-12 text-center py-5">
    <div style="width:88px;height:88px;border-radius:50%;background:rgba(79,70,229,.06);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2.5rem;color:var(--saf-primary)">
      <i class="bi bi-search"></i></div>
    <h5 class="fw-bold mb-2">Nothing matching <em>"'.htmlspecialchars($q).'"</em></h5>
    <p class="text-muted mb-4" style="max-width:380px;margin:0 auto .75rem">Try different keywords, a shorter phrase, or browse by category.</p>
    <a href="'.base_url.'?page=lost" class="btn btn-danger rounded-pill me-2 fw-semibold"><i class="bi bi-broadcast me-1"></i>Report Lost</a>
    <a href="'.base_url.'?page=items" class="btn btn-outline-primary rounded-pill fw-semibold"><i class="bi bi-grid me-1"></i>Browse All</a>
  </div>';
}
function defaultState(){
  return '<div class="col-12 text-center py-5">
    <div style="width:88px;height:88px;border-radius:50%;background:rgba(79,70,229,.06);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2.5rem;color:var(--saf-primary)">
      <i class="bi bi-search"></i></div>
    <h5 class="fw-bold mb-1">What are you looking for?</h5>
    <p class="text-muted">Search by name, description, location or browse by category above.</p>
  </div>';
}
?>

<script>
var _searchQ    = <?= json_encode($q) ?>;
var _searchCid  = <?= json_encode($cid) ?>;
var _searchType = <?= json_encode($type_filter) ?>;
var _searchTimer= null;

document.getElementById('live-search-input').addEventListener('input', function(){
  clearTimeout(_searchTimer);
  var val = this.value.trim();
  document.getElementById('search-status').textContent = val.length > 1 ? 'Searching…' : '';
  if(val.length === 0 && !_searchCid){ clearResults(); return; }
  if(val.length < 2 && val.length > 0) return;
  _searchTimer = setTimeout(function(){ _searchQ = val; fetchResults(); }, 320);
});

document.getElementById('live-search-input').addEventListener('keydown', function(e){
  if(e.key === 'Enter'){ clearTimeout(_searchTimer); _searchQ = this.value.trim(); fetchResults(); }
});

function doSearch(){
  _searchQ = document.getElementById('live-search-input').value.trim();
  clearTimeout(_searchTimer);
  fetchResults();
}

function setFilter(key, val, el){
  if(key === 'type') _searchType = parseInt(val);
  if(key === 'cid')  _searchCid  = parseInt(val);

  // Update chip styles
  var isTypeChip = el.dataset.type !== undefined;
  var isCidChip  = el.dataset.cid  !== undefined;
  document.querySelectorAll('.filter-chip').forEach(function(c){
    if(isTypeChip && c.dataset.type !== undefined){
      c.classList.remove('active','active-found','active-lost');
      c.style.borderColor = 'var(--saf-border)'; c.style.color = '';
    }
    if(isCidChip && c.dataset.cid !== undefined){
      c.classList.remove('active');
    }
  });
  if(isTypeChip){
    if(val==='-1'){ el.classList.add('active'); }
    else if(val==='1'){ el.classList.add('active-found'); el.style.borderColor='#059669'; }
    else { el.classList.add('active-lost'); el.style.borderColor='#dc2626'; }
  } else {
    el.classList.add('active');
  }
  clearTimeout(_searchTimer);
  fetchResults();
}

function showSkeletons(){
  var grid = document.getElementById('results-grid');
  var html = '';
  for(var i=0;i<8;i++){
    html += '<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">'
          + '<div class="skel-card shadow-sm">'
          + '<div class="skel" style="height:190px"></div>'
          + '<div style="padding:14px">'
          + '<div class="skel rounded mb-2" style="height:10px;width:40%"></div>'
          + '<div class="skel rounded mb-2" style="height:14px;width:85%"></div>'
          + '<div class="skel rounded" style="height:12px;width:60%"></div>'
          + '</div></div></div>';
  }
  grid.innerHTML = html;
}

function fetchResults(){
  var params = new URLSearchParams();
  params.set('page','search');
  params.set('q', _searchQ);
  if(_searchCid > 0)   params.set('cid',  _searchCid);
  if(_searchType >= 0) params.set('type', _searchType);
  params.set('_ajax','1');

  history.replaceState(null,'','?'+params.toString().replace('&_ajax=1',''));
  showSkeletons();
  document.getElementById('search-status').textContent = 'Searching…';

  $.get(_base_url_ + '?' + params.toString(), function(html){
    document.getElementById('search-status').textContent = '';
    var $parsed = $(html);
    var newGrid = $parsed.find('#results-grid').html() || html;
    var newMeta = $parsed.find('#results-count').html() || '';
    document.getElementById('results-grid').innerHTML = newGrid;
    document.getElementById('results-count').innerHTML = newMeta;
    // Stagger reveal
    document.querySelectorAll('.result-item').forEach(function(el, i){
      setTimeout(function(){ el.classList.add('in'); }, i * 45);
    });
  });
}

// Initial stagger reveal on page load
$(function(){
  document.querySelectorAll('.result-item').forEach(function(el, i){
    setTimeout(function(){ el.classList.add('in'); }, i * 55);
  });
});
</script>
