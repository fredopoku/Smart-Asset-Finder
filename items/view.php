<?php
if(!defined('base_url')){
    $id = (int)($_GET['id'] ?? 0);
    header('Location: ../index.php?page=items/view' . ($id ? '&id='.$id : ''));
    exit;
}
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$item_id){ echo '<script>location.replace("'.base_url.'?page=items")</script>'; exit; }

$stmt = $conn->prepare("SELECT il.*, cl.name as category FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE il.id=? AND il.status IN(1,2) LIMIT 1");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$item){ echo '<script>location.replace("'.base_url.'?page=items")</script>'; exit; }

$claims_count = $conn->prepare("SELECT COUNT(*) c FROM item_claims WHERE item_id=?");
$claims_count->bind_param('i', $item_id);
$claims_count->execute();
$num_claims = $claims_count->get_result()->fetch_assoc()['c'];
$claims_count->close();

$media_stmt = $conn->prepare("SELECT * FROM item_media WHERE item_id=? ORDER BY sort_order ASC");
$media_stmt->bind_param('i', $item_id);
$media_stmt->execute();
$media_files = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$media_stmt->close();
if(empty($media_files) && !empty($item['image_path'])){
    $fp = explode('?', $item['image_path'])[0];
    $media_files = [['path'=>$fp,'media_type'=>'image','id'=>0]];
}

$rel = $conn->prepare("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE il.status=1 AND il.category_id=? AND il.id!=? ORDER BY il.created_at DESC LIMIT 4");
$rel->bind_param('ii', $item['category_id'], $item_id);
$rel->execute();
$related = $rel->get_result();
$rel->close();

$is_found   = (int)$item['item_type'] === 1;
$is_claimed = (int)$item['status'] === 2;
$days_ago   = max(0, (int)floor((time() - strtotime($item['created_at'])) / 86400));

$primary_path = '';
$primary_is_video = false;
if(!empty($media_files)){
    $primary = $media_files[0];
    $primary_path = base_url . $primary['path'];
    $primary_is_video = $primary['media_type'] === 'video';
}
?>

<!-- ── Cinematic item header ─────────────────────────── -->
<div class="item-view-hero" id="item-view-top">
  <!-- Blurred background fill -->
  <?php if($primary_path && !$primary_is_video): ?>
  <div class="item-hero-blur" style="background-image:url('<?= $primary_path ?>')"></div>
  <?php endif; ?>
  <div class="item-hero-overlay"></div>

  <div class="container-xl px-4 item-hero-content">
    <!-- Breadcrumb pill -->
    <div class="item-breadcrumb-pill">
      <a href="<?= base_url ?>?page=items"><i class="bi bi-grid me-1"></i>Browse</a>
      <span>/</span>
      <a href="<?= base_url ?>?page=items&cid=<?= $item['category_id'] ?>"><?= htmlspecialchars($item['category'] ?? 'Uncategorized') ?></a>
    </div>

    <div class="row align-items-end g-0">
      <div class="col-lg-8">
        <!-- Status badges -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <span class="item-hero-badge <?= $is_found ? 'badge-hero-found' : 'badge-hero-lost' ?>">
            <i class="bi <?= $is_found ? 'bi-hand-thumbs-up-fill' : 'bi-question-circle-fill' ?> me-1"></i>
            <?= $is_found ? 'Found Item' : 'Lost Item' ?>
          </span>
          <?php if($is_claimed): ?>
          <span class="item-hero-badge badge-hero-claimed"><i class="bi bi-patch-check-fill me-1"></i>Reunited</span>
          <?php elseif($days_ago >= 7): ?>
          <span class="item-hero-badge badge-hero-waiting"><i class="bi bi-clock-fill me-1"></i>Waiting <?= $days_ago ?> days</span>
          <?php endif; ?>
          <span class="item-hero-badge badge-hero-cat"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($item['category'] ?? '') ?></span>
        </div>

        <h1 class="item-hero-title"><?= htmlspecialchars($item['title']) ?></h1>

        <div class="d-flex gap-3 flex-wrap" style="font-size:.83rem;color:rgba(255,255,255,.7)">
          <?php if(!empty($item['location'])): ?>
          <span><i class="bi bi-geo-alt-fill me-1" style="color:#f59e0b"></i><?= htmlspecialchars($item['location']) ?></span>
          <?php endif; ?>
          <?php if(!empty($item['date_lost_found'])): ?>
          <span><i class="bi bi-calendar3 me-1" style="color:#818cf8"></i><?= date('M j, Y', strtotime($item['date_lost_found'])) ?></span>
          <?php endif; ?>
          <span><i class="bi bi-clock me-1" style="color:#6ee7b7"></i>Reported <?= $days_ago === 0 ? 'today' : $days_ago.' day'.($days_ago!=1?'s':'').' ago' ?></span>
        </div>
      </div>

      <!-- Share -->
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <button class="btn-hero-share" onclick="shareItem()">
          <i class="bi bi-share-fill me-2"></i>Share
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Main content ──────────────────────────────────── -->
<div class="container-xl px-4 py-4">
  <div class="row g-4">

    <!-- LEFT: Media + Description -->
    <div class="col-lg-8">

      <!-- Media gallery -->
      <?php if(!empty($media_files)): ?>
      <div class="item-gallery-card scroll-reveal">
        <div class="item-primary-media" id="primary-media-wrap" style="background:#0f172a;border-radius:20px 20px 0 0;overflow:hidden;position:relative">
          <?php if($primary_is_video): ?>
            <video id="primary-video" src="<?= $primary_path ?>" controls playsinline
              style="width:100%;max-height:480px;display:block;object-fit:contain"></video>
          <?php else: ?>
            <img id="primary-img" src="<?= $primary_path ?>" alt="<?= htmlspecialchars($item['title']) ?>"
              style="width:100%;max-height:480px;object-fit:contain;cursor:zoom-in;display:block;transition:transform .3s ease"
              onclick="viewer_modal('<?= $primary_path ?>')"
              onmouseover="this.style.transform='scale(1.02)'"
              onmouseout="this.style.transform='scale(1)'">
          <?php endif; ?>

          <!-- Image overlay gradient -->
          <div style="position:absolute;bottom:0;left:0;right:0;height:60px;background:linear-gradient(transparent,rgba(0,0,0,.4));pointer-events:none"></div>

          <!-- Full-screen hint -->
          <?php if(!$primary_is_video): ?>
          <div style="position:absolute;bottom:12px;right:12px;background:rgba(0,0,0,.5);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:4px 10px;font-size:.72rem;color:rgba(255,255,255,.8);pointer-events:none">
            <i class="bi bi-zoom-in me-1"></i>Click to enlarge
          </div>
          <?php endif; ?>
        </div>

        <?php if(count($media_files) > 1): ?>
        <div class="d-flex gap-2 p-3" style="background:#f8faff;overflow-x:auto;border-radius:0 0 20px 20px">
          <?php foreach($media_files as $idx => $mf): $mp = base_url.$mf['path']; $mv = $mf['media_type']==='video'; ?>
          <div class="item-thumb <?= $idx===0?'active':'' ?>"
            style="flex-shrink:0;width:80px;height:64px;border-radius:10px;overflow:hidden;cursor:pointer;border:2.5px solid <?= $idx===0?'var(--saf-primary)':'#e2e8f0' ?>;position:relative;transition:all .2s"
            onclick="switchMedia('<?= $mp ?>',<?= $mv?'true':'false' ?>,this)"
            onmouseover="this.style.transform='translateY(-2px)'"
            onmouseout="this.style.transform='translateY(0)'">
            <?php if($mv): ?>
              <video src="<?= $mp ?>" style="width:100%;height:100%;object-fit:cover" muted></video>
              <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;background:rgba(0,0,0,.4)"><i class="bi bi-play-fill"></i></span>
            <?php else: ?>
              <img src="<?= $mp ?>" style="width:100%;height:100%;object-fit:cover">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <div style="height:4px;background:linear-gradient(90deg,var(--saf-primary),#7c3aed,var(--saf-gold));border-radius:0 0 20px 20px"></div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="scroll-reveal" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:20px;height:280px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.75rem;color:#94a3b8;margin-bottom:1.5rem">
        <i class="bi bi-image" style="font-size:3.5rem;opacity:.3"></i>
        <span style="font-size:.82rem">No photo provided</span>
      </div>
      <?php endif; ?>

      <!-- Description card -->
      <div class="card border-0 shadow-sm mt-4 scroll-reveal" style="border-radius:20px;overflow:hidden">
        <div style="height:3px;background:linear-gradient(90deg,var(--saf-primary),#7c3aed)"></div>
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3" style="font-family:'Space Grotesk',sans-serif;color:var(--saf-dark)">
            <i class="bi bi-file-text me-2 text-primary"></i>About This Item
          </h5>
          <p style="color:#374151;line-height:1.9;font-size:.95rem"><?= nl2br(htmlspecialchars($item['description'])) ?></p>

          <?php if(!empty($item['location'])): ?>
          <div class="mt-4 p-3 rounded-3" style="background:linear-gradient(135deg,rgba(79,70,229,.04),rgba(124,58,237,.04));border:1px solid rgba(79,70,229,.1)">
            <div class="fw-semibold mb-1" style="font-size:.82rem;color:var(--saf-primary)"><i class="bi bi-geo-alt-fill me-1"></i>Location</div>
            <div style="font-size:.93rem;color:var(--saf-navy)"><?= htmlspecialchars($item['location']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reporter info (mobile — shows between desc and claim on small screens) -->
      <?php if(!$is_claimed): ?>
      <div class="d-lg-none mt-3 scroll-reveal">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
          <div style="height:2px;background:linear-gradient(90deg,var(--saf-primary),var(--saf-gold))"></div>
          <div class="card-body p-3">
            <div class="text-muted fw-semibold mb-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em">
              <i class="bi bi-person-circle me-1"></i><?= $is_found ? 'Reported by finder' : 'Owner' ?>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--saf-primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;font-weight:800;flex-shrink:0">
                <?= strtoupper(substr($item['fullname'],0,1)) ?>
              </div>
              <div>
                <div class="fw-bold" style="font-size:.9rem;color:var(--saf-dark)"><?= htmlspecialchars($item['fullname']) ?></div>
                <a href="tel:<?= htmlspecialchars($item['contact']) ?>" class="text-decoration-none text-muted" style="font-size:.78rem">
                  <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($item['contact']) ?>
                </a>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="tel:<?= htmlspecialchars($item['contact']) ?>" class="btn btn-sm rounded-pill flex-fill fw-semibold" style="background:rgba(79,70,229,.08);color:var(--saf-primary);border:1px solid rgba(79,70,229,.2);font-size:.78rem"><i class="bi bi-telephone-fill me-1"></i>Call</a>
              <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$item['contact']) ?>?text=<?= urlencode('Hi, I saw your item "'.$item['title'].'" on Smart Asset Finder.') ?>" target="_blank" class="btn btn-sm rounded-pill flex-fill fw-semibold" style="background:#25d36618;color:#128c7e;border:1px solid #25d36630;font-size:.78rem"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT: Sticky sidebar -->
    <div class="col-lg-4">
      <div class="item-sidebar" id="item-sidebar">

        <!-- Claim / action card -->
        <?php if(!$is_claimed): ?>
        <div class="item-claim-card scroll-reveal <?= $is_found ? 'claim-found' : 'claim-lost' ?>">
          <div class="claim-icon-wrap">
            <div class="claim-icon-pulse"></div>
            <div class="claim-icon">
              <i class="bi <?= $is_found ? 'bi-emoji-smile-fill' : 'bi-broadcast' ?>"></i>
            </div>
          </div>
          <h4 class="fw-bold mb-1" style="font-family:'Space Grotesk',sans-serif">
            <?= $is_found ? 'Is this yours?' : 'Did you find this?' ?>
          </h4>
          <p style="font-size:.85rem;opacity:.85;line-height:1.65;margin-bottom:1.25rem">
            <?= $is_found
              ? 'If this item belongs to you, submit a claim. We\'ll verify and connect you with the finder securely.'
              : 'If you found this item, let the owner know. You\'ll be helping someone recover something that matters to them.' ?>
          </p>
          <?php if($num_claims > 0): ?>
          <div class="claim-counter"><i class="bi bi-people-fill me-1"></i><?= $num_claims ?> claim<?=$num_claims!=1?'s':''?> already submitted</div>
          <?php endif; ?>
          <button class="btn-claim-action w-100"
            onclick="uni_modal('<?= $is_found ? 'Claim This Item' : 'Report You Found This' ?>','<?= base_url ?>items/claim_form.php?id=<?= $item_id ?>')">
            <i class="bi <?= $is_found ? 'bi-patch-check-fill' : 'bi-hand-index-thumb-fill' ?> me-2"></i>
            <?= $is_found ? 'Submit My Claim' : 'I Found This Item' ?>
          </button>
        </div>
        <?php else: ?>
        <div class="scroll-reveal" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:1px solid #6ee7b7;border-radius:20px;padding:1.75rem;text-align:center;margin-bottom:1rem">
          <i class="bi bi-patch-check-fill" style="font-size:2.5rem;color:#059669;display:block;margin-bottom:.75rem"></i>
          <div class="fw-bold" style="color:#065f46;font-size:1.05rem;font-family:'Space Grotesk',sans-serif">Item Reunited!</div>
          <div style="font-size:.82rem;color:#047857;margin-top:.35rem">This item has been successfully returned to its owner.</div>
        </div>
        <?php endif; ?>

        <!-- Reporter card (desktop) -->
        <div class="d-none d-lg-block scroll-reveal">
          <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;overflow:hidden">
            <div style="height:2px;background:linear-gradient(90deg,var(--saf-primary),var(--saf-gold))"></div>
            <div class="card-body p-3">
              <div class="text-muted fw-semibold mb-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em">
                <i class="bi bi-person-circle me-1"></i><?= $is_found ? 'Reported by finder' : 'Owner' ?>
              </div>
              <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--saf-primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;font-weight:800;flex-shrink:0;font-family:'Space Grotesk',sans-serif">
                  <?= strtoupper(substr($item['fullname'],0,1)) ?>
                </div>
                <div>
                  <div class="fw-bold" style="font-size:.93rem;color:var(--saf-dark)"><?= htmlspecialchars($item['fullname']) ?></div>
                  <?php if(!$is_claimed): ?>
                  <a href="tel:<?= htmlspecialchars($item['contact']) ?>" class="text-decoration-none text-muted" style="font-size:.8rem">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($item['contact']) ?>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <?php if(!$is_claimed): ?>
              <div class="d-flex gap-2 mt-3">
                <a href="tel:<?= htmlspecialchars($item['contact']) ?>" class="btn btn-sm rounded-pill flex-fill fw-semibold" style="background:rgba(79,70,229,.08);color:var(--saf-primary);border:1px solid rgba(79,70,229,.2);font-size:.78rem">
                  <i class="bi bi-telephone-fill me-1"></i>Call
                </a>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$item['contact']) ?>?text=<?= urlencode('Hi, I saw your item "'.$item['title'].'" on Smart Asset Finder.') ?>"
                  target="_blank"
                  class="btn btn-sm rounded-pill flex-fill fw-semibold" style="background:#25d36618;color:#128c7e;border:1px solid #25d36630;font-size:.78rem">
                  <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- QR Tag -->
        <div class="card border-0 shadow-sm mb-3 scroll-reveal" style="border-radius:16px;overflow:hidden">
          <div style="height:2px;background:linear-gradient(90deg,#818cf8,#c084fc)"></div>
          <div class="card-body p-3">
            <div class="fw-semibold mb-1" style="font-size:.8rem;color:var(--saf-dark)"><i class="bi bi-qr-code me-1 text-primary"></i>Shareable QR Tag</div>
            <p class="text-muted mb-3" style="font-size:.74rem;line-height:1.5">Anyone who finds this item can scan below to view details and contact the owner.</p>
            <div id="item-qr" class="text-center mb-3" style="line-height:0"></div>
            <button onclick="printQR()" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-semibold">
              <i class="bi bi-printer me-1"></i>Print This Tag
            </button>
          </div>
        </div>

        <!-- Back / support -->
        <div class="d-flex gap-2 scroll-reveal">
          <a href="<?= base_url ?>?page=items" class="btn btn-outline-secondary rounded-pill flex-fill" style="font-size:.82rem">
            <i class="bi bi-arrow-left me-1"></i>Back
          </a>
          <a href="<?= base_url ?>?page=contact" class="btn btn-outline-secondary rounded-pill flex-fill" style="font-size:.82rem">
            <i class="bi bi-headset me-1"></i>Support
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- Related items -->
  <?php if($related->num_rows > 0): ?>
  <div class="mt-5 scroll-reveal">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <div class="section-label" style="margin-bottom:.3rem">More like this</div>
        <h3 class="fw-bold mb-0" style="font-size:1.3rem">Related Items</h3>
      </div>
      <a href="<?= base_url ?>?page=items&cid=<?= $item['category_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php while($r=$related->fetch_assoc()): ?>
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <a href="<?= base_url ?>?page=items/view&id=<?= $r['id'] ?>" class="text-decoration-none">
          <div class="item-card card h-100" style="border-radius:16px">
            <div class="card-img-wrap" style="border-radius:16px 16px 0 0">
              <?php if(!empty($r['image_path']) && is_file(base_app.explode('?',$r['image_path'])[0])): ?>
                <img src="<?= base_url.explode('?',$r['image_path'])[0] ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted"><i class="bi bi-image" style="font-size:2rem;opacity:.2"></i></div>
              <?php endif; ?>
              <span class="item-type-badge <?= $r['item_type']?'badge-found':'badge-lost' ?>"><?= $r['item_type']?'Found':'Lost' ?></span>
            </div>
            <div class="card-body">
              <div class="card-cat"><?= htmlspecialchars($r['cat_name']??'') ?></div>
              <div class="card-title txt-clamp-2"><?= htmlspecialchars($r['title']) ?></div>
            </div>
            <div class="card-footer">
              <span class="card-date"><?= date('M j', strtotime($r['created_at'])) ?></span>
              <span class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;padding:.2rem .65rem">View</span>
            </div>
          </div>
        </a>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<style>
/* ── Cinematic hero ── */
.item-view-hero {
  position: relative;
  padding: 3.5rem 0 2.5rem;
  overflow: hidden;
  margin-top: -1px;
  background: var(--saf-dark);
}
.item-hero-blur {
  position: absolute; inset: 0;
  background-size: cover;
  background-position: center;
  filter: blur(40px) saturate(1.4);
  opacity: .25;
  transform: scale(1.1);
}
.item-hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(3,7,18,.85) 0%, rgba(15,23,42,.75) 60%, rgba(30,41,59,.9) 100%);
}
.item-hero-content { position: relative; z-index: 1; }
.item-breadcrumb-pill {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 50px; padding: .3rem 1rem;
  font-size: .75rem; color: rgba(255,255,255,.6);
  margin-bottom: 1.5rem; backdrop-filter: blur(12px);
}
.item-breadcrumb-pill a { color: rgba(255,255,255,.7); text-decoration: none; }
.item-breadcrumb-pill a:hover { color: #fff; }
.item-hero-badge {
  display: inline-flex; align-items: center;
  padding: .3rem .9rem; border-radius: 50px;
  font-size: .74rem; font-weight: 700; letter-spacing: .04em;
}
.badge-hero-found    { background: rgba(16,185,129,.2); border: 1px solid rgba(16,185,129,.4); color: #6ee7b7; }
.badge-hero-lost     { background: rgba(239,68,68,.2);  border: 1px solid rgba(239,68,68,.4);  color: #fca5a5; }
.badge-hero-claimed  { background: rgba(79,70,229,.2);  border: 1px solid rgba(79,70,229,.4);  color: #818cf8; }
.badge-hero-waiting  { background: rgba(245,158,11,.2); border: 1px solid rgba(245,158,11,.4); color: #fcd34d; }
.badge-hero-cat      { background: rgba(255,255,255,.08);border: 1px solid rgba(255,255,255,.15);color: rgba(255,255,255,.7); }
.item-hero-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: clamp(1.5rem, 3.5vw, 2.4rem);
  font-weight: 800; color: #fff;
  letter-spacing: -.03em; line-height: 1.2;
  margin-bottom: .75rem;
}
.btn-hero-share {
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.2);
  color: rgba(255,255,255,.85);
  border-radius: 50px; padding: .45rem 1.25rem;
  font-size: .82rem; font-weight: 600;
  cursor: pointer; transition: all .2s;
  backdrop-filter: blur(12px);
}
.btn-hero-share:hover { background: rgba(255,255,255,.18); color: #fff; }

/* ── Gallery ── */
.item-gallery-card { border-radius: 20px; overflow: hidden; box-shadow: var(--saf-shadow-md); margin-bottom: 1.5rem; }

/* ── Sidebar ── */
.item-sidebar { position: sticky; top: 88px; }

/* ── Claim card ── */
.item-claim-card {
  position: relative; border-radius: 20px;
  padding: 1.75rem; margin-bottom: 1rem;
  overflow: hidden; text-align: center;
}
.claim-found {
  background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
  color: #ecfdf5;
  box-shadow: 0 12px 40px rgba(5,150,105,.35);
}
.claim-lost {
  background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 50%, #4f46e5 100%);
  color: #ede9fe;
  box-shadow: 0 12px 40px rgba(79,70,229,.35);
}
.claim-icon-wrap { position: relative; width: 72px; height: 72px; margin: 0 auto 1rem; }
.claim-icon-pulse {
  position: absolute; inset: -8px;
  border-radius: 50%;
  border: 2px solid currentColor;
  opacity: .4;
  animation: pulseRing 2s ease-out infinite;
}
@keyframes pulseRing {
  0%   { transform: scale(.85); opacity: .5; }
  50%  { transform: scale(1.12); opacity: .15; }
  100% { transform: scale(.85); opacity: .5; }
}
.claim-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: rgba(255,255,255,.15);
  border: 1.5px solid rgba(255,255,255,.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem;
}
.claim-counter {
  display: inline-flex; align-items: center;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 50px; padding: .25rem .85rem;
  font-size: .73rem; margin-bottom: 1rem;
}
.btn-claim-action {
  background: rgba(255,255,255,.15);
  border: 1.5px solid rgba(255,255,255,.4);
  color: #fff; border-radius: 50px;
  padding: .65rem 1.5rem; font-size: .88rem;
  font-weight: 700; cursor: pointer;
  transition: all .2s; backdrop-filter: blur(8px);
  font-family: 'Space Grotesk', sans-serif;
}
.btn-claim-action:hover {
  background: rgba(255,255,255,.28);
  border-color: rgba(255,255,255,.7);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0,0,0,.2);
}

/* ── Scroll reveal ── */
.scroll-reveal { opacity: 0; transform: translateY(24px); transition: opacity .55s ease, transform .55s ease; }
.scroll-reveal.revealed { opacity: 1; transform: translateY(0); }
</style>

<script src="<?= base_url ?>assets/vendor/qrcode/qrcode.min.js"></script>
<script>
// Media switcher
function switchMedia(src, isVideo, thumbEl){
  var wrap = document.getElementById('primary-media-wrap');
  if(isVideo){
    wrap.querySelector('img,video')?.remove();
    var v = document.createElement('video');
    v.src = src; v.controls = true; v.playsInline = true;
    v.style.cssText = 'width:100%;max-height:480px;display:block;object-fit:contain';
    wrap.prepend(v);
  } else {
    wrap.querySelector('img,video')?.remove();
    var img = document.createElement('img');
    img.src = src;
    img.style.cssText = 'width:100%;max-height:480px;object-fit:contain;cursor:zoom-in;display:block;transition:transform .3s ease';
    img.onclick = function(){ viewer_modal(src); };
    img.onmouseover = function(){ this.style.transform='scale(1.02)'; };
    img.onmouseout  = function(){ this.style.transform='scale(1)'; };
    wrap.prepend(img);
  }
  document.querySelectorAll('.item-thumb').forEach(function(t){ t.style.borderColor = '#e2e8f0'; });
  thumbEl.style.borderColor = 'var(--saf-primary)';
}

// QR code
var _qr_url  = '<?= base_url ?>?page=items/view&id=<?= $item_id ?>';
var _qr_label= '<?= addslashes(htmlspecialchars($item['title'])) ?>';
var _item_id = <?= $item_id ?>;

// Log QR scan if arrived externally
(function(){
  var ref = document.referrer;
  var internal = ref && ref.indexOf(window.location.hostname) !== -1;
  if(!internal){
    var logIt = function(lat, lng){
      var d = {item_id: _item_id};
      if(lat) d.lat = lat; if(lng) d.lng = lng;
      $.ajax({ url: _base_url_+'classes/QrScan.php', method:'POST', data:d, dataType:'json' });
    };
    if(navigator.geolocation){
      navigator.geolocation.getCurrentPosition(
        function(p){ logIt(p.coords.latitude, p.coords.longitude); },
        function(){ logIt(null,null); },
        { timeout:8000, maximumAge:60000 }
      );
    } else { logIt(null,null); }
  }
})();

$(function(){
  // QR code
  new QRCode(document.getElementById('item-qr'), {
    text: _qr_url, width: 160, height: 160,
    colorDark: '#0f172a', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });

  // Scroll reveal
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('revealed'); io.unobserve(e.target); } });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.scroll-reveal').forEach(function(el){ io.observe(el); });
});

function printQR(){
  var canvas = document.querySelector('#item-qr canvas');
  var img    = document.querySelector('#item-qr img');
  var src    = canvas ? canvas.toDataURL() : (img ? img.src : '');
  if(!src){ alert_toast('QR code not ready.','warning'); return; }
  var win = window.open('','_blank');
  win.document.write('<!DOCTYPE html><html><head><title>SAF Tag — '+_qr_label+'</title>'
    +'<style>body{margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#fff;font-family:"Space Grotesk",sans-serif}'
    +'.tag{border:2px dashed #c7d2fe;border-radius:20px;padding:28px 32px;text-align:center;max-width:260px}'
    +'.tag img{width:190px;height:190px;display:block;margin:0 auto 14px}'
    +'.tag h3{font-size:13px;font-weight:800;margin:0 0 4px;color:#0f172a}'
    +'.tag .site{font-size:10px;color:#4f46e5;font-weight:700;letter-spacing:.06em;margin-bottom:8px}'
    +'.tag p{font-size:10px;color:#64748b;margin:0 0 6px;line-height:1.5}'
    +'.tag small{font-size:8px;color:#94a3b8;word-break:break-all}'
    +'@media print{body{min-height:auto}}</style></head><body>'
    +'<div class="tag"><img src="'+src+'">'
    +'<div class="site">SMART ASSET FINDER</div>'
    +'<h3>'+_qr_label+'</h3>'
    +'<p>Scan to return this item to its owner</p>'
    +'<small>'+_qr_url+'</small>'
    +'</div><script>window.onload=function(){window.print();}<\/script></body></html>');
  win.document.close();
}

function shareItem(){
  var url  = window.location.href;
  var text = 'Can you help find this item? — '+_qr_label+' — '+url;
  if(navigator.share){
    navigator.share({ title: _qr_label, text: text, url: url });
  } else {
    navigator.clipboard.writeText(url).then(function(){
      alert_toast('Link copied to clipboard!','success');
    });
  }
}
</script>
