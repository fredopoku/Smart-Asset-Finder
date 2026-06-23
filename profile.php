<?php
if(!isset($_SESSION['pub_userdata'])){ header('Location: '.base_url.'?page=login'); exit; }
$uid  = (int)$_SESSION['pub_userdata']['id'];
$user = $conn->query("SELECT * FROM registered_users WHERE id={$uid} LIMIT 1")->fetch_assoc();
$pts  = (int)($conn->query("SELECT COALESCE(SUM(points),0) p FROM point_transactions WHERE user_id={$uid}")->fetch_assoc()['p'] ?? 0);
$items_count  = (int)($conn->query("SELECT COUNT(*) c FROM item_list WHERE user_id={$uid}")->fetch_assoc()['c'] ?? 0);
$orders_count = (int)($conn->query("SELECT COUNT(*) c FROM orders WHERE user_id={$uid}")->fetch_assoc()['c'] ?? 0);
$tags_count   = (int)($conn->query("SELECT COUNT(*) c FROM qr_tags WHERE user_id={$uid}")->fetch_assoc()['c'] ?? 0);
$claims_count = (int)($conn->query("SELECT COUNT(*) c FROM item_claims WHERE user_id={$uid}")->fetch_assoc()['c'] ?? 0);

$avatar_url = !empty($user['avatar']) && is_file(base_app.$user['avatar'])
              ? base_url.$user['avatar'] : '';
$initials   = strtoupper(substr($user['firstname'],0,1).substr($user['lastname'],0,1));
?>

<style>
/* ── Profile page ── */
.profile-hero {
  background: var(--saf-dark);
  position: relative; overflow: hidden;
  padding: 2.5rem 0 5rem;
}
.profile-hero::before {
  content:''; position:absolute; inset:0;
  background: radial-gradient(ellipse 70% 90% at 80% 50%, rgba(79,70,229,.5) 0%, transparent 60%),
              radial-gradient(ellipse 50% 60% at 10% 80%, rgba(245,158,11,.15) 0%, transparent 55%);
}
.profile-hero::after {
  content:''; position:absolute; inset:0;
  background-image:radial-gradient(circle,rgba(255,255,255,.04) 1px,transparent 1px);
  background-size:26px 26px;
}
.profile-hero-content { position:relative; z-index:1; }

.profile-avatar-wrap {
  position: relative; display: inline-block;
}
.profile-avatar-img, .profile-avatar-init {
  width: 100px; height: 100px; border-radius: 50%;
  border: 3px solid rgba(255,255,255,.3);
  object-fit: cover;
}
.profile-avatar-init {
  background: linear-gradient(135deg,#4f46e5,#7c3aed);
  display: flex; align-items: center; justify-content: center;
  font-size: 2.2rem; font-weight: 800; color: #fff;
  font-family: 'Space Grotesk', sans-serif;
}
.profile-avatar-edit-btn {
  position: absolute; bottom: 2px; right: 2px;
  width: 30px; height: 30px; border-radius: 50%;
  background: #fff; border: 2px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .2s;
  color: var(--saf-primary); font-size: .8rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.profile-avatar-edit-btn:hover {
  background: var(--saf-primary); color: #fff; border-color: var(--saf-primary);
}

.profile-stat-pill {
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 12px; padding: .65rem 1rem;
  text-align: center; backdrop-filter: blur(8px);
  min-width: 72px;
}
.profile-stat-num { font-family:'Space Grotesk',sans-serif; font-size:1.2rem; font-weight:800; color:#fff; line-height:1; }
.profile-stat-lbl { font-size:.68rem; color:rgba(255,255,255,.55); margin-top:.2rem; }

.profile-card-wrap { margin-top: -3rem; position: relative; z-index: 2; }

/* Sidebar nav */
.profile-nav-link {
  display: flex; align-items: center; gap:.65rem;
  padding: .6rem .9rem; border-radius: 10px;
  font-size: .86rem; font-weight: 500;
  color: var(--saf-muted); text-decoration: none;
  transition: all .15s;
}
.profile-nav-link:hover { background: rgba(79,70,229,.06); color: var(--saf-primary); }
.profile-nav-link.active { background: rgba(79,70,229,.1); color: var(--saf-primary); font-weight: 700; }
.profile-nav-link i { width: 18px; text-align: center; }

/* Avatar picker modal styles */
.ap-preset-img {
  border-radius: 50%; cursor: pointer; transition: all .18s;
  border: 3px solid transparent; width: 100%; aspect-ratio:1;
  object-fit:cover; display:block;
}
.ap-preset-img:hover { transform: scale(1.07); border-color: var(--saf-primary); }
.ap-preset-img.selected { border-color: var(--saf-primary); box-shadow: 0 0 0 3px rgba(79,70,229,.2); }
.ap-upload-zone {
  border: 2px dashed #c7d2fe; border-radius: 14px;
  padding: 1.5rem 1rem; text-align: center; cursor: pointer;
  transition: all .2s; background: #f8faff;
}
.ap-upload-zone:hover { border-color: var(--saf-primary); background: rgba(79,70,229,.04); }

/* Form section titles */
.form-section-title {
  display: flex; align-items: center; gap: .6rem;
  margin-bottom: 1.5rem;
}
.form-section-title .icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem;
}
</style>

<?php
// Preset avatar definitions
$avatar_presets = [
  ['id'=>'male-blue',     'label'=>'Male',    'style'=>'M'],
  ['id'=>'male-teal',     'label'=>'Male',    'style'=>'M'],
  ['id'=>'male-orange',   'label'=>'Male',    'style'=>'M'],
  ['id'=>'male-dark',     'label'=>'Male',    'style'=>'M'],
  ['id'=>'female-purple', 'label'=>'Female',  'style'=>'F'],
  ['id'=>'female-rose',   'label'=>'Female',  'style'=>'F'],
  ['id'=>'female-teal',   'label'=>'Female',  'style'=>'F'],
  ['id'=>'female-green',  'label'=>'Female',  'style'=>'F'],
];
?>
<?php $display_name = ucwords(strtolower(trim($user['firstname'].' '.$user['lastname']))); ?>
<!-- Avatar upload input (hidden) -->
<input type="file" id="avatar-file-input" accept="image/*" style="display:none" onchange="uploadAvatarFile(this)">

<!-- ── Profile hero ── -->
<div class="profile-hero">
  <div class="container-xl px-4 profile-hero-content">
    <div class="d-flex align-items-center gap-4 flex-wrap">

      <!-- Avatar — camera button directly opens modal, no dropdown -->
      <div class="profile-avatar-wrap">
        <?php if($avatar_url): ?>
        <img src="<?= $avatar_url ?>" class="profile-avatar-img" id="profile-avatar-display" alt="Avatar">
        <?php else: ?>
        <div class="profile-avatar-init" id="profile-avatar-display"><?= $initials ?></div>
        <?php endif; ?>
        <button class="profile-avatar-edit-btn" onclick="openAvatarPicker()" title="Change photo">
          <i class="bi bi-camera-fill"></i>
        </button>
      </div>

      <!-- Name & email -->
      <div>
        <h2 class="fw-bold text-white mb-1" style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.2rem,3vw,1.7rem);letter-spacing:-.02em" id="hero-display-name">
          <?= htmlspecialchars($display_name) ?>
        </h2>
        <div style="color:rgba(255,255,255,.6);font-size:.88rem"><?= htmlspecialchars($user['email']) ?></div>
        <div class="mt-2 d-flex gap-2 flex-wrap">
          <?php if($user['email_verified']): ?>
          <span style="background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.3);border-radius:50px;padding:.2rem .7rem;font-size:.72rem;color:#6ee7b7"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
          <?php else: ?>
          <span style="background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.3);border-radius:50px;padding:.2rem .7rem;font-size:.72rem;color:#fcd34d"><i class="bi bi-exclamation-triangle-fill me-1"></i>Email not verified</span>
          <?php endif; ?>
          <?php if(!empty($user['phone'])): ?>
          <span style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:50px;padding:.2rem .7rem;font-size:.72rem;color:rgba(255,255,255,.7)"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($user['phone']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Stats -->
      <div class="ms-auto d-flex gap-2 flex-wrap" style="margin-top:.5rem">
        <div class="profile-stat-pill">
          <div class="profile-stat-num" style="color:#fcd34d"><?= number_format($pts) ?></div>
          <div class="profile-stat-lbl">Points</div>
        </div>
        <div class="profile-stat-pill">
          <div class="profile-stat-num"><?= $items_count ?></div>
          <div class="profile-stat-lbl">Reports</div>
        </div>
        <div class="profile-stat-pill">
          <div class="profile-stat-num"><?= $tags_count ?></div>
          <div class="profile-stat-lbl">QR Tags</div>
        </div>
        <div class="profile-stat-pill">
          <div class="profile-stat-num"><?= $claims_count ?></div>
          <div class="profile-stat-lbl">Claims</div>
        </div>
        <div class="profile-stat-pill">
          <div class="profile-stat-num"><?= $orders_count ?></div>
          <div class="profile-stat-lbl">Orders</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Main content ── -->
<div class="container-xl px-4 profile-card-wrap pb-5">
  <div class="row g-4">

    <!-- Left sidebar nav -->
    <div class="col-lg-3 col-md-4">
      <div class="card border-0 shadow-sm p-3" style="border-radius:16px;position:sticky;top:86px">
        <div class="fw-semibold mb-2 px-2" style="font-size:.72rem;color:var(--saf-muted);text-transform:uppercase;letter-spacing:.07em">Account</div>
        <nav class="d-flex flex-column gap-1">
          <a href="#section-info" class="profile-nav-link active" onclick="scrollTo('#section-info',this)">
            <i class="bi bi-person-fill text-primary"></i>Personal Info
          </a>
          <a href="#section-password" class="profile-nav-link" onclick="scrollTo('#section-password',this)">
            <i class="bi bi-shield-lock-fill" style="color:#7c3aed"></i>Change Password
          </a>
        </nav>
        <hr class="my-2">
        <div class="fw-semibold mb-2 px-2" style="font-size:.72rem;color:var(--saf-muted);text-transform:uppercase;letter-spacing:.07em">Activity</div>
        <nav class="d-flex flex-column gap-1">
          <a href="<?= base_url ?>?page=my-items" class="profile-nav-link">
            <i class="bi bi-collection" style="color:#0ea5e9"></i>My Submissions
          </a>
          <a href="<?= base_url ?>?page=my-orders" class="profile-nav-link">
            <i class="bi bi-bag-check-fill" style="color:#059669"></i>My Orders
            <?php if($orders_count > 0): ?><span class="badge rounded-pill bg-primary ms-auto" style="font-size:.65rem"><?= $orders_count ?></span><?php endif; ?>
          </a>
        </nav>
        <hr class="my-2">
        <a href="<?= base_url ?>classes/Login.php?f=logout_user" class="profile-nav-link" style="color:#ef4444" onclick="return confirm('Sign out?')">
          <i class="bi bi-box-arrow-right" style="color:#ef4444"></i>Sign Out
        </a>
      </div>
    </div>

    <!-- Right: forms -->
    <div class="col-lg-9 col-md-8">

      <!-- Personal info -->
      <div id="section-info" class="card border-0 shadow-sm mb-4 scroll-reveal" style="border-radius:16px;overflow:hidden">
        <div style="height:3px;background:linear-gradient(90deg,var(--saf-primary),#7c3aed)"></div>
        <div class="card-body p-4">
          <div class="form-section-title">
            <div class="icon" style="background:rgba(79,70,229,.1);color:var(--saf-primary)"><i class="bi bi-person-fill"></i></div>
            <div>
              <div class="fw-bold" style="font-family:'Space Grotesk',sans-serif">Personal Information</div>
              <div class="text-muted" style="font-size:.75rem">Update your name and contact details</div>
            </div>
          </div>
          <div id="profile-msg"></div>
          <form id="profile-frm" novalidate>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">First Name</label>
                <input type="text" class="form-control rounded-3" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">Last Name</label>
                <input type="text" class="form-control rounded-3" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                  <input type="email" class="form-control rounded-end bg-light" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                <div class="text-muted mt-1" style="font-size:.72rem"><i class="bi bi-lock me-1"></i>Email cannot be changed</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">Phone Number</label>
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                  <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 0241234567">
                </div>
              </div>
              <div class="col-12 pt-1">
                <button type="button" class="btn btn-gradient rounded-pill px-5 fw-bold" onclick="saveProfile()">
                  <i class="bi bi-check2 me-1"></i>Save Changes
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Change password -->
      <div id="section-password" class="card border-0 shadow-sm scroll-reveal" style="border-radius:16px;overflow:hidden">
        <div style="height:3px;background:linear-gradient(90deg,#7c3aed,#c084fc)"></div>
        <div class="card-body p-4">
          <div class="form-section-title">
            <div class="icon" style="background:rgba(124,58,237,.1);color:#7c3aed"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
              <div class="fw-bold" style="font-family:'Space Grotesk',sans-serif">Change Password</div>
              <div class="text-muted" style="font-size:.75rem">Use a strong, unique password</div>
            </div>
          </div>
          <div id="pw-msg"></div>
          <form id="pw-frm" novalidate autocomplete="off">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.82rem">Current Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="old_password" placeholder="Enter current password" id="pw-old">
                  <button class="btn btn-outline-secondary" type="button" onclick="toggleField('pw-old',this)"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="new_password" placeholder="Min 8 characters" id="pw-new" oninput="pwStrength(this.value)">
                  <button class="btn btn-outline-secondary" type="button" onclick="toggleField('pw-new',this)"><i class="bi bi-eye"></i></button>
                </div>
                <div class="mt-1" style="height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden"><div id="pw-bar" style="height:100%;width:0;transition:width .3s,background .3s"></div></div>
                <div id="pw-str-txt" class="mt-1" style="font-size:.72rem"></div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.82rem">Confirm New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="confirm_password" placeholder="Repeat new password" id="pw-confirm">
                  <button class="btn btn-outline-secondary" type="button" onclick="toggleField('pw-confirm',this)"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-12 pt-1">
                <button type="button" class="btn btn-outline-primary rounded-pill px-5 fw-semibold" onclick="changePassword()">
                  <i class="bi bi-shield-check me-1"></i>Update Password
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
var _avatarPickerModal = null;

function openAvatarPicker(){
  if(!_avatarPickerModal) _avatarPickerModal = new bootstrap.Modal(document.getElementById('avatar-picker-modal'));
  _avatarPickerModal.show();
}

function _updateAvatarDisplay(src){
  var wrap = document.getElementById('profile-avatar-display');
  if(wrap.tagName === 'IMG'){
    wrap.src = src;
  } else {
    var img = document.createElement('img');
    img.id = 'profile-avatar-display';
    img.src = src; img.className = 'profile-avatar-img'; img.alt = 'Avatar';
    wrap.replaceWith(img);
  }
  document.querySelectorAll('.user-avatar-img').forEach(function(el){ el.src = src+'?t='+Date.now(); });
  if(_avatarPickerModal) _avatarPickerModal.hide();
  alert_toast('Profile photo updated!','success');
}

function uploadAvatarFile(input){
  if(!input.files || !input.files[0]) return;
  var fd = new FormData();
  fd.append('avatar', input.files[0]);
  fd.append('csrf_token', $('meta[name="csrf-token"]').attr('content') || '');
  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Login.php?f=upload_avatar',
    method: 'POST', processData: false, contentType: false,
    data: fd, dataType: 'json',
    success: function(r){
      end_loader();
      if(r.status === 'success'){
        _updateAvatarDisplay(r.path);
        input.value = '';
        // Show remove button
        document.getElementById('ap-remove-wrap').style.display = '';
      } else { alert_toast(r.msg,'error'); }
    }
  });
}

// Keep old name for backward compat
function uploadAvatar(input){
  if(!input.files || !input.files[0]) return;
  var fd = new FormData();
  fd.append('avatar', input.files[0]);
  fd.append('csrf_token', $('meta[name="csrf-token"]').attr('content') || '');
  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Login.php?f=upload_avatar',
    method: 'POST', processData: false, contentType: false,
    data: fd, dataType: 'json',
    success: function(r){
      end_loader();
      if(r.status === 'success'){
        _updateAvatarDisplay(r.path);
        input.value = '';
      } else { alert_toast(r.msg,'error'); }
    }
  });
}

function pickPresetAvatar(id){
  start_loader();
  $.post(_base_url_+'classes/Login.php?f=set_avatar_preset', {preset:id}, function(r){
    end_loader();
    if(r.status==='success'){
      _updateAvatarDisplay(r.path);
      document.getElementById('ap-remove-wrap').style.display='';
      document.querySelectorAll('.ap-preset-img').forEach(function(img){ img.classList.remove('selected'); });
      var picked = document.querySelector('.ap-preset-img[data-id="'+id+'"]');
      if(picked) picked.classList.add('selected');
    } else { alert_toast(r.msg,'error'); }
  },'json');
}

// Placeholder — will be replaced below
function removeAvatar(){
  if(!confirm('Remove your profile photo?')) return;
  $.post(_base_url_+'classes/Login.php?f=remove_avatar', {}, function(r){
    if(r.status === 'success'){
      var wrap = document.getElementById('profile-avatar-display');
      var initials = '<?= $initials ?>';
      var div = document.createElement('div');
      div.id = 'profile-avatar-display';
      div.className = 'profile-avatar-init';
      div.textContent = initials;
      wrap.replaceWith(div);
      document.getElementById('ap-remove-wrap').style.display='none';
      if(_avatarPickerModal) _avatarPickerModal.hide();
      document.querySelectorAll('.user-avatar-img').forEach(function(el){ el.style.display='none'; });
      alert_toast('Photo removed.', 'success');
    } else { alert_toast(r.msg, 'error'); }
  }, 'json');
}

/* ── Profile / password forms ── */
function saveProfile(){
  var data = {};
  $('#profile-frm').serializeArray().forEach(function(f){ data[f.name]=f.value; });
  start_loader();
  $.post(_base_url_+'classes/Login.php?f=update_profile', data, function(r){
    end_loader();
    if(r.status==='success'){
      document.getElementById('profile-msg').innerHTML = '<div class="alert alert-success rounded-3 py-2"><i class="bi bi-check-circle-fill me-2"></i>'+r.msg+'</div>';
      document.querySelectorAll('.user-first').forEach(function(el){ el.textContent=data.firstname; });
      var nameEl = document.getElementById('hero-display-name');
      if(nameEl) nameEl.textContent = (data.firstname+' '+data.lastname).replace(/\b\w/g,function(c){return c.toUpperCase();});
    } else {
      document.getElementById('profile-msg').innerHTML = '<div class="alert alert-danger rounded-3 py-2">'+r.msg+'</div>';
    }
  }, 'json');
}

function changePassword(){
  var data = {};
  $('#pw-frm').serializeArray().forEach(function(f){ data[f.name]=f.value; });
  if(data.new_password !== data.confirm_password){ alert_toast('Passwords do not match.','warning'); return; }
  start_loader();
  $.post(_base_url_+'classes/Login.php?f=change_password', data, function(r){
    end_loader();
    if(r.status==='success'){
      document.getElementById('pw-msg').innerHTML = '<div class="alert alert-success rounded-3 py-2"><i class="bi bi-check-circle-fill me-2"></i>'+r.msg+'</div>';
      $('#pw-frm')[0].reset();
      document.getElementById('pw-bar').style.width = '0';
    } else {
      document.getElementById('pw-msg').innerHTML = '<div class="alert alert-danger rounded-3 py-2">'+r.msg+'</div>';
    }
  }, 'json');
}

function pwStrength(pw){
  var s=0;
  if(pw.length>=8)s++;if(pw.length>=12)s++;
  if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
  var col=s<=1?'#ef4444':s<=3?'#f59e0b':'#10b981';
  var lbl=s<=1?'Weak':s<=3?'Fair':s===5?'Strong':'Good';
  document.getElementById('pw-bar').style.cssText='width:'+(s/5*100)+'%;background:'+col+';height:100%';
  document.getElementById('pw-str-txt').textContent=pw.length?lbl:'';
  document.getElementById('pw-str-txt').style.color=col;
}

function toggleField(id, btn){
  var f=document.getElementById(id);
  var ic=btn.querySelector('i');
  f.type=f.type==='password'?'text':'password';
  ic.className=f.type==='password'?'bi bi-eye':'bi bi-eye-slash';
}

function scrollTo(id, el){
  event.preventDefault();
  document.querySelector(id).scrollIntoView({behavior:'smooth',block:'start'});
  document.querySelectorAll('.profile-nav-link').forEach(function(a){ a.classList.remove('active'); });
  el.classList.add('active');
}

$(function(){
  document.querySelectorAll('.scroll-reveal').forEach(function(el){
    el.classList.add('revealed');
  });
});

</script>

<!-- ── Avatar Picker Modal (combined: upload + presets + remove) ── -->
<div class="modal fade" id="avatar-picker-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
    <div class="modal-content border-0" style="border-radius:20px;overflow:hidden">
      <div style="height:3px;background:linear-gradient(90deg,#4f46e5,#7c3aed,#f59e0b)"></div>
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <div>
          <h5 class="fw-bold mb-0" style="font-family:'Space Grotesk',sans-serif"><i class="bi bi-camera-fill me-2 text-primary"></i>Change Profile Photo</h5>
          <div class="text-muted" style="font-size:.78rem;margin-top:.2rem">Upload your photo or pick an avatar</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 pb-4">

        <!-- Upload zone -->
        <div class="ap-upload-zone mb-3" onclick="document.getElementById('avatar-file-input').click()">
          <i class="bi bi-cloud-arrow-up text-primary" style="font-size:1.6rem"></i>
          <div class="fw-semibold mt-1" style="font-size:.88rem">Click to upload your photo</div>
          <div class="text-muted" style="font-size:.73rem">JPG, PNG or WEBP &nbsp;·&nbsp; Max 3 MB</div>
        </div>

        <!-- Divider -->
        <div class="d-flex align-items-center gap-2 mb-3">
          <hr class="flex-grow-1 my-0"><span class="text-muted" style="font-size:.72rem;white-space:nowrap">or choose a preset</span><hr class="flex-grow-1 my-0">
        </div>

        <!-- Filter tabs -->
        <div class="d-flex gap-2 mb-3" id="ap-filter-bar">
          <button class="btn btn-sm rounded-pill" onclick="filterPresets('all',this)" style="border:1.5px solid #4f46e5;background:#4f46e5;color:#fff;font-size:.78rem;padding:.28rem .85rem;font-weight:600">All</button>
          <button class="btn btn-sm rounded-pill" onclick="filterPresets('M',this)" style="border:1.5px solid #e2e8f0;color:#64748b;font-size:.78rem;padding:.28rem .85rem;font-weight:600">Male</button>
          <button class="btn btn-sm rounded-pill" onclick="filterPresets('F',this)" style="border:1.5px solid #e2e8f0;color:#64748b;font-size:.78rem;padding:.28rem .85rem;font-weight:600">Female</button>
        </div>

        <!-- Preset grid -->
        <div class="row g-2" id="preset-grid">
          <?php
          $presets = [
            ['id'=>'male-blue',     'label'=>'Cool Blue',    'style'=>'M'],
            ['id'=>'male-teal',     'label'=>'Ocean Teal',   'style'=>'M'],
            ['id'=>'male-orange',   'label'=>'Sunrise',      'style'=>'M'],
            ['id'=>'male-dark',     'label'=>'Midnight',     'style'=>'M'],
            ['id'=>'female-purple', 'label'=>'Royal Purple', 'style'=>'F'],
            ['id'=>'female-rose',   'label'=>'Coral Rose',   'style'=>'F'],
            ['id'=>'female-teal',   'label'=>'Azure',        'style'=>'F'],
            ['id'=>'female-green',  'label'=>'Forest',       'style'=>'F'],
          ];
          foreach($presets as $p): ?>
          <div class="col-3 preset-item" data-style="<?= $p['style'] ?>">
            <img src="<?= base_url ?>assets/img/avatars/preset/<?= $p['id'] ?>.svg"
                 class="ap-preset-img" data-id="<?= $p['id'] ?>"
                 onclick="pickPresetAvatar('<?= $p['id'] ?>')" alt="<?= $p['label'] ?>">
            <div class="text-center mt-1" style="font-size:.65rem;color:#64748b"><?= $p['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Remove photo (hidden when no photo) -->
        <div id="ap-remove-wrap" style="<?= $avatar_url ? '' : 'display:none' ?>;margin-top:1rem">
          <hr class="my-0 mb-2">
          <button class="btn btn-sm w-100 rounded-3 fw-semibold" style="color:#dc2626;border:1.5px solid #fecaca;background:#fef2f2" onclick="removeAvatar()">
            <i class="bi bi-trash3 me-1"></i>Remove current photo
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

