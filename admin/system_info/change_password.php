<?php
$admin = isset($_SESSION['userdata']) ? $_SESSION['userdata'] : [];
?>
<div class="container-fluid py-3">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">

      <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--saf-primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-0">Change Admin Password</h5>
          <div class="text-muted" style="font-size:.8rem">Update your administrator account password</div>
        </div>
      </div>

      <div id="cp-msg"></div>

      <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div style="height:3px;background:linear-gradient(90deg,var(--saf-primary),#7c3aed)"></div>
        <div class="card-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem">Current Password</label>
            <div class="input-group">
              <input type="password" class="form-control rounded-start" id="cp-old" placeholder="Enter current password">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('cp-old',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control rounded-start" id="cp-new" placeholder="Minimum 8 characters" oninput="checkStrength(this.value)">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('cp-new',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div id="pw-strength-bar" class="mt-2" style="height:4px;border-radius:2px;background:#e2e8f0;transition:width .3s,background .3s;width:0"></div>
            <div id="pw-strength-txt" class="mt-1" style="font-size:.73rem;color:var(--saf-muted)"></div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold" style="font-size:.85rem">Confirm New Password</label>
            <div class="input-group">
              <input type="password" class="form-control rounded-start" id="cp-confirm" placeholder="Repeat new password">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('cp-confirm',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <button class="btn btn-gradient rounded-pill w-100 fw-bold py-2" onclick="changeAdminPassword()">
            <i class="bi bi-shield-check me-1"></i>Update Password
          </button>
        </div>
      </div>

      <!-- Security tips -->
      <div class="mt-3 p-3 rounded-3" style="background:rgba(79,70,229,.05);border:1px solid rgba(79,70,229,.12)">
        <div class="fw-semibold mb-2" style="font-size:.8rem;color:var(--saf-primary)"><i class="bi bi-lightbulb me-1"></i>Strong password tips</div>
        <ul class="mb-0" style="font-size:.78rem;color:var(--saf-muted);padding-left:1.1rem;line-height:1.9">
          <li>At least 12 characters</li>
          <li>Mix of upper &amp; lowercase letters</li>
          <li>Include numbers and symbols (!@#$%)</li>
          <li>Avoid dictionary words or birthdays</li>
        </ul>
      </div>

    </div>
  </div>
</div>

<script>
function togglePw(id, btn){
  var inp = document.getElementById(id);
  var icon = btn.querySelector('i');
  if(inp.type === 'password'){
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

function checkStrength(pw){
  var bar = document.getElementById('pw-strength-bar');
  var txt = document.getElementById('pw-strength-txt');
  var score = 0;
  if(pw.length >= 8)  score++;
  if(pw.length >= 12) score++;
  if(/[A-Z]/.test(pw)) score++;
  if(/[0-9]/.test(pw)) score++;
  if(/[^A-Za-z0-9]/.test(pw)) score++;
  var pct = (score / 5 * 100) + '%';
  var col = score <= 1 ? '#ef4444' : score <= 3 ? '#f59e0b' : '#10b981';
  var lbl = score <= 1 ? 'Weak' : score <= 3 ? 'Fair' : score === 5 ? 'Strong' : 'Good';
  bar.style.width = pct;
  bar.style.background = col;
  txt.textContent = pw.length ? lbl : '';
  txt.style.color = col;
}

function changeAdminPassword(){
  var old     = document.getElementById('cp-old').value.trim();
  var nw      = document.getElementById('cp-new').value.trim();
  var confirm = document.getElementById('cp-confirm').value.trim();
  if(!old || !nw || !confirm){ alert_toast('All fields are required.','warning'); return; }
  if(nw !== confirm){ alert_toast('New passwords do not match.','warning'); return; }
  if(nw.length < 8){ alert_toast('New password must be at least 8 characters.','warning'); return; }

  start_loader();
  $.ajax({
    url: _base_url_ + 'classes/Login.php?f=change_password',
    method: 'POST', dataType: 'json',
    data: { old_password: old, new_password: nw, confirm_password: confirm },
    success: function(r){
      end_loader();
      if(r.status === 'success'){
        document.getElementById('cp-msg').innerHTML = '<div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i>'+r.msg+'</div>';
        document.getElementById('cp-old').value = '';
        document.getElementById('cp-new').value = '';
        document.getElementById('cp-confirm').value = '';
        document.getElementById('pw-strength-bar').style.width = '0';
        document.getElementById('pw-strength-txt').textContent = '';
      } else {
        document.getElementById('cp-msg').innerHTML = '<div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>'+(r.msg||'Error.')+'</div>';
      }
    }
  });
}
</script>
