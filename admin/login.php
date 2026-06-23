<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | <?= htmlspecialchars($_settings->info('name')) ?></title>
  <link href="<?= base_url ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= base_url ?>assets/css/style.css" rel="stylesheet">
  <link href="<?= base_url ?>assets/css/custom.css" rel="stylesheet">
  <script src="<?= base_url ?>assets/js/jquery-3.6.4.min.js"></script>
  <script src="<?= base_url ?>assets/js/script.js"></script>
  <script>
    var _base_url_ = '<?= base_url ?>';
    var _csrf_token_ = '<?= csrf_token() ?>';
    $(function(){ $.ajaxSetup({headers:{'X-CSRF-Token': _csrf_token_}}); });
  </script>
  <style>
    body {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    .login-card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.4);
      overflow: hidden;
    }
    .login-brand {
      background: linear-gradient(135deg, #1a56db, #0ea5e9);
      padding: 2rem;
      text-align: center;
    }
    .login-brand .brand-icon {
      width: 64px;
      height: 64px;
      background: rgba(255,255,255,0.2);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.8rem;
      color: #fff;
    }
    .login-brand h1 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff;
      margin: 0;
    }
    .login-brand p { color: rgba(255,255,255,0.75); font-size:.85rem; margin:0; }
    .login-body { padding: 2rem; }
    .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 .2rem rgba(26,86,219,.2); }
    .btn-login {
      background: linear-gradient(135deg, #1a56db, #0ea5e9);
      border: none;
      border-radius: 8px;
      padding: .75rem;
      font-weight: 600;
      letter-spacing: .3px;
      transition: opacity .2s;
    }
    .btn-login:hover { opacity: .9; }
    .input-group-text { background: #f8fafc; border-right: none; }
    .form-control.icon-input { border-left: none; }
    .footer-credit { color: rgba(255,255,255,.5); font-size:.8rem; text-align:center; margin-top:1.5rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6 col-sm-9">

        <div class="login-card card mb-3">
          <div class="login-brand">
            <div class="brand-icon">
              <i class="bi bi-search-heart"></i>
            </div>
            <h1><?= htmlspecialchars($_settings->info('name')) ?></h1>
            <p>Administration Portal</p>
          </div>

          <div class="login-body">
            <h5 class="fw-bold mb-1">Welcome back</h5>
            <p class="text-muted small mb-4">Enter your credentials to access the dashboard</p>

            <form id="login-frm" novalidate>
              <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" name="username" class="form-control icon-input" placeholder="Enter username" required>
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password" class="form-control icon-input" placeholder="Enter password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button class="btn btn-primary btn-login w-100 text-white" type="submit">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
              </button>
            </form>
          </div>
        </div>

        <div class="footer-credit">
          &copy; <?= date('Y') ?> Smart Asset Finder &mdash; Built by Frederick Opoku Afriyie
        </div>

      </div>
    </div>
  </div>

<script src="<?= base_url ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url ?>assets/js/main.js"></script>
<script>
$(document).ready(function(){
  end_loader();
  $('#togglePwd').on('click', function(){
    var pwd = $('input[name=password]');
    var icon = $(this).find('i');
    if(pwd.attr('type') === 'password'){
      pwd.attr('type','text');
      icon.removeClass('bi-eye').addClass('bi-eye-slash');
    } else {
      pwd.attr('type','password');
      icon.removeClass('bi-eye-slash').addClass('bi-eye');
    }
  });
});
</script>
</body>
</html>
