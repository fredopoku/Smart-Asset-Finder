<?php
if(!isset($_SESSION['pub_userdata'])){ redirect('?page=login'); exit; }
$uid = (int)$_SESSION['pub_userdata']['id'];

$urow = $conn->prepare("SELECT firstname,lastname,email,phone,avatar,email_verified FROM registered_users WHERE id=? LIMIT 1");
$urow->bind_param('i', $uid); $urow->execute();
$user = $urow->get_result()->fetch_assoc(); $urow->close();


$brow = $conn->prepare("SELECT b.icon,b.color,b.name FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC LIMIT 4");
$brow->bind_param('i', $uid); $brow->execute();
$user_badges = $brow->get_result()->fetch_all(MYSQLI_ASSOC); $brow->close();

$my_items_stmt = $conn->prepare("SELECT il.*, cl.name as cat_name FROM item_list il LEFT JOIN category_list cl ON cl.id=il.category_id WHERE il.user_id=? ORDER BY il.created_at DESC");
$my_items_stmt->bind_param('i', $uid); $my_items_stmt->execute();
$items_res = $my_items_stmt->get_result()->fetch_all(MYSQLI_ASSOC); $my_items_stmt->close();

$my_claims_stmt = $conn->prepare("SELECT ic.*,il.title as item_title,il.item_type FROM item_claims ic LEFT JOIN item_list il ON il.id=ic.item_id WHERE ic.user_id=? OR ic.email=? ORDER BY ic.created_at DESC");
$email_s = $user['email'];
$my_claims_stmt->bind_param('is', $uid, $email_s); $my_claims_stmt->execute();
$claims_res = $my_claims_stmt->get_result()->fetch_all(MYSQLI_ASSOC); $my_claims_stmt->close();

$tag_stmt = $conn->prepare("SELECT * FROM qr_tags WHERE user_id=? ORDER BY created_at ASC");
$tag_stmt->bind_param('i', $uid); $tag_stmt->execute();
$qr_tags = $tag_stmt->get_result()->fetch_all(MYSQLI_ASSOC); $tag_stmt->close();

$orders_count = (int)($conn->query("SELECT COUNT(*) c FROM orders WHERE user_id={$uid}")->fetch_assoc()['c'] ?? 0);

$status_labels  = [0=>'Pending', 1=>'Published', 2=>'Claimed'];
$status_classes = [0=>'status-pending', 1=>'status-published', 2=>'status-claimed'];
$claim_labels   = [0=>'Under Review', 1=>'Approved', 2=>'Rejected'];
$claim_classes  = [0=>'status-pending', 1=>'status-published', 2=>'status-rejected'];

$avatar_url = !empty($user['avatar']) && is_file(base_app.$user['avatar'])
              ? base_url.$user['avatar'] : '';
$initials   = strtoupper(substr($user['firstname'],0,1).substr($user['lastname'],0,1));
$fullname   = ucwords(strtolower(trim($user['firstname'].' '.$user['lastname'])));
?>

<style>
.myitems-hero{background:var(--saf-dark);position:relative;overflow:hidden;padding:2.5rem 0 4rem}
.myitems-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 90% at 85% 30%,rgba(79,70,229,.6) 0%,transparent 55%),radial-gradient(ellipse 50% 60% at 5% 85%,rgba(245,158,11,.18) 0%,transparent 50%)}
.myitems-hero::after{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.04) 1px,transparent 1px);background-size:26px 26px}
.myitems-hero-content{position:relative;z-index:1}
.mi-avatar{width:76px;height:76px;border-radius:50%;border:3px solid rgba(255,255,255,.25);object-fit:cover;flex-shrink:0}
.mi-avatar-init{width:76px;height:76px;border-radius:50%;border:3px solid rgba(255,255,255,.2);background:linear-gradient(135deg,rgba(255,255,255,.2),rgba(255,255,255,.08));display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:800;color:#fff;font-family:'Space Grotesk',sans-serif;flex-shrink:0}
.mi-stat-pill{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:12px;padding:.5rem .85rem;text-align:center;backdrop-filter:blur(8px);min-width:62px;text-decoration:none;display:block;transition:background .2s}
.mi-stat-pill:hover{background:rgba(255,255,255,.18)}
.mi-stat-num{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:800;color:#fff;line-height:1}
.mi-stat-lbl{font-size:.64rem;color:rgba(255,255,255,.5);margin-top:.15rem}
.mi-wrap{padding:1.5rem 0 4rem}

/* Sections */
.mi-section{background:#fff;border-radius:18px;box-shadow:0 2px 16px rgba(0,0,0,.06);margin-bottom:1.25rem;overflow:hidden}
.mi-section-head{padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem}
.mi-section-body{padding:1.25rem 1.5rem}

/* QR tag card */
.qr-tag-card{background:linear-gradient(135deg,#f8faff,#fff);border:1px solid #e8eeff;border-radius:14px;padding:.85rem;text-align:center;transition:transform .2s,box-shadow .2s;position:relative}
.qr-tag-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(79,70,229,.12)}
.qr-tag-card .tag-code{font-size:.6rem;letter-spacing:.05em;font-family:monospace;color:#94a3b8;margin-top:.25rem}

/* Premium print badge */
.premium-badge{position:absolute;top:-6px;right:-6px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.58rem;font-weight:800;border-radius:50px;padding:.18rem .5rem;letter-spacing:.04em;text-transform:uppercase;box-shadow:0 2px 8px rgba(245,158,11,.4)}

/* Table tweaks */
.mi-table thead th{background:#f8faff;font-size:.75rem;color:var(--saf-muted);font-weight:600;padding:.6rem 1rem;border-bottom:1px solid #f1f5f9}
.mi-table tbody td{padding:.65rem 1rem;vertical-align:middle;border-bottom:1px solid #f8faff}
.mi-table tbody tr:last-child td{border-bottom:none}
.mi-table tbody tr:hover td{background:#fafbff}

/* Empty state */
.mi-empty{text-align:center;padding:2.5rem 1rem}
.mi-empty-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .85rem;font-size:1.6rem}
</style>

<!-- ── Hero ── -->
<div class="myitems-hero">
  <div class="container-xl px-4 myitems-hero-content">
    <div class="d-flex align-items-center gap-3 flex-wrap">

      <?php if($avatar_url): ?>
      <img src="<?= $avatar_url ?>" class="mi-avatar" alt="Avatar">
      <?php else: ?>
      <div class="mi-avatar-init"><?= $initials ?></div>
      <?php endif; ?>

      <div class="flex-grow-1">
        <div style="font-size:.68rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.09em;margin-bottom:.2rem">My Dashboard</div>
        <h2 class="fw-bold text-white mb-1" style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.15rem,3vw,1.6rem);letter-spacing:-.02em">
          <?= htmlspecialchars($fullname) ?>
        </h2>
        <div style="color:rgba(255,255,255,.5);font-size:.8rem">
          <?= htmlspecialchars($user['email']) ?>
          <?php if($user['email_verified']): ?>
          <span style="background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.3);border-radius:50px;padding:.12rem .5rem;font-size:.65rem;color:#6ee7b7;margin-left:.4rem"><i class="bi bi-patch-check-fill"></i> Verified</span>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2 mt-2 flex-wrap">
          <a href="<?= base_url ?>?page=found" class="btn btn-sm rounded-pill fw-semibold" style="background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.22);font-size:.76rem;padding:.3rem .85rem"><i class="bi bi-plus me-1"></i>Report Found</a>
          <a href="<?= base_url ?>?page=lost"  class="btn btn-sm rounded-pill fw-semibold" style="background:rgba(255,255,255,.07);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.16);font-size:.76rem;padding:.3rem .85rem"><i class="bi bi-plus me-1"></i>Report Lost</a>
          <a href="<?= base_url ?>?page=profile" class="btn btn-sm rounded-pill fw-semibold" style="background:rgba(255,255,255,.07);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.16);font-size:.76rem;padding:.3rem .85rem"><i class="bi bi-person me-1"></i>Edit Profile</a>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <div class="mi-stat-pill">
          <div class="mi-stat-num"><?= count($items_res) ?></div>
          <div class="mi-stat-lbl">Reports</div>
        </div>
        <div class="mi-stat-pill">
          <div class="mi-stat-num"><?= count($qr_tags) ?></div>
          <div class="mi-stat-lbl">QR Tags</div>
        </div>
        <?php if($orders_count > 0): ?>
        <a href="<?= base_url ?>?page=my-orders" class="mi-stat-pill text-decoration-none">
          <div class="mi-stat-num"><?= $orders_count ?></div>
          <div class="mi-stat-lbl">Orders</div>
        </a>
        <?php endif; ?>
        <?php if(!empty($user_badges)): ?>
        <div class="mi-stat-pill d-flex align-items-center gap-1 justify-content-center">
          <?php foreach($user_badges as $b): ?>
          <i class="bi <?= htmlspecialchars($b['icon']) ?>" style="color:<?= htmlspecialchars($b['color']) ?>;font-size:.95rem" title="<?= htmlspecialchars($b['name']) ?>"></i>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- ── Content ── -->
<div class="container-xl px-4 mi-wrap">

  <!-- ─── QR Tags ─── -->
  <div class="mi-section">
    <div class="mi-section-head">
      <div>
        <div class="section-label" style="margin-bottom:.2rem">Free Asset Tags</div>
        <h3 class="fw-bold mb-0" style="font-size:1.05rem">My QR Tags</h3>
        <div class="text-muted" style="font-size:.78rem;margin-top:.15rem">Stick these on valuables — anyone who finds them scans to return to you instantly.</div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <?php if(!empty($qr_tags)): ?>
        <button class="btn btn-sm btn-outline-primary rounded-pill fw-semibold" onclick="new bootstrap.Modal(document.getElementById('premium-print-modal')).show()">
          <i class="bi bi-stars me-1" style="color:#f59e0b"></i>Professional Print
        </button>
        <button class="btn btn-sm btn-gradient rounded-pill fw-semibold" onclick="printAllTags()">
          <i class="bi bi-printer me-1"></i>Self Print
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div class="mi-section-body">
      <?php if(!empty($qr_tags)): ?>
      <div class="row g-3">
        <?php foreach($qr_tags as $qt):
          $turl = base_url.'?page=tag&code='.$qt['tag_code'];
          $tlbl = !empty($qt['label']) ? $qt['label'] : '';
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
          <div class="qr-tag-card">
            <div id="qrtag-<?= $qt['id'] ?>" style="display:flex;justify-content:center;line-height:0;margin-bottom:.5rem"></div>
            <div id="lbl-<?= $qt['id'] ?>" class="fw-semibold txt-clamp-1" style="font-size:.78rem;color:var(--saf-dark);min-height:1.1rem">
              <?= $tlbl ? htmlspecialchars($tlbl) : '<span style="color:#94a3b8;font-style:italic;font-weight:400">Unlabelled</span>' ?>
            </div>
            <div class="tag-code"><?= $qt['tag_code'] ?></div>
            <div class="d-flex gap-1 mt-2">
              <button onclick="editTag(<?= $qt['id'] ?>,'<?= addslashes($tlbl) ?>')" class="btn btn-sm rounded-pill flex-grow-1" style="font-size:.68rem;padding:.22rem .4rem;border:1px solid #e2e8f0;color:#64748b"><i class="bi bi-pencil me-1"></i>Label</button>
              <button onclick="printTag(<?= $qt['id'] ?>,'<?= addslashes($tlbl?:$qt['tag_code']) ?>','<?= $turl ?>')" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1" style="font-size:.68rem;padding:.22rem .4rem"><i class="bi bi-printer me-1"></i>Print</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="mi-empty" style="background:#f8faff;border:1.5px dashed #c7d2fe;border-radius:12px">
        <div class="mi-empty-icon" style="background:rgba(79,70,229,.08);color:var(--saf-primary)"><i class="bi bi-qr-code"></i></div>
        <div class="fw-semibold mb-1">No QR tags yet</div>
        <p class="text-muted mb-2" style="font-size:.8rem;max-width:320px;margin:0 auto .75rem">Contact support or visit our shop to get free QR tags for your valuables.</p>
        <a href="<?= base_url ?>?page=shop" class="btn btn-outline-primary rounded-pill btn-sm fw-semibold"><i class="bi bi-bag me-1"></i>Visit Shop</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ─── My Submissions ─── -->
  <div class="mi-section">
    <div class="mi-section-head">
      <div>
        <h3 class="fw-bold mb-0" style="font-size:1.05rem">My Submissions</h3>
        <div class="text-muted" style="font-size:.78rem;margin-top:.15rem">Items you've reported to the platform</div>
      </div>
      <?php if(count($items_res)>0): ?>
      <span class="badge rounded-pill bg-primary-subtle text-primary" style="font-size:.75rem;padding:.3rem .8rem"><?= count($items_res) ?> item<?= count($items_res)!=1?'s':'' ?></span>
      <?php endif; ?>
    </div>
    <div class="p-0">
      <?php if(count($items_res)>0): ?>
      <div class="table-responsive">
        <table class="table mb-0 mi-table">
          <thead><tr>
            <th style="padding-left:1.5rem">Item</th>
            <th>Type</th>
            <th>Date</th>
            <th>Status</th>
            <th></th>
          </tr></thead>
          <tbody>
            <?php foreach($items_res as $it): ?>
            <tr>
              <td style="padding-left:1.5rem">
                <div class="d-flex align-items-center gap-3">
                  <?php if(!empty($it['image_path']) && is_file(base_app.explode('?',$it['image_path'])[0])): ?>
                  <img src="<?= base_url.explode('?',$it['image_path'])[0] ?>" style="width:42px;height:42px;object-fit:cover;border-radius:10px;flex-shrink:0">
                  <?php else: ?>
                  <div style="width:42px;height:42px;background:#f1f5f9;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="bi bi-image text-muted" style="font-size:.85rem"></i></div>
                  <?php endif; ?>
                  <div>
                    <div class="fw-semibold txt-clamp-1" style="font-size:.85rem;color:var(--saf-dark)"><?= htmlspecialchars($it['title']) ?></div>
                    <div class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($it['cat_name']??'') ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge rounded-pill <?= $it['item_type']?'badge-found':'badge-lost' ?>"><?= $it['item_type']?'Found':'Lost' ?></span></td>
              <td style="font-size:.77rem;color:var(--saf-muted)"><?= date('M j, Y', strtotime($it['created_at'])) ?></td>
              <td><span class="badge rounded-pill <?= $status_classes[$it['status']] ?>"><?= $status_labels[$it['status']] ?></span></td>
              <td><a href="<?= base_url ?>?page=items/view&id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;padding:.22rem .65rem">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="mi-empty mx-3 my-2" style="background:#f8faff;border:1.5px dashed #c7d2fe;border-radius:12px">
        <div class="mi-empty-icon" style="background:rgba(14,165,233,.08);color:#0ea5e9"><i class="bi bi-collection"></i></div>
        <div class="fw-semibold mb-1">No submissions yet</div>
        <p class="text-muted mb-3" style="font-size:.8rem">Help reunite lost items with their owners</p>
        <a href="<?= base_url ?>?page=found" class="btn btn-primary rounded-pill btn-sm fw-semibold"><i class="bi bi-plus me-1"></i>Report Found Item</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ─── My Claims ─── -->
  <div class="mi-section">
    <div class="mi-section-head">
      <div>
        <h3 class="fw-bold mb-0" style="font-size:1.05rem">My Claims</h3>
        <div class="text-muted" style="font-size:.78rem;margin-top:.15rem">Items you've submitted a claim for</div>
      </div>
      <?php if(count($claims_res)>0): ?>
      <span class="badge rounded-pill bg-success-subtle text-success" style="font-size:.75rem;padding:.3rem .8rem"><?= count($claims_res) ?> claim<?= count($claims_res)!=1?'s':'' ?></span>
      <?php endif; ?>
    </div>
    <div class="p-0">
      <?php if(count($claims_res)>0): ?>
      <div class="table-responsive">
        <table class="table mb-0 mi-table">
          <thead><tr>
            <th style="padding-left:1.5rem">Item</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>Status</th>
          </tr></thead>
          <tbody>
            <?php foreach($claims_res as $cl): ?>
            <tr>
              <td style="padding-left:1.5rem">
                <div class="fw-semibold" style="font-size:.85rem;color:var(--saf-dark)"><?= htmlspecialchars($cl['item_title']??'Unknown item') ?></div>
                <?php if(!empty($cl['admin_note'])): ?>
                <div style="font-size:.7rem;color:#64748b;margin-top:.2rem"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($cl['admin_note']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge rounded-pill <?= $cl['item_type']?'badge-found':'badge-lost' ?>"><?= $cl['item_type']?'Found':'Lost' ?></span></td>
              <td style="font-size:.77rem;color:var(--saf-muted)"><?= date('M j, Y', strtotime($cl['created_at'])) ?></td>
              <td><span class="badge rounded-pill <?= $claim_classes[$cl['status']] ?>"><?= $claim_labels[$cl['status']] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="mi-empty mx-3 my-2" style="background:#f8faff;border:1.5px dashed #c7d2fe;border-radius:12px">
        <div class="mi-empty-icon" style="background:rgba(5,150,105,.08);color:#059669"><i class="bi bi-patch-check"></i></div>
        <div class="fw-semibold mb-1">No claims yet</div>
        <p class="text-muted mb-3" style="font-size:.8rem">See something that belongs to you?</p>
        <a href="<?= base_url ?>?page=items" class="btn btn-outline-primary rounded-pill btn-sm fw-semibold"><i class="bi bi-grid me-1"></i>Browse Items</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── Premium Print Modal ── -->
<div class="modal fade" id="premium-print-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0" style="border-radius:20px;overflow:hidden">
      <div style="height:4px;background:linear-gradient(90deg,#f59e0b,#d97706,#4f46e5)"></div>
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <div>
          <h5 class="fw-bold mb-0" style="font-family:'Space Grotesk',sans-serif"><i class="bi bi-stars text-warning me-2"></i>Professional QR Tag Printing</h5>
          <div class="text-muted" style="font-size:.8rem;margin-top:.25rem">Premium quality, waterproof, adhesive-backed tags</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <!-- Pricing cards -->
        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="print-plan-card" style="cursor:pointer">
              <input type="radio" name="print_plan" value="basic" style="display:none" onchange="selectPlan(this)">
              <div class="plan-inner" data-plan="basic" style="border:2px solid #e2e8f0;border-radius:12px;padding:.85rem .5rem;text-align:center;transition:all .2s">
                <div style="font-size:1.2rem;margin-bottom:.2rem">📋</div>
                <div class="fw-bold" style="font-size:.82rem">Basic</div>
                <div style="font-size:1rem;font-weight:800;color:var(--saf-primary);margin:.2rem 0">GHS 10</div>
                <div style="font-size:.68rem;color:#64748b">3 tags</div>
              </div>
            </label>
          </div>
          <div class="col-4">
            <label class="print-plan-card" style="cursor:pointer">
              <input type="radio" name="print_plan" value="standard" style="display:none" checked onchange="selectPlan(this)">
              <div class="plan-inner" data-plan="standard" style="border:2px solid var(--saf-primary);border-radius:12px;padding:.85rem .5rem;text-align:center;background:rgba(79,70,229,.04);transition:all .2s">
                <div style="font-size:1.2rem;margin-bottom:.2rem">🏅</div>
                <div class="fw-bold" style="font-size:.82rem">Standard</div>
                <div style="font-size:1rem;font-weight:800;color:var(--saf-primary);margin:.2rem 0">GHS 25</div>
                <div style="font-size:.68rem;color:#64748b">10 tags</div>
                <div style="font-size:.6rem;background:#4f46e5;color:#fff;border-radius:50px;padding:.08rem .4rem;margin-top:.25rem">Popular</div>
              </div>
            </label>
          </div>
          <div class="col-4">
            <label class="print-plan-card" style="cursor:pointer">
              <input type="radio" name="print_plan" value="premium" style="display:none" onchange="selectPlan(this)">
              <div class="plan-inner" data-plan="premium" style="border:2px solid #e2e8f0;border-radius:12px;padding:.85rem .5rem;text-align:center;transition:all .2s">
                <div style="font-size:1.2rem;margin-bottom:.2rem">👑</div>
                <div class="fw-bold" style="font-size:.82rem">Premium</div>
                <div style="font-size:1rem;font-weight:800;color:var(--saf-gold);margin:.2rem 0">GHS 50</div>
                <div style="font-size:.68rem;color:#64748b">25 tags + case</div>
              </div>
            </label>
          </div>
        </div>

        <!-- What's included -->
        <div class="rounded-3 p-3 mb-3" style="background:#f8faff;border:1px solid #e8eeff;font-size:.78rem">
          <div class="fw-semibold mb-1 text-primary"><i class="bi bi-check-circle-fill me-1"></i>What you get</div>
          <div class="d-flex flex-wrap gap-2">
            <span><i class="bi bi-droplet-fill text-primary me-1" style="font-size:.7rem"></i>Waterproof vinyl</span>
            <span><i class="bi bi-shield-check text-primary me-1" style="font-size:.7rem"></i>Scratch-resistant</span>
            <span><i class="bi bi-sticky-fill text-primary me-1" style="font-size:.7rem"></i>Adhesive-backed</span>
            <span><i class="bi bi-truck text-primary me-1" style="font-size:.7rem"></i>Delivered to you</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.8rem">Delivery Address</label>
          <textarea class="form-control rounded-3" id="print-address" rows="2" placeholder="Enter your delivery address…" style="font-size:.83rem;resize:none"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:.8rem">Phone for delivery</label>
          <input type="tel" class="form-control rounded-3" id="print-phone" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="0241234567" style="font-size:.83rem">
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient rounded-pill fw-bold px-4" onclick="orderPremiumPrint()">
          <i class="bi bi-bag-check me-1"></i>Place Print Order
        </button>
      </div>
    </div>
  </div>
</div>

<?php if(!empty($qr_tags)): ?>
<script src="<?= base_url ?>assets/vendor/qrcode/qrcode.min.js"></script>
<?php endif; ?>

<script>
var _qr_tags = <?= json_encode(array_map(fn($t)=>['id'=>$t['id'],'code'=>$t['tag_code'],'label'=>$t['label']?:$t['tag_code'],'url'=>base_url.'?page=tag&code='.$t['tag_code']], $qr_tags)) ?>;
var _selected_plan = 'standard';

$(function(){
  // Generate QR codes
  _qr_tags.forEach(function(t){
    var el = document.getElementById('qrtag-'+t.id);
    if(!el) return;
    new QRCode(el, {text:t.url, width:100, height:100, colorDark:'#0f172a', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});
  });
});

function _getSrc(id){
  var el=document.getElementById('qrtag-'+id); if(!el) return '';
  var c=el.querySelector('canvas'),i=el.querySelector('img');
  return c?c.toDataURL():(i?i.src:'');
}
function printTag(id,label,url){ var s=_getSrc(id); if(!s){alert_toast('QR not ready.','warning');return;} _openPrint([{src:s,label,url}]); }
function printAllTags(){ var a=[]; _qr_tags.forEach(function(t){ var s=_getSrc(t.id); if(s)a.push({src:s,label:t.label,url:t.url}); }); if(!a.length){alert_toast('QR codes not ready.','warning');return;} _openPrint(a); }

function _openPrint(items){
  var cards=items.map(function(t){
    return '<div class="tag"><img src="'+t.src+'"><h3>SMART ASSET FINDER</h3><p>Found this? Scan to return it.</p><b>'+String(t.label).substring(0,36)+'</b></div>';
  }).join('');
  var win=window.open('','_blank');
  win.document.write(
    '<!DOCTYPE html><html><head><title>QR Tags</title>'
    +'<style>body{margin:16px;font-family:"Space Grotesk",sans-serif;background:#fff}'
    +'.grid{display:flex;flex-wrap:wrap;gap:14px}'
    +'.tag{border:2px dashed #c7d2fe;border-radius:16px;padding:14px 12px;text-align:center;width:168px}'
    +'.tag img{width:136px;height:136px;display:block;margin:0 auto 8px}'
    +'.tag h3{font-size:9px;font-weight:800;margin:0 0 3px;color:#4f46e5;letter-spacing:.06em}'
    +'.tag p{font-size:8px;color:#64748b;margin:0 0 5px}'
    +'.tag b{font-size:9.5px;color:#0f172a;word-break:break-word}'
    +'@media print{body{margin:4mm}}'
    +'</style></head><body>'
    +'<div class="grid">'+cards+'</div>'
    +'<scr'+'ipt>window.onload=function(){window.print();}<\/scr'+'ipt>'
    +'</body></html>'
  );
  win.document.close();
}

function editTag(id,cur){
  _prompt('Name this tag','e.g. My Laptop, House Keys',cur,function(v){
    if(v===null) return; v=v.substring(0,100);
    $.ajax({url:_base_url_+'classes/Master.php?f=update_qr_tag',method:'POST',dataType:'json',
      data:{id,label:v},
      success:function(r){
        if(r.status==='success'){
          var el=document.getElementById('lbl-'+id);
          if(el) el.innerHTML = v ? _esc(v) : '<span style="color:#94a3b8;font-style:italic;font-weight:400">Unlabelled</span>';
          _qr_tags.forEach(function(t){if(t.id===id)t.label=v||t.code;});
          alert_toast('Tag saved.','success');
        } else alert_toast(r.msg,'error');
      }
    });
  });
}
function _esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Premium print plan selector
function selectPlan(radio){
  _selected_plan = radio.value;
  document.querySelectorAll('.plan-inner').forEach(function(el){
    var isPicked = el.dataset.plan === _selected_plan;
    el.style.borderColor = isPicked ? 'var(--saf-primary)' : '#e2e8f0';
    el.style.background  = isPicked ? 'rgba(79,70,229,.04)' : '';
  });
}
// Init default selected
document.querySelectorAll('.plan-inner[data-plan="standard"]').forEach(function(el){
  el.style.borderColor='var(--saf-primary)'; el.style.background='rgba(79,70,229,.04)';
});

function orderPremiumPrint(){
  var addr  = document.getElementById('print-address').value.trim();
  var phone = document.getElementById('print-phone').value.trim();
  var plans = {basic:'Basic – 3 Tags (GHS 10)',standard:'Standard – 10 Tags (GHS 25)',premium:'Premium – 25 Tags + Case (GHS 50)'};
  var prices = {basic:10,standard:25,premium:50};
  if(!addr){ alert_toast('Please enter your delivery address.','warning'); return; }
  if(!phone){ alert_toast('Please enter a phone number.','warning'); return; }
  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Master.php?f=place_order',
    method: 'POST', dataType: 'json',
    data: {
      product_name: 'Professional QR Tag Print – '+plans[_selected_plan],
      qty: 1,
      total_amount: prices[_selected_plan],
      payment_method: 'momo',
      delivery_address: addr,
      contact_phone: phone
    },
    success: function(r){
      end_loader();
      if(r.status==='success'){
        bootstrap.Modal.getOrCreateInstance(document.getElementById('premium-print-modal')).hide();
        alert_toast('Print order placed! Ref: '+(r.order_ref||'')+'  We\'ll contact you to arrange delivery.','success');
      } else { alert_toast(r.msg||'Error placing order.','error'); }
    }
  });
}
</script>
