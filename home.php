<?php
$cover_url    = validate_image($_settings->info('cover'));
$is_logged_in = isset($_SESSION['pub_userdata']);
?>

<!-- ════════════════ HERO ════════════════ -->
<div class="col-12 px-0" id="hero-wrapper">
  <div id="site-header">

    <!-- Animated orb blobs -->
    <div class="saf-orb saf-orb-1"></div>
    <div class="saf-orb saf-orb-2"></div>
    <div class="saf-orb saf-orb-3"></div>

    <div class="container-xl px-4 w-100">
      <div class="row align-items-center g-5" style="min-height:calc(100vh - 68px);padding:5rem 0">

        <!-- ── LEFT: Copy ── -->
        <div class="col-lg-6">
          <div class="header-content">

            <!-- Label -->
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(129,140,248,.75);margin-bottom:1.75rem">
              The Smart Lost &amp; Found Platform &nbsp;·&nbsp; Powered by AI
            </div>

            <!-- Headline -->
            <h1 class="siteTitle" style="font-size:clamp(2.6rem,6vw,4.6rem);line-height:1.04;margin-bottom:1.5rem">
              Lost something?<br><span class="text-gradient">Someone found it.</span>
            </h1>

            <!-- Sub-headline -->
            <p class="site-tagline" style="max-width:430px;margin-bottom:1.2rem">
              Search our community database of lost &amp; found items worldwide.
              Report what you lost. Report what you found. Our AI matches them instantly.
            </p>

            <!-- Hardware upsell — small, secondary -->
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:2.2rem">
              <span style="font-size:.73rem;color:rgba(255,255,255,.5);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);padding:.3rem .7rem;border-radius:50px;font-weight:600">
                <i class="bi bi-qr-code me-1"></i>QR Tags
              </span>
              <span style="font-size:.73rem;color:rgba(255,255,255,.5);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);padding:.3rem .7rem;border-radius:50px;font-weight:600">
                <i class="bi bi-wifi me-1"></i>NFC
              </span>
              <span style="font-size:.73rem;color:rgba(255,255,255,.5);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);padding:.3rem .7rem;border-radius:50px;font-weight:600">
                <i class="bi bi-geo-alt-fill me-1"></i>GPS Tracker
              </span>
              <span style="font-size:.73rem;color:rgba(255,255,255,.5);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);padding:.3rem .7rem;border-radius:50px;font-weight:600">
                <i class="bi bi-airplane me-1"></i>Travel Ready
              </span>
            </div>

            <!-- CTAs -->
            <div class="d-flex align-items-center gap-3 flex-wrap hero-btns">
              <?php if(!$is_logged_in): ?>
              <a href="<?= base_url ?>?page=items"
                 class="btn btn-gradient rounded-pill fw-bold"
                 style="padding:.9rem 2.4rem;font-size:.95rem;letter-spacing:-.01em">
                Search Items &nbsp;→
              </a>
              <a href="<?= base_url ?>?page=register"
                 style="color:rgba(255,255,255,.45);font-size:.875rem;text-decoration:none;font-weight:500;border-bottom:1px solid rgba(255,255,255,.18);padding-bottom:2px;transition:color .2s"
                 onmouseover="this.style.color='rgba(255,255,255,.75)'" onmouseout="this.style.color='rgba(255,255,255,.45)'">
                Create free account
              </a>
              <?php else: ?>
              <a href="<?= base_url ?>?page=items"
                 class="btn btn-gradient rounded-pill fw-bold"
                 style="padding:.9rem 2.4rem;font-size:.95rem">
                Browse Items &nbsp;→
              </a>
              <a href="<?= base_url ?>?page=lost"
                 style="color:rgba(255,255,255,.45);font-size:.875rem;text-decoration:none;font-weight:500;border-bottom:1px solid rgba(255,255,255,.18);padding-bottom:2px">
                Report lost item
              </a>
              <?php endif; ?>
            </div>

            <!-- Value pills — no raw DB numbers -->
            <div class="d-flex gap-3 flex-wrap hero-nums" style="margin-top:3.5rem">
              <div class="d-flex align-items-center gap-2" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:50px;padding:.4rem 1rem">
                <i class="bi bi-lightning-charge-fill" style="color:#a5b4fc;font-size:.8rem"></i>
                <span style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:600">AI-powered matching</span>
              </div>
              <div class="d-flex align-items-center gap-2" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:50px;padding:.4rem 1rem">
                <i class="bi bi-shield-check-fill" style="color:#6ee7b7;font-size:.8rem"></i>
                <span style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:600">Anti-theft verified</span>
              </div>
              <div class="d-flex align-items-center gap-2" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:50px;padding:.4rem 1rem">
                <i class="bi bi-globe2" style="color:#7dd3fc;font-size:.8rem"></i>
                <span style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:600">Works worldwide</span>
              </div>
            </div>

          </div>
        </div>

        <!-- ── RIGHT: Product card — shows exactly what a finder sees ── -->
        <div class="col-lg-6 d-flex justify-content-center align-items-center">
          <div class="hero-product-wrap" style="perspective:1200px">

            <!-- The card: a real UI mockup of the QR scan result page -->
            <div class="hero-pc">

              <!-- Status bar -->
              <div class="hero-pc-header">
                <div class="hero-pc-scan-badge">
                  <div class="hero-pc-scan-dot"></div>
                  <i class="bi bi-qr-code-scan"></i>
                  QR Tag Scanned
                </div>
                <span style="font-size:.65rem;color:rgba(255,255,255,.28)">Just now</span>
              </div>

              <!-- Body -->
              <div class="hero-pc-body">

                <!-- Lost item info -->
                <div class="hero-pc-item">
                  <div class="hero-pc-item-icon"><i class="bi bi-laptop"></i></div>
                  <div style="min-width:0">
                    <div class="hero-pc-item-name">MacBook Pro 14"</div>
                    <div class="hero-pc-item-meta"><i class="bi bi-geo-alt me-1"></i>Found at City Airport</div>
                  </div>
                  <div class="hero-pc-lost-badge">Lost</div>
                </div>

                <div class="hero-pc-divider"></div>

                <!-- Owner identity (private-safe) -->
                <div class="hero-pc-owner">
                  <div class="hero-pc-owner-av">FA</div>
                  <div>
                    <div class="hero-pc-owner-name">Frederick A.</div>
                    <div class="hero-pc-owner-sub">
                      <i class="bi bi-patch-check-fill me-1" style="color:#818cf8"></i>Verified owner
                    </div>
                  </div>
                </div>

                <!-- Contact actions -->
                <div class="hero-pc-btns">
                  <button class="hero-pc-btn hero-pc-btn-call"><i class="bi bi-telephone-fill"></i>Call Owner</button>
                  <button class="hero-pc-btn hero-pc-btn-wa"><i class="bi bi-whatsapp"></i>WhatsApp</button>
                </div>

              </div>

              <!-- Footer trust line -->
              <div class="hero-pc-footer">
                <span><i class="bi bi-shield-check"></i>No login required</span>
                <span><i class="bi bi-lightning-charge"></i>Returns in minutes</span>
              </div>

            </div>

            <!-- Floating: owner got the alert -->
            <div class="hero-notif">
              <div class="hero-notif-icon"><i class="bi bi-check2-all"></i></div>
              <div>
                <div class="hero-notif-title">Owner notified</div>
                <div class="hero-notif-sub">SMS sent · 2 seconds ago</div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

    <div class="hero-scroll-arrow">
      <span>Discover more</span>
      <i class="bi bi-chevron-double-down"></i>
    </div>
  </div>
</div>

<!-- ══ TRUST BAR — feature guarantees ══ -->
<div style="background:#f8faff;border-top:1px solid #eef2ff;border-bottom:1px solid #eef2ff">
  <div class="container-xl px-4">
    <div class="d-flex align-items-center justify-content-center gap-5 flex-wrap" style="padding:1.25rem 0">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-search-heart" style="color:#4f46e5;font-size:.95rem"></i>
        <span style="font-size:.75rem;color:#475569;font-weight:600">Free to search — no account needed</span>
      </div>
      <div style="width:1px;height:20px;background:#e2e8f0"></div>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-robot" style="color:#7c3aed;font-size:.95rem"></i>
        <span style="font-size:.75rem;color:#475569;font-weight:600">AI matches lost &amp; found automatically</span>
      </div>
      <div style="width:1px;height:20px;background:#e2e8f0"></div>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shield-check" style="color:#059669;font-size:.95rem"></i>
        <span style="font-size:.75rem;color:#475569;font-weight:600">Anti-theft ownership verification</span>
      </div>
      <div style="width:1px;height:20px;background:#e2e8f0"></div>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-globe2" style="color:#0ea5e9;font-size:.95rem"></i>
        <span style="font-size:.75rem;color:#475569;font-weight:600">Works anywhere in the world</span>
      </div>
    </div>
  </div>
</div>

<!-- ══ HOW IT WORKS ══ -->
<div style="background:#fff;padding:6rem 0 5rem">
  <div class="container-xl px-4">
    <div class="text-center mb-5">
      <div class="section-label">How it works</div>
      <h2 class="fw-bold mt-2" style="font-size:clamp(1.8rem,4vw,2.8rem);letter-spacing:-.03em;color:#0f172a">
        Simple. Fast.<br>No app needed.
      </h2>
    </div>
    <div class="row g-0 position-relative" style="max-width:900px;margin:0 auto">
      <div class="d-none d-md-block" style="position:absolute;top:52px;left:calc(16.66% + 24px);right:calc(16.66% + 24px);height:2px;background:linear-gradient(90deg,#4f46e5,#7c3aed,#10b981);z-index:0;opacity:.3"></div>
      <?php
      $steps = [
        ['bi-search-heart','Search first','Someone lost a phone near you? Search our map. Someone found a passport? It is in here. Start with a search — no account needed.','#4f46e5','rgba(79,70,229,.08)'],
        ['bi-file-earmark-plus','Report it','Lost something? Report it with a photo, description, and location. Found something? Submit it here. AI starts matching immediately.','#7c3aed','rgba(124,58,237,.08)'],
        ['bi-people-fill','Connect & return','When a match is found, both parties are notified. The claimant answers ownership questions. Verified? They are connected.','#10b981','rgba(16,185,129,.08)'],
      ];
      foreach($steps as $i => $s): ?>
      <div class="col-md-4 text-center" style="padding:0 1.5rem">
        <div style="position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;border-radius:50%;background:<?= $s[4] ?>;border:2px solid <?= $s[3] ?>22;margin-bottom:1.5rem">
          <i class="bi <?= $s[0] ?>" style="font-size:1.6rem;color:<?= $s[3] ?>"></i>
          <span style="position:absolute;top:-6px;right:-6px;width:22px;height:22px;border-radius:50%;background:<?= $s[3] ?>;color:#fff;font-size:.65rem;font-weight:800;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif"><?= $i+1 ?></span>
        </div>
        <h5 class="fw-bold mb-2" style="color:#0f172a;font-family:'Space Grotesk',sans-serif"><?= $s[1] ?></h5>
        <p style="color:#64748b;font-size:.875rem;line-height:1.65;margin:0"><?= $s[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= base_url ?>?page=items" class="btn btn-outline-primary rounded-pill fw-bold px-5 me-2" style="padding:.85rem 2.5rem">
        Search Items →
      </a>
      <a href="<?= base_url ?>?page=lost" class="btn btn-danger rounded-pill fw-bold" style="padding:.85rem 2.5rem">
        Report Lost Item
      </a>
      <div style="margin-top:.85rem;font-size:.72rem;color:#94a3b8">Free to use &nbsp;·&nbsp; No app needed &nbsp;·&nbsp; Works anywhere in the world</div>
    </div>
  </div>
</div>

<!-- ══ FEATURES — platform + hardware ══ -->
<div style="background:#f8faff;padding:6rem 0">
  <div class="container-xl px-4">
    <div class="text-center mb-5">
      <div class="section-label">Why Smart Asset Finder</div>
      <h2 class="fw-bold mt-2" style="font-size:clamp(1.8rem,4vw,2.8rem);letter-spacing:-.03em;color:#0f172a">
        More than a lost &amp; found board.
      </h2>
      <p class="text-muted mt-2" style="max-width:500px;margin:0 auto;font-size:.9rem">Search and report for free. Upgrade with hardware that makes recovery instant — wherever you are in the world.</p>
    </div>

    <div class="row g-4">
      <?php
      $features = [
        ['bi-robot','AI auto-matching','When a found item is reported, our AI instantly scans all lost reports and notifies owners of likely matches. No admin needed. No waiting.','#4f46e5','rgba(79,70,229,.06)','rgba(79,70,229,.15)'],
        ['bi-shield-lock-fill','Anti-theft claim verification','Claimants must answer private security questions you set when reporting. AI scores their answers. Thieves can\'t pass — real owners can.','#dc2626','rgba(220,38,38,.06)','rgba(220,38,38,.15)'],
        ['bi-qr-code','QR + NFC hardware tags','Stick a SAF tag on anything. Finder scans or taps it — no app, no account. Instantly connected to you. Available in sticker, keyring, and wristband.','#7c3aed','rgba(124,58,237,.06)','rgba(124,58,237,.15)'],
        ['bi-geo-alt-fill','Real-time GPS tracking','Upgrade to the SAF GPS Tracker — a coin-sized puck that shows live location on your dashboard. Never lose a bag, car, or shipment again.','#0ea5e9','rgba(14,165,233,.06)','rgba(14,165,233,.15)'],
        ['bi-airplane-fill','Built for travellers','Lost something while on the move? Reports are visible across borders. Whether you are at an airport, a hotel, or a transit hub — finders and owners connect worldwide.','#f59e0b','rgba(245,158,11,.06)','rgba(245,158,11,.15)'],
        ['bi-map-fill','Live map view','Browse lost &amp; found items on an interactive map. See what\'s been reported near you, at the airport, in your neighbourhood — in real time.','#10b981','rgba(16,185,129,.06)','rgba(16,185,129,.15)'],
      ];
      foreach($features as $f): ?>
      <div class="col-lg-4 col-md-6">
        <div style="background:#fff;border:1px solid #f1f5f9;border-radius:20px;padding:1.75rem;height:100%;display:flex;gap:1.1rem;transition:box-shadow .2s,transform .2s" onmouseover="this.style.boxShadow='0 12px 40px rgba(0,0,0,.08)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
          <div style="width:48px;height:48px;border-radius:13px;background:<?= $f[5] ?>;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:<?= $f[3] ?>;flex-shrink:0">
            <i class="bi <?= $f[0] ?>"></i>
          </div>
          <div>
            <div class="fw-bold mb-1" style="font-size:.95rem;color:#0f172a;font-family:'Space Grotesk',sans-serif"><?= $f[1] ?></div>
            <div style="color:#64748b;font-size:.825rem;line-height:1.6"><?= $f[2] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
      <a href="<?= base_url ?>?page=shop" class="btn btn-gradient rounded-pill fw-bold px-5" style="padding:.85rem 2.5rem">
        See hardware options →
      </a>
    </div>
  </div>
</div>

<!-- ══ WHAT WE TRACK — showcase reel ══ -->
<?php
$browse = base_url.'?page=items';
$reel_base = base_url.'assets/img/reel/';
// Row 1 — phones, laptops, bags, watches, wallets
$row1 = [
  ['phone.jpg',      'Smartphone'],
  ['laptop.jpg',     'Laptop'],
  ['bag.jpg',        'Bag & Backpack'],
  ['watch.jpg',      'Wristwatch'],
  ['wallet.jpg',     'Wallet'],
  ['tablet.jpg',     'Tablet'],
  ['luggage.jpg',    'Luggage'],
];
// Row 2 — passports, keys, cameras, jewelry, earphones
$row2 = [
  ['passport.jpg',   'Passport'],
  ['keys.jpg',       'Keys'],
  ['camera.jpg',     'Camera'],
  ['jewelry.jpg',    'Jewellery'],
  ['headphones.jpg', 'Headphones'],
  ['airpods.jpg',    'Earphones'],
];
?>
<div style="background:#050814;position:relative;overflow:hidden;padding:6rem 0">
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:60px 60px;pointer-events:none"></div>
  <div style="position:absolute;inset:0;background:linear-gradient(90deg,#050814 0%,transparent 10%,transparent 90%,#050814 100%);z-index:2;pointer-events:none"></div>

  <div class="text-center mb-5" style="position:relative;z-index:3">
    <div style="font-size:.68rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:rgba(129,140,248,.7)">What you can search for</div>
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;color:#fff;letter-spacing:-.03em;margin-top:.5rem;margin-bottom:.5rem">
      Lost it? Someone here might have it.
    </h2>
    <p style="color:rgba(255,255,255,.42);font-size:.9rem;max-width:480px;margin:0 auto">Phones, laptops, wallets, passports, watches and more — reported and searchable by real people worldwide.</p>
  </div>

  <!-- Row 1 — scrolls left -->
  <div style="position:relative;z-index:1;overflow:hidden;margin-bottom:14px">
    <div class="saf-reel-track">
      <?php foreach(array_merge($row1,$row1) as $c): ?>
      <div class="saf-reel-card">
        <img src="<?= $reel_base.$c[0] ?>" alt="<?= htmlspecialchars($c[1]) ?>" loading="lazy">
        <div class="saf-reel-label"><?= htmlspecialchars($c[1]) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- Row 2 — scrolls right -->
  <div style="position:relative;z-index:1;overflow:hidden;margin-bottom:3.5rem">
    <div class="saf-reel-track saf-reel-rev">
      <?php foreach(array_merge($row2,$row2) as $c): ?>
      <div class="saf-reel-card">
        <img src="<?= $reel_base.$c[0] ?>" alt="<?= htmlspecialchars($c[1]) ?>" loading="lazy">
        <div class="saf-reel-label"><?= htmlspecialchars($c[1]) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Single CTA -->
  <div class="text-center" style="position:relative;z-index:3">
    <a href="<?= $browse ?>" class="btn btn-gradient rounded-pill fw-bold" style="padding:.9rem 2.75rem;font-size:.95rem">
      Browse reported items →
    </a>
    <div style="margin-top:.85rem;font-size:.7rem;color:rgba(255,255,255,.25)">Lost something? Found something? This is where it gets resolved.</div>
  </div>
</div>

<!-- ══ WHY IT MATTERS ══ -->
<div style="background:#0f172a;padding:6rem 0">
  <div class="container-xl px-4">

    <div class="text-center mb-5">
      <div class="section-label" style="background:rgba(129,140,248,.15);border-color:rgba(129,140,248,.25);color:#a5b4fc">The problem we solve</div>
      <h2 class="fw-bold mt-2" style="font-size:clamp(1.8rem,4vw,2.8rem);letter-spacing:-.03em;color:#fff">
        Lost ≠ Gone forever.
      </h2>
      <p style="color:rgba(255,255,255,.45);max-width:520px;margin:.75rem auto 0;font-size:.9rem;line-height:1.7">
        Every day, millions of items are lost. Most never make it back — not because no one found them, but because there was no way to connect finder to owner. SAF fixes that.
      </p>
    </div>

    <div class="row g-4">
      <?php
      $pillars = [
        ['bi-lightning-charge-fill','Instant connection','The moment a found item is scanned or reported, the owner is notified. No calls to lost property. No waiting days.','#4f46e5','rgba(79,70,229,.12)'],
        ['bi-shield-lock-fill','Built against fraud','Private ownership questions and AI scoring mean only the real owner can claim their item back. Thieves cannot bluff their way through.','#dc2626','rgba(220,38,38,.12)'],
        ['bi-airplane-fill','No borders','Lost at an airport? Found in another city? Reports are visible and searchable everywhere. Distance is not a barrier.','#f59e0b','rgba(245,158,11,.12)'],
        ['bi-qr-code','Tag it once, protected forever','One SAF tag on your item means any finder — anywhere in the world — can connect it back to you instantly. No app. No friction.','#10b981','rgba(16,185,129,.12)'],
      ];
      foreach($pillars as $p): ?>
      <div class="col-md-6 col-lg-3">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.75rem;height:100%">
          <div style="width:44px;height:44px;border-radius:12px;background:<?= $p[4] ?>;display:flex;align-items:center;justify-content:center;margin-bottom:1.1rem">
            <i class="bi <?= $p[0] ?>" style="font-size:1.15rem;color:<?= $p[3] ?>"></i>
          </div>
          <div style="font-size:.88rem;font-weight:700;color:<?= $p[3] ?>;margin-bottom:.5rem;font-family:'Space Grotesk',sans-serif"><?= $p[1] ?></div>
          <div style="font-size:.8rem;color:rgba(255,255,255,.5);line-height:1.65"><?= $p[2] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
      <a href="<?= base_url ?>?page=lost" class="btn btn-outline-light rounded-pill px-4 fw-semibold me-2">Report lost item</a>
      <a href="<?= base_url ?>?page=found" class="btn btn-gradient rounded-pill px-4 fw-semibold">Report found item</a>
    </div>

  </div>
</div>


<!-- ══ FINAL CTA — bold close, one button ══ -->
<div style="background:#050814;position:relative;overflow:hidden;padding:8rem 0">
  <div class="saf-orb saf-orb-1" style="opacity:.5"></div>
  <div class="saf-orb saf-orb-2" style="opacity:.4"></div>
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:60px 60px"></div>

  <div class="container-xl px-4 text-center" style="position:relative;z-index:1">
    <div style="max-width:680px;margin:0 auto">
      <div style="font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(129,140,248,.75);margin-bottom:1.5rem">
        The decision is simple
      </div>
      <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(2.2rem,5.5vw,4rem);font-weight:800;color:#fff;letter-spacing:-.04em;line-height:1.05;margin-bottom:1.5rem">
        Two minutes.<br><span style="background:linear-gradient(135deg,#818cf8,#c084fc,#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Zero regrets.</span>
      </h2>
      <p style="color:rgba(255,255,255,.5);font-size:1rem;max-width:440px;margin:0 auto 2.5rem;line-height:1.7">
        Register free. Tag your items today. Because the worst time to wish you had a SAF tag is the moment you lose something that matters.
      </p>
      <div class="d-flex flex-column align-items-center gap-3">
        <?php if(!$is_logged_in): ?>
        <a href="<?= base_url ?>?page=register" class="btn btn-gradient rounded-pill fw-bold" style="padding:1rem 3rem;font-size:1rem;letter-spacing:-.01em;box-shadow:0 8px 40px rgba(79,70,229,.45)">
          Create my free account →
        </a>
        <?php else: ?>
        <a href="<?= base_url ?>?page=my-items" class="btn btn-gradient rounded-pill fw-bold" style="padding:1rem 3rem;font-size:1rem">
          Go to my dashboard →
        </a>
        <?php endif; ?>
        <span style="font-size:.72rem;color:rgba(255,255,255,.28)">
          Free &nbsp;·&nbsp; No credit card &nbsp;·&nbsp; Start in under 2 minutes
        </span>
      </div>
    </div>
  </div>
</div>
