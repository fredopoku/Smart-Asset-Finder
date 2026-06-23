<?php
// Payment details from system settings
$_pay = [
  'mtn_number'         => $_settings->info('pay_mtn_number'),
  'mtn_name'           => $_settings->info('pay_mtn_name'),
  'vodafone_number'    => $_settings->info('pay_vodafone_number'),
  'vodafone_name'      => $_settings->info('pay_vodafone_name'),
  'airteltigo_number'  => $_settings->info('pay_airteltigo_number'),
  'airteltigo_name'    => $_settings->info('pay_airteltigo_name'),
  'bank_name'          => $_settings->info('pay_bank_name'),
  'bank_account_name'  => $_settings->info('pay_bank_account_name'),
  'bank_account_number'=> $_settings->info('pay_bank_account_number'),
  'bank_branch'        => $_settings->info('pay_bank_branch'),
  'instructions'       => $_settings->info('pay_instructions'),
];

// Pre-fill checkout if logged in
$buyer_name  = '';
$buyer_email = '';
$buyer_phone = '';
if(isset($_SESSION['pub_userdata'])){
    $buyer_name  = htmlspecialchars($_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname']);
    $buyer_email = htmlspecialchars($_SESSION['pub_userdata']['email']);
    $buyer_phone = htmlspecialchars($_SESSION['pub_userdata']['phone'] ?? '');
}

// Product catalogue — hardcoded for now, admin-managed later
$products = [
  [
    'sku'       => 'nano-4',
    'name'      => 'SAF Nano Pack',
    'tagline'   => '4 waterproof QR stickers',
    'price'     => 12.00,
    'badge'     => 'Most Popular',
    'badge_cls' => 'bg-warning text-dark',
    'icon'      => 'bi-grid-3x3',
    'color'     => '#4f46e5',
    'bg'        => 'rgba(79,70,229,.08)',
    'points'    => 150,
    'desc'      => 'Professional-grade waterproof QR stickers, 3 × 3 cm. UV-resistant print that won\'t fade outdoors. Pre-registered to your account — stick on and you\'re done.',
    'features'  => ['4 stickers per pack','Waterproof & UV-resistant','3 cm × 3 cm — fits phones, laptops, bags','Pre-registered to your SAF account'],
    'delivery'  => '3–5 business days',
  ],
  [
    'sku'       => 'slim-1',
    'name'      => 'SAF Slim',
    'tagline'   => 'Ultra-thin laptop & device tag',
    'price'     => 18.00,
    'badge'     => null,
    'badge_cls' => '',
    'icon'      => 'bi-phone',
    'color'     => '#0ea5e9',
    'bg'        => 'rgba(14,165,233,.08)',
    'points'    => 220,
    'desc'      => 'Only 0.3 mm thick. Slides under a laptop, sticks to a phone back, or sits inside a bag lining. Tamper-evident ink — if someone tries to remove it, the QR pattern distorts and you get an alert.',
    'features'  => ['0.3 mm ultra-thin','Tamper-evident — alerts on removal','Adhesive rated for 5+ years','Works on metal surfaces'],
    'delivery'  => '3–5 business days',
  ],
  [
    'sku'       => 'shield-1',
    'name'      => 'SAF Shield',
    'tagline'   => 'Metal keyring tag — QR + NFC',
    'price'     => 25.00,
    'badge'     => 'New',
    'badge_cls' => 'bg-success text-white',
    'icon'      => 'bi-shield-fill-check',
    'color'     => '#10b981',
    'bg'        => 'rgba(16,185,129,.08)',
    'points'    => 300,
    'desc'      => 'Brushed aluminium keyring tag with both a QR code AND an embedded NFC chip. iPhone users can tap it (no camera needed). Android users can scan or tap. Built to survive keys, bags, and outdoor use.',
    'features'  => ['QR code + NFC chip (dual-mode)','Brushed aluminium — drop-proof','Fits any key ring or bag zipper','Tap with iPhone or Android — no app needed'],
    'delivery'  => '5–7 business days',
  ],
  [
    'sku'       => 'band-1',
    'name'      => 'SAF Band',
    'tagline'   => 'Safety wristband for kids & elderly',
    'price'     => 20.00,
    'badge'     => 'Unique',
    'badge_cls' => 'bg-danger text-white',
    'icon'      => 'bi-watch',
    'color'     => '#f43f5e',
    'bg'        => 'rgba(244,63,94,.08)',
    'points'    => 250,
    'desc'      => 'Soft silicone wristband with an embedded QR code panel. Designed for children, the elderly, or anyone who could get separated in a crowd. Finder scans it → your contact details appear instantly — no app, no login.',
    'features'  => ['Soft medical-grade silicone','Adjustable 14 cm – 22 cm','Engraved emergency contact panel','Waterproof — shower, swim, rain'],
    'delivery'  => '3–5 business days',
  ],
  [
    'sku'       => 'essentials',
    'name'      => 'SAF Essentials Pack',
    'tagline'   => '8 stickers + 2 keyrings + 1 NFC card',
    'price'     => 55.00,
    'badge'     => 'Best Value',
    'badge_cls' => 'bg-primary text-white',
    'icon'      => 'bi-box-seam',
    'color'     => '#7c3aed',
    'bg'        => 'rgba(124,58,237,.08)',
    'points'    => 650,
    'desc'      => 'Everything you need to protect a household. 8 waterproof QR stickers for everyday items, 2 metal keyring tags for keys and bags, and 1 dual-mode NFC card for your wallet. All pre-registered. Ships in branded packaging.',
    'features'  => ['8 × Nano stickers (assorted sizes)','2 × SAF Shield keyring tags','1 × SAF NFC wallet card','Branded gift-ready packaging'],
    'delivery'  => '5–7 business days',
  ],
  [
    'sku'       => 'pro',
    'name'      => 'SAF Pro Pack',
    'tagline'   => '15 stickers + 4 keyrings + 2 NFC + luggage tag',
    'price'     => 95.00,
    'badge'     => 'Family & Business',
    'badge_cls' => 'bg-dark text-white',
    'icon'      => 'bi-briefcase',
    'color'     => '#f59e0b',
    'bg'        => 'rgba(245,158,11,.08)',
    'points'    => 1100,
    'desc'      => 'The complete SAF protection kit for families, professionals, or small businesses tagging their equipment. Comes with a premium laser-engraved luggage tag that survives baggage handling. Our best-selling bundle.',
    'features'  => ['15 × Nano stickers (mixed sizes)','4 × SAF Shield keyring tags','2 × SAF NFC wallet cards','1 × Premium laser-engraved luggage tag','Priority dispatch'],
    'delivery'  => '3–5 business days (priority)',
  ],
  [
    'sku'       => 'gps',
    'name'      => 'SAF GPS Tracker',
    'tagline'   => 'Bluetooth GPS puck — real-time location',
    'price'     => 180.00,
    'badge'     => 'Premium',
    'badge_cls' => 'bg-gradient text-white',
    'icon'      => 'bi-geo-alt-fill',
    'color'     => '#06b6d4',
    'bg'        => 'rgba(6,182,212,.08)',
    'points'    => 2000,
    'desc'      => 'Our most advanced tag. A coin-sized Bluetooth GPS puck that pairs with your SAF account and shows real-time location on your dashboard. Battery lasts 6 months. Drop it in a bag, car, or ship container.',
    'features'  => ['Real-time GPS + Bluetooth tracking','6-month battery life','IP67 waterproof','SAF app integration (coming soon)','Crowd-sourced detection via SAF network'],
    'delivery'  => '7–14 business days',
  ],
];

$delivery_fee = 5.00; // flat GHS 5 delivery
?>

<!-- ═══════════════ SHOP HERO ═══════════════ -->
<div class="col-12 px-0" style="background:var(--saf-dark);padding:3rem 0 2.5rem;margin-bottom:0">
  <div class="container-xl px-4">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <div class="section-label" style="margin-bottom:.75rem">Physical Hardware Store</div>
        <h1 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,4vw,3rem);font-weight:800;color:#fff;line-height:1.1;margin-bottom:1rem">
          Protect What You Love.<br>
          <span style="background:linear-gradient(135deg,#818cf8,#c084fc,#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Professionally Tagged.</span>
        </h1>
        <p style="color:rgba(255,255,255,.6);font-size:.95rem;max-width:500px;line-height:1.8;margin-bottom:1.5rem">
          Every SAF tag comes <strong style="color:#fff">pre-registered to your account</strong> — no activation, no setup. Stick it on, and your item is instantly protected. If it's found, we notify you.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <div style="display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.7);font-size:.83rem">
            <i class="bi bi-truck text-warning"></i> Worldwide delivery
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.7);font-size:.83rem">
            <i class="bi bi-shield-check text-success"></i> Every tag pre-registered
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.7);font-size:.83rem">
            <i class="bi bi-trophy-fill text-warning"></i> Earn points on every purchase
          </div>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-flex justify-content-end gap-3 align-items-center">
        <?php
        $hero_icons = [
          ['bi-grid-3x3','#4f46e5','Nano Stickers'],
          ['bi-shield-fill-check','#10b981','Shield Tag'],
          ['bi-watch','#f43f5e','SAF Band'],
          ['bi-geo-alt-fill','#06b6d4','GPS Tracker'],
        ];
        foreach($hero_icons as $i => $ic): ?>
        <div style="background:var(--saf-glass);border:1px solid var(--saf-glass-border);border-radius:20px;padding:1.25rem;text-align:center;width:100px;<?= $i%2===1 ? 'margin-top:28px' : '' ?>">
          <div style="width:44px;height:44px;border-radius:12px;background:<?= $ic[1] ?>22;display:flex;align-items:center;justify-content:center;margin:0 auto .6rem;font-size:1.3rem;color:<?= $ic[1] ?>">
            <i class="bi <?= $ic[0] ?>"></i>
          </div>
          <div style="font-size:.67rem;color:rgba(255,255,255,.6);font-weight:600;line-height:1.3"><?= $ic[2] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ UNIQUE SELLING STRIP ═══════════════ -->
<div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:.75rem 0;margin-bottom:3rem">
  <div class="container-xl px-4">
    <div class="d-flex justify-content-center gap-5 flex-wrap" style="font-size:.8rem;color:rgba(255,255,255,.9);font-weight:500">
      <span><i class="bi bi-check2 me-1"></i>No activation needed</span>
      <span><i class="bi bi-check2 me-1"></i>QR + NFC dual-mode</span>
      <span><i class="bi bi-check2 me-1"></i>Tamper-evident options</span>
      <span><i class="bi bi-check2 me-1"></i>Flat GHS 5 delivery</span>
      <span><i class="bi bi-check2 me-1"></i>Points on every order</span>
    </div>
  </div>
</div>

<!-- ═══════════════ PRODUCT GRID ═══════════════ -->
<div class="container-xl px-4 pb-5">

  <div class="row g-4 mb-5">
    <?php foreach($products as $idx => $p): ?>
    <div class="col-lg-4 col-md-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius:20px;overflow:hidden;transition:transform .2s,box-shadow .2s" onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 48px rgba(0,0,0,.13)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">

        <!-- Card top -->
        <div class="position-relative p-4 pb-3" style="background:<?= $p['bg'] ?>;border-bottom:1px solid <?= $p['color'] ?>18">
          <?php if($p['badge']): ?>
          <span class="badge rounded-pill <?= $p['badge_cls'] ?> position-absolute" style="top:14px;right:14px;font-size:.68rem;padding:.35em .75em"><?= $p['badge'] ?></span>
          <?php endif; ?>
          <div style="width:52px;height:52px;border-radius:14px;background:<?= $p['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;margin-bottom:.9rem">
            <i class="bi <?= $p['icon'] ?>"></i>
          </div>
          <h5 class="fw-bold mb-0" style="color:var(--saf-dark);font-family:'Space Grotesk',sans-serif"><?= $p['name'] ?></h5>
          <div style="font-size:.8rem;color:<?= $p['color'] ?>;font-weight:600;margin-top:.2rem"><?= $p['tagline'] ?></div>
        </div>

        <div class="card-body p-4 d-flex flex-column">
          <p style="font-size:.83rem;color:#64748b;line-height:1.7;margin-bottom:1rem"><?= $p['desc'] ?></p>

          <!-- Features -->
          <ul class="list-unstyled mb-3 flex-grow-1" style="font-size:.78rem">
            <?php foreach($p['features'] as $f): ?>
            <li class="d-flex align-items-start gap-2 mb-1">
              <i class="bi bi-check2-circle flex-shrink-0 mt-1" style="color:<?= $p['color'] ?>"></i>
              <span style="color:#374151"><?= $f ?></span>
            </li>
            <?php endforeach; ?>
          </ul>

          <!-- Delivery -->
          <div class="d-flex align-items-center gap-1 mb-3" style="font-size:.72rem;color:#94a3b8">
            <i class="bi bi-truck"></i> <?= $p['delivery'] ?>
          </div>

          <!-- Price + CTA -->
          <div class="d-flex align-items-center justify-content-between gap-2 mt-auto">
            <div>
              <div class="fw-bold" style="font-size:1.5rem;color:var(--saf-dark);font-family:'Space Grotesk',sans-serif">GHS <?= number_format($p['price'],2) ?></div>
              <div style="font-size:.7rem;color:#94a3b8">or <?= number_format($p['points']) ?> pts</div>
            </div>
            <button class="btn btn-gradient rounded-pill px-3 fw-semibold" style="font-size:.82rem"
              onclick="openOrder(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
              <i class="bi bi-bag-plus me-1"></i>Order Now
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- How ordering works -->
  <div class="row g-0 mb-5" style="background:var(--saf-dark);border-radius:24px;overflow:hidden">
    <div class="col-12 p-5">
      <div class="text-center mb-4">
        <div class="section-label" style="background:rgba(129,140,248,.15);border-color:rgba(129,140,248,.25);color:#818cf8;margin-bottom:.5rem">How It Works</div>
        <h2 style="color:#fff;font-family:'Space Grotesk',sans-serif;font-size:1.6rem">From order to protected in days</h2>
      </div>
      <div class="row g-4 text-center">
        <?php $steps = [
          ['bi-bag-check','Order Online','Pick your tags and enter your delivery address. Pay via mobile money or card.','#818cf8'],
          ['bi-box-seam','We Print & Register','Your tags are printed with your unique QR codes and pre-registered to your SAF account.','#10b981'],
          ['bi-truck','We Deliver','Packaged and delivered to your door worldwide. Estimated delivery 3–10 business days depending on location.','#f59e0b'],
          ['bi-sticker','Stick & Forget','Attach your tags to your valuables. If anything gets lost, our network brings it back.','#f43f5e'],
        ];
        foreach($steps as $i => $s): ?>
        <div class="col-md-3 col-6">
          <div style="width:48px;height:48px;border-radius:14px;background:<?= $s[3] ?>22;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.2rem;color:<?= $s[3] ?>">
            <i class="bi <?= $s[0] ?>"></i>
          </div>
          <div style="font-size:.75rem;font-weight:700;color:rgba(255,255,255,.9);margin-bottom:.3rem;font-family:'Space Grotesk',sans-serif"><?= $s[1] ?></div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.45);line-height:1.6"><?= $s[2] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- FAQ / reassurance strip -->
  <div class="row g-3 mb-4">
    <?php $faq = [
      ['bi-patch-question','Why is my account pre-registered on every tag?','We link every tag\'s QR code to your SAF account during printing — so there\'s nothing to set up. Scan any tag and it points straight to your profile.'],
      ['bi-cash-coin','What payment methods do you accept?','We accept card payments via Paystack. All transactions are encrypted and processed securely.'],
      ['bi-arrow-repeat','What if my tag is damaged or lost?','Contact us and we\'ll replace any defective tag free of charge within 30 days of delivery. Damaged tags from normal wear are replaced at 50% off.'],
    ]; foreach($faq as $f): ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:16px">
        <i class="bi <?= $f[0] ?> mb-2" style="font-size:1.3rem;color:var(--saf-primary)"></i>
        <div class="fw-semibold mb-1" style="font-size:.85rem;color:var(--saf-dark)"><?= $f[1] ?></div>
        <div style="font-size:.78rem;color:#64748b;line-height:1.6"><?= $f[2] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- ══════════ ORDER MODAL ══════════ -->
<div class="modal fade" id="order-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="order-modal-title">Order</h5>
          <div class="text-muted" style="font-size:.8rem" id="order-modal-sub"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="order-alert"></div>
        <form id="order-frm" novalidate>
          <input type="hidden" name="sku"        id="o-sku">
          <input type="hidden" name="product"    id="o-product">
          <input type="hidden" name="unit_price" id="o-price">
          <div class="row g-3">

            <!-- Product summary -->
            <div class="col-12">
              <div id="order-product-card" class="p-3 rounded-3 mb-1" style="background:#f8fafc;border:1px solid #e2e8f0"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.83rem">Quantity</label>
              <div class="input-group" style="width:130px">
                <button type="button" class="btn btn-outline-secondary" onclick="changeQty(-1)">−</button>
                <input type="number" name="qty" id="o-qty" class="form-control text-center fw-bold" value="1" min="1" max="20" style="font-size:.95rem" oninput="updateTotal()">
                <button type="button" class="btn btn-outline-secondary" onclick="changeQty(1)">+</button>
              </div>
            </div>

            <div class="col-md-6 d-flex align-items-end">
              <div class="w-100 p-3 rounded-3 text-center" style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
                <div style="font-size:.7rem;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:1px">Total</div>
                <div id="o-total-display" class="fw-bold text-white" style="font-size:1.6rem;font-family:'Space Grotesk',sans-serif">GHS 0.00</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.6)">incl. GHS <?= number_format($delivery_fee,2) ?> delivery</div>
              </div>
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.83rem">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="customer_name" class="form-control" required value="<?= $buyer_name ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.83rem">Email <span class="text-danger">*</span></label>
              <input type="email" name="customer_email" class="form-control" required value="<?= $buyer_email ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.83rem">Phone <span class="text-danger">*</span></label>
              <input type="tel" name="customer_phone" class="form-control" required placeholder="e.g. 0244 123 456" value="<?= $buyer_phone ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.83rem">Payment Method <span class="text-danger">*</span></label>
              <div class="d-flex flex-wrap gap-2 pt-1">
                <label class="pay-method-pill">
                  <input type="radio" name="payment_method" value="mobile_money" checked>
                  <span>📱 Mobile Money</span>
                </label>
                <label class="pay-method-pill">
                  <input type="radio" name="payment_method" value="card">
                  <span>💳 Card</span>
                </label>
                <label class="pay-method-pill">
                  <input type="radio" name="payment_method" value="cash_on_delivery">
                  <span>💵 Cash on Delivery</span>
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" style="font-size:.83rem">Delivery Address <span class="text-danger">*</span></label>
              <textarea name="delivery_address" class="form-control" rows="2" required placeholder="Street address, area, city/town, region"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" style="font-size:.83rem">Order Notes <span class="text-muted fw-normal">(optional)</span></label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Preferred delivery time, special instructions..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-2">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient rounded-pill px-5 fw-semibold" id="place-order-btn" onclick="initiatePayment()">
          <i class="bi bi-lock-fill me-1"></i>Pay Securely
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ ORDER SUCCESS MODAL ══════════ -->
<div class="modal fade" id="order-success-modal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-body p-4">
        <!-- Header -->
        <div class="text-center mb-4">
          <div style="width:64px;height:64px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;color:#fff">
            <i class="bi bi-check-lg"></i>
          </div>
          <h5 class="fw-bold mb-1" style="font-family:'Space Grotesk',sans-serif">Order Placed!</h5>
          <div id="order-ref-display" class="text-muted" style="font-size:.8rem"></div>
        </div>

        <!-- Payment instructions -->
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;margin-bottom:16px">
          <div class="fw-semibold mb-1" style="font-size:.82rem;color:#92400e"><i class="bi bi-exclamation-circle me-1"></i>Complete your payment</div>
          <div style="font-size:.8rem;color:#78350f"><?= htmlspecialchars($_pay['instructions'] ?: 'Send payment to any of the details below and share your receipt with us via WhatsApp or email to confirm your order.') ?></div>
        </div>

        <!-- Payment methods -->
        <div id="pay-details-container">

          <?php if($_pay['mtn_number']): ?>
          <div class="pay-detail-card" data-method="mobile_money" style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold mb-1" style="font-size:.82rem">📱 MTN Mobile Money</div>
                <div style="font-size:1.1rem;font-weight:700;letter-spacing:.04em;color:#030712"><?= htmlspecialchars($_pay['mtn_number']) ?></div>
                <?php if($_pay['mtn_name']): ?>
                <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($_pay['mtn_name']) ?></div>
                <?php endif; ?>
              </div>
              <button class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;white-space:nowrap"
                onclick="copyPay('<?= htmlspecialchars($_pay['mtn_number']) ?>', this)">Copy</button>
            </div>
          </div>
          <?php endif; ?>

          <?php if($_pay['vodafone_number']): ?>
          <div class="pay-detail-card" data-method="mobile_money" style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold mb-1" style="font-size:.82rem">📱 Vodafone Cash</div>
                <div style="font-size:1.1rem;font-weight:700;letter-spacing:.04em;color:#030712"><?= htmlspecialchars($_pay['vodafone_number']) ?></div>
                <?php if($_pay['vodafone_name']): ?>
                <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($_pay['vodafone_name']) ?></div>
                <?php endif; ?>
              </div>
              <button class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;white-space:nowrap"
                onclick="copyPay('<?= htmlspecialchars($_pay['vodafone_number']) ?>', this)">Copy</button>
            </div>
          </div>
          <?php endif; ?>

          <?php if($_pay['airteltigo_number']): ?>
          <div class="pay-detail-card" data-method="mobile_money" style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold mb-1" style="font-size:.82rem">📱 AirtelTigo Money</div>
                <div style="font-size:1.1rem;font-weight:700;letter-spacing:.04em;color:#030712"><?= htmlspecialchars($_pay['airteltigo_number']) ?></div>
                <?php if($_pay['airteltigo_name']): ?>
                <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($_pay['airteltigo_name']) ?></div>
                <?php endif; ?>
              </div>
              <button class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;white-space:nowrap"
                onclick="copyPay('<?= htmlspecialchars($_pay['airteltigo_number']) ?>', this)">Copy</button>
            </div>
          </div>
          <?php endif; ?>

          <?php if($_pay['bank_account_number']): ?>
          <div class="pay-detail-card" data-method="card" style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px">
            <div class="fw-bold mb-2" style="font-size:.82rem">🏦 Bank Transfer<?php if($_pay['bank_name']): ?> — <?= htmlspecialchars($_pay['bank_name']) ?><?php endif; ?></div>
            <div class="row g-2" style="font-size:.8rem">
              <?php if($_pay['bank_account_name']): ?>
              <div class="col-12">
                <span class="text-muted">Account Name</span><br>
                <span class="fw-semibold"><?= htmlspecialchars($_pay['bank_account_name']) ?></span>
              </div>
              <?php endif; ?>
              <div class="col-sm-7">
                <span class="text-muted">Account Number</span><br>
                <span style="font-size:1rem;font-weight:700;letter-spacing:.04em;color:#030712"><?= htmlspecialchars($_pay['bank_account_number']) ?></span>
              </div>
              <div class="col-sm-5 d-flex align-items-end">
                <button class="btn btn-sm btn-outline-secondary rounded-pill w-100" style="font-size:.72rem"
                  onclick="copyPay('<?= htmlspecialchars($_pay['bank_account_number']) ?>', this)">Copy Account No.</button>
              </div>
              <?php if($_pay['bank_branch']): ?>
              <div class="col-12">
                <span class="text-muted">Branch: </span><span class="fw-semibold"><?= htmlspecialchars($_pay['bank_branch']) ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if(!$_pay['mtn_number'] && !$_pay['vodafone_number'] && !$_pay['bank_account_number']): ?>
          <div class="text-center text-muted py-3" style="font-size:.82rem">
            Payment details not yet configured — our team will contact you directly.
          </div>
          <?php endif; ?>

        </div>

        <div class="text-muted mt-3 mb-4" style="font-size:.75rem;text-align:center">
          Reference your order number <strong id="order-ref-inline"></strong> in your payment description.
        </div>
        <button class="btn btn-primary rounded-pill w-100 fw-semibold" data-bs-dismiss="modal">Done</button>
      </div>
    </div>
  </div>
</div>

<script>
var _delivery_fee = <?= $delivery_fee ?>;
var _current_product = null;

function openOrder(product){
  _current_product = product;
  $('#o-sku').val(product.sku);
  $('#o-product').val(product.name);
  $('#o-price').val(product.price);
  $('#o-qty').val(1);
  $('#order-modal-title').text('Order — ' + product.name);
  $('#order-modal-sub').text(product.tagline);
  $('#order-product-card').html(
    '<div class="d-flex align-items-center gap-3">'
  + '<div style="width:44px;height:44px;border-radius:12px;background:'+product.bg+';display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:'+product.color+';flex-shrink:0"><i class="bi '+product.icon+'"></i></div>'
  + '<div><div class="fw-semibold" style="font-size:.88rem">'+product.name+'</div>'
  + '<div style="font-size:.75rem;color:#64748b">GHS '+parseFloat(product.price).toFixed(2)+' per unit &nbsp;·&nbsp; '+product.delivery+'</div></div>'
  + '</div>'
  );
  $('#order-alert').html('');
  updateTotal();
  var m = new bootstrap.Modal(document.getElementById('order-modal'));
  m.show();
}

function changeQty(delta){
  var qty = parseInt($('#o-qty').val()) + delta;
  if(qty < 1) qty = 1;
  if(qty > 20) qty = 20;
  $('#o-qty').val(qty);
  updateTotal();
}

function updateTotal(){
  if(!_current_product) return;
  var qty   = Math.max(1, parseInt($('#o-qty').val()) || 1);
  var price = parseFloat(_current_product.price);
  var total = qty * price + _delivery_fee;
  $('#o-total-display').text('GHS ' + total.toFixed(2));
}

function copyPay(text, btn){
  navigator.clipboard.writeText(text).then(function(){
    var orig = btn.textContent;
    btn.textContent = 'Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    setTimeout(function(){ btn.textContent = orig; btn.classList.remove('btn-success'); btn.classList.add('btn-outline-secondary'); }, 1800);
  });
}

var _paystackKey = '<?= htmlspecialchars(PAYSTACK_PUBLIC) ?>';
var _paystackAvailable = _paystackKey && _paystackKey.indexOf('xxxxxx') === -1;

function initiatePayment(){
  $('#order-alert').html('');
  var frm   = document.getElementById('order-frm');
  var email = frm.querySelector('[name=customer_email]').value.trim();
  var name  = frm.querySelector('[name=customer_name]').value.trim();
  var phone = frm.querySelector('[name=customer_phone]').value.trim();
  var addr  = frm.querySelector('[name=delivery_address]').value.trim();
  var meth  = frm.querySelector('[name=payment_method]:checked')?.value || 'mobile_money';

  if(!name || !email || !phone || !addr){
    $('#order-alert').html('<div class="alert alert-warning py-2 mb-0" style="font-size:.83rem"><i class="bi bi-exclamation-circle me-1"></i>Please fill in all required fields.</div>');
    return;
  }

  var qty   = parseInt($('#o-qty').val()) || 1;
  var price = parseFloat($('#o-price').val()) || 0;
  var total = qty * price + _delivery_fee;
  var amountPesewas = Math.round(total * 100);

  // Cash on delivery — no Paystack needed
  if(meth === 'cash_on_delivery' || !_paystackAvailable){
    placeOrderManual();
    return;
  }

  // Launch Paystack popup
  var $btn = $('#place-order-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Redirecting…');
  var txRef = 'SAF-' + Date.now();
  var channels = meth === 'card' ? ['card'] : ['mobile_money', 'card'];

  var handler = PaystackPop.setup({
    key:      _paystackKey,
    email:    email,
    amount:   amountPesewas,
    currency: 'GHS',
    ref:      txRef,
    channels: channels,
    metadata: {
      custom_fields: [
        {display_name:'Name',    variable_name:'name',    value: name},
        {display_name:'Phone',   variable_name:'phone',   value: phone},
        {display_name:'Product', variable_name:'product', value: $('#o-product').val()},
      ]
    },
    callback: function(response){
      $btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Confirming…');
      var fd = new FormData(frm);
      fd.append('reference', response.reference);
      $.ajax({
        url: _base_url_ + 'classes/Master.php?f=verify_paystack_payment',
        data: fd, cache:false, contentType:false, processData:false,
        method:'POST', dataType:'json',
        success: function(r){
          $btn.prop('disabled',false).html('<i class="bi bi-lock-fill me-1"></i>Pay Securely');
          if(r.status==='success'){
            bootstrap.Modal.getInstance(document.getElementById('order-modal')).hide();
            showOrderSuccess(r.ref, true);
          } else {
            $('#order-alert').html('<div class="alert alert-danger py-2 mb-0" style="font-size:.83rem">'+r.msg+'</div>');
          }
        },
        error: function(){
          $btn.prop('disabled',false).html('<i class="bi bi-lock-fill me-1"></i>Pay Securely');
          alert_toast('Verification error. Contact support with ref: '+response.reference,'error');
        }
      });
    },
    onClose: function(){
      $btn.prop('disabled',false).html('<i class="bi bi-lock-fill me-1"></i>Pay Securely');
    }
  });
  handler.openIframe();
}

function placeOrderManual(){
  var $btn = $('#place-order-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Placing…');
  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Master.php?f=place_order',
    data: new FormData(document.getElementById('order-frm')),
    cache:false, contentType:false, processData:false,
    method:'POST', dataType:'json',
    error: function(){
      end_loader();
      $btn.prop('disabled',false).html('<i class="bi bi-lock-fill me-1"></i>Pay Securely');
      alert_toast('Something went wrong. Please try again.','error');
    },
    success: function(r){
      end_loader();
      $btn.prop('disabled',false).html('<i class="bi bi-lock-fill me-1"></i>Pay Securely');
      if(r.status==='success'){
        bootstrap.Modal.getInstance(document.getElementById('order-modal')).hide();
        showOrderSuccess(r.ref, false);
      } else {
        $('#order-alert').html('<div class="alert alert-danger py-2 mb-0" style="font-size:.83rem">'+r.msg+'</div>');
      }
    }
  });
}

function showOrderSuccess(ref, paid){
  $('#order-ref-display').text('Order ref: #' + ref);
  $('#order-ref-inline').text('#' + ref);
  // Hide/show payment details block depending on payment status
  if(paid){
    $('#pay-details-container').html('<div class="text-center py-3" style="color:#059669"><i class="bi bi-patch-check-fill" style="font-size:2rem"></i><div class="fw-bold mt-2">Payment confirmed!</div><div class="text-muted" style="font-size:.82rem">Your order is being processed.</div></div>');
    document.querySelector('#order-success-modal .modal-body > div:nth-child(2)').style.display='none';
  }
  new bootstrap.Modal(document.getElementById('order-success-modal')).show();
}
</script>
