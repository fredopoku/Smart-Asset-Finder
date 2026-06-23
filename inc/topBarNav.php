<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="container-xl d-flex justify-content-between align-items-center px-4 w-100">

    <!-- Logo -->
    <a href="<?= base_url ?>" class="logo d-flex align-items-center gap-2 text-decoration-none">
      <img src="<?= validate_image($_settings->info('logo')) ?>" alt="Smart Asset Finder">
      <span class="logo-text d-none d-sm-inline">Smart Asset Finder</span>
    </a>

    <!-- Desktop nav -->
    <nav class="header-nav me-auto d-none d-lg-flex ms-4">
      <ul class="d-flex align-items-center h-100 mb-0 ps-2 gap-1 list-unstyled">
        <li><a href="<?= base_url ?>" class="nav-link <?= ($page??'')==='home'||($page??'')==''?'active':'' ?>">Home</a></li>
        <li><a href="<?= base_url ?>?page=items" class="nav-link <?= ($page??'')==='items'?'active':'' ?>">Browse Items</a></li>
        <li><a href="<?= base_url ?>?page=found" class="nav-link <?= ($page??'')==='found'?'active':'' ?>">Found Something</a></li>
        <li><a href="<?= base_url ?>?page=lost" class="nav-link <?= ($page??'')==='lost'?'active':'' ?>">Lost Something</a></li>
        <li>
          <a href="<?= base_url ?>?page=shop" class="nav-link <?= ($page??'')==='shop'?'active':'' ?>" style="font-weight:600">
            <i class="bi bi-bag me-1" style="font-size:.78rem"></i>Shop
          </a>
        </li>
        <li><a href="<?= base_url ?>?page=about" class="nav-link <?= ($page??'')==='about'?'active':'' ?>">About</a></li>
        <li><a href="<?= base_url ?>?page=contact" class="nav-link <?= ($page??'')==='contact'?'active':'' ?>">Contact</a></li>
      </ul>
    </nav>

    <!-- Right side -->
    <div class="d-flex align-items-center gap-2">

      <!-- Search -->
      <button class="btn btn-sm bell-btn" type="button" id="header-search-toggle"
        style="background:none;border:none;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .2s">
        <i class="bi bi-search" style="font-size:.95rem"></i>
      </button>

      <?php if(isset($_SESSION['pub_userdata'])):
        $uid = (int)$_SESSION['pub_userdata']['id'];
        $notif_count = $conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];
      ?>
      <!-- Notification bell -->
      <div class="dropdown">
        <button class="btn btn-sm bell-btn position-relative" type="button" id="notifBell" data-bs-toggle="dropdown"
          style="background:none;border:none;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%">
          <i class="bi bi-bell" style="font-size:.95rem"></i>
          <?php if($notif_count > 0): ?>
            <span class="position-absolute" style="top:5px;right:5px;width:7px;height:7px;background:#ef4444;border-radius:50%;border:1.5px solid transparent"></span>
          <?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" id="notifDropdown"
          style="border-radius:16px;min-width:320px;max-height:400px;overflow-y:auto;margin-top:8px">
          <div class="px-3 py-2 d-flex justify-content-between align-items-center sticky-top bg-white border-bottom" style="border-radius:16px 16px 0 0">
            <span class="fw-bold" style="font-size:.87rem;font-family:'Space Grotesk',sans-serif">Notifications</span>
            <?php if($notif_count > 0): ?>
            <span class="badge rounded-pill" style="background:rgba(79,70,229,.1);color:#4f46e5;font-size:.7rem"><?= $notif_count ?> new</span>
            <?php endif; ?>
          </div>
          <div id="notif-list" class="p-2">
            <div class="text-center text-muted py-4" style="font-size:.83rem"><i class="bi bi-arrow-repeat spin me-1"></i>Loading…</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if(isset($_SESSION['pub_userdata'])): ?>
        <!-- User menu -->
        <div class="dropdown">
<?php
  $_nav_av  = !empty($_SESSION['pub_userdata']['avatar']) && is_file(base_app.$_SESSION['pub_userdata']['avatar'])
              ? base_url.$_SESSION['pub_userdata']['avatar'] : '';
  $_nav_ini = strtoupper(substr($_SESSION['pub_userdata']['firstname'],0,1));
?>
          <button class="btn btn-sm user-menu-btn rounded-pill d-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown"
            style="font-size:.83rem;font-weight:600;height:36px;border-radius:50px">
            <?php if($_nav_av): ?>
            <img src="<?= $_nav_av ?>" class="user-avatar-img" style="width:24px;height:24px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">
            <?php else: ?>
            <span style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#fff;font-weight:800;flex-shrink:0" class="user-first-init">
              <?= $_nav_ini ?>
            </span>
            <?php endif; ?>
            <span class="d-none d-md-inline user-first"><?= htmlspecialchars($_SESSION['pub_userdata']['firstname']) ?></span>
            <i class="bi bi-chevron-down" style="font-size:.6rem;opacity:.6"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:14px;min-width:210px;margin-top:8px">
            <li class="px-3 pt-2 pb-3 d-flex align-items-center gap-3" style="border-bottom:1px solid #f1f5f9;margin-bottom:.35rem">
              <?php if($_nav_av): ?>
              <img src="<?= $_nav_av ?>" class="user-avatar-img" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">
              <?php else: ?>
              <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;font-weight:800;flex-shrink:0">
                <?= $_nav_ini ?>
              </div>
              <?php endif; ?>
              <div>
                <div class="fw-bold" style="font-size:.85rem;font-family:'Space Grotesk',sans-serif;color:#0f172a;line-height:1.2">
                  <?= htmlspecialchars($_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname']) ?>
                </div>
                <div class="text-muted" style="font-size:.72rem;margin-top:1px"><?= htmlspecialchars($_SESSION['pub_userdata']['email']) ?></div>
              </div>
            </li>
            <li><a class="dropdown-item py-2" href="<?= base_url ?>?page=profile"><i class="bi bi-person-circle me-2 text-muted" style="font-size:.85rem"></i>My Profile</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2" href="<?= base_url ?>?page=my-items"><i class="bi bi-collection me-2 text-muted" style="font-size:.85rem"></i>My Submissions</a></li>
            <li><a class="dropdown-item py-2" href="<?= base_url ?>?page=my-orders"><i class="bi bi-bag-check me-2 text-muted" style="font-size:.85rem"></i>My Orders</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?= base_url ?>classes/Login.php?f=logout_user"><i class="bi bi-box-arrow-left me-2" style="font-size:.85rem"></i>Sign Out</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= base_url ?>?page=login" class="btn btn-sm nav-btn-login rounded-pill px-3" style="font-size:.83rem;font-weight:600;height:36px;display:flex;align-items:center">Sign In</a>
        <a href="<?= base_url ?>?page=register" class="btn btn-sm nav-btn-register rounded-pill px-3 d-none d-sm-flex" style="font-size:.83rem;font-weight:600;height:36px;align-items:center">Get Started</a>
      <?php endif; ?>

      <!-- Mobile hamburger -->
      <button class="btn btn-sm d-lg-none bell-btn" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav"
        style="background:none;border:none;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%">
        <i class="bi bi-list" style="font-size:1.2rem"></i>
      </button>
    </div>
  </div>

  <!-- Mobile nav -->
  <div class="collapse w-100" id="mobileNav"
    style="position:absolute;top:100%;left:0;z-index:999;background:rgba(3,7,18,.97);backdrop-filter:blur(24px);border-top:1px solid rgba(255,255,255,.07)">
    <ul class="list-unstyled px-4 py-3 mb-0">
      <li><a href="<?= base_url ?>" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">Home</a></li>
      <li><a href="<?= base_url ?>?page=items" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">Browse Items</a></li>
      <li><a href="<?= base_url ?>?page=found" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">Found Something</a></li>
      <li><a href="<?= base_url ?>?page=lost" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">Lost Something</a></li>
      <li><a href="<?= base_url ?>?page=shop" class="d-block py-2 text-decoration-none" style="color:#f59e0b;font-size:.9rem;font-weight:600"><i class="bi bi-bag me-2"></i>Shop</a></li>
      <li><a href="<?= base_url ?>?page=about" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">About</a></li>
      <li><a href="<?= base_url ?>?page=contact" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500">Contact</a></li>
      <?php if(isset($_SESSION['pub_userdata'])): ?>
      <li><a href="<?= base_url ?>?page=profile" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
      <li><a href="<?= base_url ?>?page=my-orders" class="d-block py-2 text-decoration-none" style="color:rgba(255,255,255,.75);font-size:.9rem;font-weight:500"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
      <?php else: ?>
      <li class="pt-3 pb-1 d-flex gap-2">
        <a href="<?= base_url ?>?page=login" class="btn btn-sm rounded-pill px-4 fw-semibold" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">Sign In</a>
        <a href="<?= base_url ?>?page=register" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">Get Started</a>
      </li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- Slide-down search -->
  <div id="header-search-bar" class="w-100" style="position:absolute;top:100%;left:0;z-index:998;display:none;background:rgba(3,7,18,.96);backdrop-filter:blur(20px);border-top:1px solid rgba(255,255,255,.08);padding:.875rem 0">
    <form action="<?= base_url ?>?page=search" method="GET" class="container-xl px-4 d-flex gap-2">
      <input type="hidden" name="page" value="search">
      <input type="text" name="q" class="form-control rounded-pill" placeholder="Search items…" autofocus
        style="background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.15);color:#fff">
      <button type="submit" class="btn btn-gradient rounded-pill px-4">Search</button>
    </form>
  </div>
</header>

<?php if(isset($_SESSION['pub_userdata']) && empty($_SESSION['pub_userdata']['email_verified'])): ?>
<?php
$_dev_verify_url = '';
if(APP_ENV !== 'production'){
  $uid_v = (int)$_SESSION['pub_userdata']['id'];
  $tok_stmt = $conn->prepare("SELECT verification_token FROM registered_users WHERE id=? LIMIT 1");
  $tok_stmt->bind_param('i', $uid_v);
  $tok_stmt->execute();
  $tok_row = $tok_stmt->get_result()->fetch_assoc();
  $tok_stmt->close();
  if(!empty($tok_row['verification_token'])){
    $_dev_verify_url = base_url.'?page=verify-email&token='.$tok_row['verification_token'];
  }
}
?>
<div id="verify-banner" style="background:linear-gradient(90deg,#fefce8,#fffbeb);border-bottom:1px solid #fde047;padding:8px 0">
  <div class="container-xl px-4">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <div class="d-flex align-items-center gap-2" style="font-size:.82rem;color:#713f12">
        <i class="bi bi-envelope-exclamation-fill text-warning flex-shrink-0"></i>
        <span>Please verify your email address to unlock all features.</span>
      </div>
      <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <?php if($_dev_verify_url): ?>
        <a href="<?= $_dev_verify_url ?>" class="btn btn-sm fw-semibold" style="background:#fde047;color:#713f12;border:none;border-radius:20px;padding:3px 14px;font-size:.8rem">Verify now</a>
        <?php else: ?>
        <button class="btn btn-sm fw-semibold" id="resend-verify-btn" style="background:#fde047;color:#713f12;border:none;border-radius:20px;padding:3px 14px;font-size:.8rem">Resend email</button>
        <?php endif; ?>
        <button type="button" class="btn-close btn-close-sm" id="dismiss-verify-banner" aria-label="Dismiss" style="opacity:.4;font-size:.62rem"></button>
      </div>
    </div>
    <div id="resend-verify-msg" style="font-size:.78rem;margin-top:4px;display:none"></div>
  </div>
</div>
<?php endif; ?>

<script>
$(function(){
  /* ── Nav transparency on scroll ── */
  var $header = $('#header');
  var hasHero  = $('#site-header').length > 0;

  function updateNav(){
    if(!hasHero || $(window).scrollTop() > 30){
      $header.addClass('scrolled');
    } else {
      $header.removeClass('scrolled');
    }
  }
  updateNav();
  $(window).on('scroll.nav', updateNav);

  /* ── Search bar ── */
  $('#header-search-toggle').on('click', function(){
    $('#header-search-bar').slideToggle(180);
    setTimeout(function(){ $('#header-search-bar input[name="q"]').focus(); }, 200);
  });

  /* ── Verify banner ── */
  $('#dismiss-verify-banner').on('click', function(){ $('#verify-banner').slideUp(200); });
  $('#resend-verify-btn').on('click', function(){
    var $btn = $(this);
    $btn.prop('disabled', true).text('Sending…');
    $.getJSON(_base_url_+'classes/Login.php?f=resend_verification', function(r){
      var ok = r.status === 'success';
      $('#resend-verify-msg').text(r.msg).css('color', ok ? '#166534' : '#7f1d1d').show();
      if(ok) $btn.text('Sent!');
      else { $btn.prop('disabled', false).text('Resend email'); }
    });
  });

  /* ── Notification bell ── */
  var notifLoaded = false;
  $('#notifBell').closest('.dropdown').on('show.bs.dropdown', function(){
    if(notifLoaded) return;
    notifLoaded = true;
    $.getJSON(_base_url_+'classes/Master.php?f=get_notifications', function(r){
      if(!r.data || r.data.length === 0){
        $('#notif-list').html('<div class="text-center text-muted py-4" style="font-size:.83rem"><i class="bi bi-bell-slash fs-3 d-block mb-2 opacity-25"></i>No notifications yet</div>');
        return;
      }
      var icons = {success:'check-circle-fill text-success',info:'info-circle-fill text-primary',warning:'exclamation-triangle-fill text-warning',danger:'x-circle-fill text-danger'};
      var html = r.data.map(function(n){
        var icon = icons[n.type] || icons.info;
        var ago  = n.created_at ? new Date(n.created_at).toLocaleDateString() : '';
        var unread = n.is_read === '0' || n.is_read === 0;
        return '<a href="'+(n.link||'#')+'" class="d-flex gap-2 align-items-start text-decoration-none p-2 rounded-3 notif-item" style="'+(unread?'background:#f5f3ff;':'')+'">'
          +'<i class="bi bi-'+icon+' mt-1 flex-shrink-0" style="font-size:.82rem"></i>'
          +'<div><div style="font-size:.8rem;color:#0f172a;line-height:1.4">'+n.message+'</div>'
          +'<div style="font-size:.7rem;color:#94a3b8;margin-top:2px">'+ago+'</div></div></a>';
      }).join('');
      $('#notif-list').html(html);
    });
  });
});
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { display:inline-block; animation:spin .8s linear infinite; }
.notif-item:hover { background:#f8f7ff !important; }
#header-search-bar input::placeholder { color: rgba(255,255,255,.38) !important; }
</style>
