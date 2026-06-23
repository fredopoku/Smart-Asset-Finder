<?php
require_once '../config.php';
require_once __DIR__.'/Mailer.php';
require_once __DIR__.'/Whatsapp.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}

	// ── Admin / Staff Login ───────────────────────────────────────────────
	public function login(){
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';

		if(empty($username) || empty($password)){
			return json_encode(['status'=>'failed','msg'=>'Username and password are required.']);
		}

		$stmt = $this->conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();

		if($result->num_rows === 0){
			return json_encode(['status'=>'incorrect','msg'=>'Invalid username or password.']);
		}

		$data = $result->fetch_assoc();
		if(!password_verify($password, $data['password'])){
			return json_encode(['status'=>'incorrect','msg'=>'Invalid username or password.']);
		}

		// Regenerate session to prevent fixation
		safe_session_regenerate();

		foreach($data as $k => $v){
			if(!is_numeric($k) && $k !== 'password'){
				$this->settings->set_userdata($k, $v);
			}
		}
		$this->settings->set_userdata('login_type', 1);

		// Update last_login
		$stmt2 = $this->conn->prepare("UPDATE users SET last_login=NOW() WHERE id=?");
		$stmt2->bind_param('i', $data['id']);
		$stmt2->execute();
		$stmt2->close();

		return json_encode(['status'=>'success']);
	}

	public function logout(){
		safe_session_regenerate();
		$this->settings->sess_des();
		redirect('admin/login.php');
	}

	// ── Public User Registration ──────────────────────────────────────────
	public function register(){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
		if(!rate_limit('register', $ip, 5, 900)){
			return json_encode(['status'=>'failed','msg'=>'Too many registration attempts. Please try again in 15 minutes.']);
		}

		$firstname = trim($_POST['firstname'] ?? '');
		$lastname  = trim($_POST['lastname'] ?? '');
		$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$phone     = trim($_POST['phone'] ?? '');
		$password  = $_POST['password'] ?? '';
		$confirm   = $_POST['confirm_password'] ?? '';

		if(!$firstname || !$lastname || !$email || !$password){
			return json_encode(['status'=>'failed','msg'=>'All fields are required.']);
		}
		if(strlen($password) < 8){
			return json_encode(['status'=>'failed','msg'=>'Password must be at least 8 characters.']);
		}
		if($password !== $confirm){
			return json_encode(['status'=>'failed','msg'=>'Passwords do not match.']);
		}

		// Check duplicate email
		$chk = $this->conn->prepare("SELECT id FROM registered_users WHERE email=?");
		$chk->bind_param('s', $email);
		$chk->execute();
		if($chk->get_result()->num_rows > 0){
			return json_encode(['status'=>'failed','msg'=>'An account with this email already exists.']);
		}
		$chk->close();

		$hash  = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
		$token = bin2hex(random_bytes(32));

		$stmt = $this->conn->prepare("INSERT INTO registered_users (firstname,lastname,email,phone,password,verification_token) VALUES (?,?,?,?,?,?)");
		$stmt->bind_param('ssssss', $firstname, $lastname, $email, $phone, $hash, $token);
		$stmt->execute();
		$stmt->close();

		// Auto-login after registration (unverified)
		$new_uid = $this->conn->insert_id;
		safe_session_regenerate();
		$_SESSION['pub_userdata'] = [
			'id'            => $new_uid,
			'firstname'     => $firstname,
			'lastname'      => $lastname,
			'email'         => $email,
			'phone'         => $phone,
			'email_verified'=> 0,
			'points'        => 10,
		];

		// Award registration points + 3 free QR tags
		award_points($new_uid, 10, 'register', 'Welcome bonus — account created');
		generate_qr_tags($new_uid, 3);

		// Referral credit — ?page=register&ref=base64(uid)
		$ref_raw = $_POST['ref'] ?? $_GET['ref'] ?? ($_SESSION['saf_ref'] ?? '');
		if($ref_raw){
			$ref_uid = (int)base64_decode(strtr($ref_raw, '-_', '+/'));
			if($ref_uid > 0 && $ref_uid !== $new_uid){
				// Verify referrer exists and is active
				$chkr = $this->conn->prepare("SELECT id FROM registered_users WHERE id=? AND status=1 LIMIT 1");
				$chkr->bind_param('i', $ref_uid);
				$chkr->execute();
				if($chkr->get_result()->num_rows > 0){
					award_points($ref_uid, 25, 'referral', 'Friend registered using your referral link', $new_uid);
				}
				$chkr->close();
			}
			unset($_SESSION['saf_ref']);
		}

		// Send verification email + WhatsApp welcome (non-blocking)
		$verifyUrl = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/').'?page=verify-email&token='.$token;
		Mailer::verifyEmail($email, $firstname.' '.$lastname, $verifyUrl);
		if($phone) Whatsapp::welcome($phone, $firstname);

		return json_encode(['status'=>'success','msg'=>'Account created! Please check your email to verify your address.']);
	}

	// ── Public User Login ─────────────────────────────────────────────────
	public function login_user(){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
		if(!rate_limit('login', $ip, 10, 300)){
			return json_encode(['status'=>'failed','msg'=>'Too many login attempts. Please try again in 5 minutes.']);
		}

		$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$password = $_POST['password'] ?? '';

		if(!$email || !$password){
			return json_encode(['status'=>'failed','msg'=>'Email and password are required.']);
		}

		$stmt = $this->conn->prepare("SELECT * FROM registered_users WHERE email=? AND status=1 LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();

		if($result->num_rows === 0){
			return json_encode(['status'=>'failed','msg'=>'Invalid email or password.']);
		}

		$data = $result->fetch_assoc();
		if(!password_verify($password, $data['password'])){
			return json_encode(['status'=>'failed','msg'=>'Invalid email or password.']);
		}

		safe_session_regenerate();
		$_SESSION['pub_userdata'] = [
			'id'             => $data['id'],
			'firstname'      => $data['firstname'],
			'lastname'       => $data['lastname'],
			'email'          => $data['email'],
			'phone'          => $data['phone'],
			'avatar'         => $data['avatar'],
			'email_verified' => (int)$data['email_verified'],
		];
		$this->settings->set_flashdata('success', 'Welcome back, '.$data['firstname'].'!');
		return json_encode(['status'=>'success']);
	}

	// ── Public User Logout ────────────────────────────────────────────────
	public function logout_user(){
		unset($_SESSION['pub_userdata']);
		safe_session_regenerate();
		redirect('?page=login');
	}

	// ── Email Verification ───────────────────────────────────────────────────
	public function verify_email(){
		$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? $_POST['token'] ?? '');
		if(!$token) return json_encode(['status'=>'failed','msg'=>'Invalid verification link.']);

		$stmt = $this->conn->prepare("SELECT id, firstname, lastname, email, phone, avatar FROM registered_users WHERE verification_token=? AND email_verified=0 LIMIT 1");
		$stmt->bind_param('s', $token);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if(!$user) return json_encode(['status'=>'already','msg'=>'This link is invalid or your email is already verified.']);

		$upd = $this->conn->prepare("UPDATE registered_users SET email_verified=1, verification_token=NULL WHERE id=?");
		$upd->bind_param('i', $user['id']);
		$upd->execute();
		$upd->close();

		// Award email verification points + badge
		award_points((int)$user['id'], 10, 'email_verified', 'Email address verified');

		// Update session if the user is currently logged in
		if(isset($_SESSION['pub_userdata']) && $_SESSION['pub_userdata']['id'] == $user['id']){
			$_SESSION['pub_userdata']['email_verified'] = 1;
		}

		// Send the welcome email now that they're confirmed
		Mailer::welcome($user['email'], $user['firstname'].' '.$user['lastname']);

		return json_encode(['status'=>'success','msg'=>'Email verified! Your account is fully active.']);
	}

	public function resend_verification(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		if($_SESSION['pub_userdata']['email_verified'] ?? 0) return json_encode(['status'=>'failed','msg'=>'Already verified.']);

		$uid = (int)$_SESSION['pub_userdata']['id'];
		$token = bin2hex(random_bytes(32));

		$upd = $this->conn->prepare("UPDATE registered_users SET verification_token=? WHERE id=? AND email_verified=0");
		$upd->bind_param('si', $token, $uid);
		$upd->execute();
		if($this->conn->affected_rows === 0) return json_encode(['status'=>'failed','msg'=>'Already verified.']);
		$upd->close();

		$email = $_SESSION['pub_userdata']['email'];
		$name  = $_SESSION['pub_userdata']['firstname'].' '.$_SESSION['pub_userdata']['lastname'];
		$verifyUrl = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/').'?page=verify-email&token='.$token;
		Mailer::verifyEmail($email, $name, $verifyUrl);

		return json_encode(['status'=>'success','msg'=>'Verification email resent. Check your inbox.']);
	}

	// ── Password Reset (public) ──────────────────────────────────────────────
	public function request_reset(){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
		if(!rate_limit('reset', $ip, 5, 900)){
			return json_encode(['status'=>'failed','msg'=>'Too many reset requests. Please try again in 15 minutes.']);
		}

		$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
		if(!$email) return json_encode(['status'=>'failed','msg'=>'Please enter a valid email address.']);

		$stmt = $this->conn->prepare("SELECT id, firstname, lastname FROM registered_users WHERE email=? AND status=1 LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if(!$user) return json_encode(['status'=>'success','msg'=>'If that email is registered, a reset link has been sent.']);

		$del = $this->conn->prepare("DELETE FROM password_resets WHERE email=?");
		$del->bind_param('s', $email);
		$del->execute();
		$del->close();

		$token   = bin2hex(random_bytes(32));
		$expires = date('Y-m-d H:i:s', strtotime('+60 minutes'));
		$ins = $this->conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
		$ins->bind_param('sss', $email, $token, $expires);
		$ins->execute();
		$ins->close();

		$resetUrl = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/').'?page=reset-password&token='.$token;
		Mailer::passwordReset($email, $user['firstname'].' '.$user['lastname'], $resetUrl);

		return json_encode(['status'=>'success','msg'=>'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).']);
	}

	public function do_reset(){
		$token    = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? '');
		$password = $_POST['password'] ?? '';
		$confirm  = $_POST['confirm_password'] ?? '';

		if(!$token || !$password) return json_encode(['status'=>'failed','msg'=>'All fields are required.']);
		if(strlen($password) < 8)  return json_encode(['status'=>'failed','msg'=>'Password must be at least 8 characters.']);
		if($password !== $confirm) return json_encode(['status'=>'failed','msg'=>'Passwords do not match.']);

		$stmt = $this->conn->prepare("SELECT email FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1");
		$stmt->bind_param('s', $token);
		$stmt->execute();
		$reset = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if(!$reset) return json_encode(['status'=>'failed','msg'=>'This reset link is invalid or has expired. Please request a new one.']);

		$hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
		$upd  = $this->conn->prepare("UPDATE registered_users SET password=? WHERE email=?");
		$upd->bind_param('ss', $hash, $reset['email']);
		$upd->execute();
		$upd->close();

		$mark = $this->conn->prepare("UPDATE password_resets SET used=1 WHERE token=?");
		$mark->bind_param('s', $token);
		$mark->execute();
		$mark->close();

		return json_encode(['status'=>'success','msg'=>'Password reset successfully! You can now sign in with your new password.']);
	}

	// ── Change Password (admin) ───────────────────────────────────────────
	public function update_profile(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		$id        = (int)$_SESSION['pub_userdata']['id'];
		$firstname = trim($_POST['firstname'] ?? '');
		$lastname  = trim($_POST['lastname']  ?? '');
		$phone     = trim($_POST['phone']     ?? '');

		if(!$firstname || !$lastname) return json_encode(['status'=>'failed','msg'=>'Name is required.']);
		$firstname = substr(ucwords(strtolower($firstname)), 0, 60);
		$lastname  = substr(ucwords(strtolower($lastname)),  0, 60);
		$phone     = substr(preg_replace('/[^0-9+\-\s()]/', '', $phone), 0, 20);

		$u = $this->conn->prepare("UPDATE registered_users SET firstname=?, lastname=?, phone=? WHERE id=?");
		$u->bind_param('sssi', $firstname, $lastname, $phone, $id);
		$u->execute();
		$u->close();

		$_SESSION['pub_userdata']['firstname'] = $firstname;
		$_SESSION['pub_userdata']['lastname']  = $lastname;
		$_SESSION['pub_userdata']['phone']     = $phone;

		return json_encode(['status'=>'success','msg'=>'Profile updated.']);
	}

	public function upload_avatar(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		$id = (int)$_SESSION['pub_userdata']['id'];

		if(empty($_FILES['avatar']['tmp_name'])){
			return json_encode(['status'=>'failed','msg'=>'No file uploaded.']);
		}
		$file  = $_FILES['avatar'];
		$ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$allow = ['jpg','jpeg','png','gif','webp'];
		if(!in_array($ext, $allow)){
			return json_encode(['status'=>'failed','msg'=>'Only JPG, PNG, GIF, WEBP allowed.']);
		}
		if($file['size'] > 3 * 1024 * 1024){
			return json_encode(['status'=>'failed','msg'=>'Image must be under 3 MB.']);
		}
		$dir  = base_app . 'uploads/avatars/';
		if(!is_dir($dir)) mkdir($dir, 0775, true);

		// Remove old avatar
		$old = $this->conn->prepare("SELECT avatar FROM registered_users WHERE id=? LIMIT 1");
		$old->bind_param('i', $id); $old->execute();
		$oldrow = $old->get_result()->fetch_assoc(); $old->close();
		if(!empty($oldrow['avatar'])){
			$oldpath = base_app . ltrim($oldrow['avatar'], '/');
			if(is_file($oldpath)) unlink($oldpath);
		}

		$filename = 'avatar_' . $id . '_' . time() . '.' . $ext;
		$dest     = $dir . $filename;
		if(!move_uploaded_file($file['tmp_name'], $dest)){
			return json_encode(['status'=>'failed','msg'=>'Upload failed. Check folder permissions.']);
		}
		$path = 'uploads/avatars/' . $filename;
		$u = $this->conn->prepare("UPDATE registered_users SET avatar=? WHERE id=?");
		$u->bind_param('si', $path, $id); $u->execute(); $u->close();

		$_SESSION['pub_userdata']['avatar'] = $path;
		return json_encode(['status'=>'success','msg'=>'Photo updated.','path'=> base_url . $path]);
	}

	public function set_avatar_preset(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		$id     = (int)$_SESSION['pub_userdata']['id'];
		$preset = preg_replace('/[^a-z0-9\-]/', '', $_POST['preset'] ?? '');
		if(!$preset) return json_encode(['status'=>'failed','msg'=>'Invalid preset.']);

		$src = base_app . 'assets/img/avatars/preset/' . $preset . '.svg';
		if(!is_file($src)) return json_encode(['status'=>'failed','msg'=>'Preset not found.']);

		$dir  = base_app . 'uploads/avatars/';
		if(!is_dir($dir)) mkdir($dir, 0775, true);

		// Remove old avatar
		$old = $this->conn->prepare("SELECT avatar FROM registered_users WHERE id=? LIMIT 1");
		$old->bind_param('i', $id); $old->execute();
		$oldrow = $old->get_result()->fetch_assoc(); $old->close();
		if(!empty($oldrow['avatar']) && strpos($oldrow['avatar'], 'preset/') === false){
			$oldpath = base_app . ltrim($oldrow['avatar'], '/');
			if(is_file($oldpath)) unlink($oldpath);
		}

		$dest = $dir . 'avatar_' . $id . '_preset.svg';
		copy($src, $dest);
		$path = 'uploads/avatars/avatar_' . $id . '_preset.svg';

		$u = $this->conn->prepare("UPDATE registered_users SET avatar=? WHERE id=?");
		$u->bind_param('si', $path, $id); $u->execute(); $u->close();
		$_SESSION['pub_userdata']['avatar'] = $path;
		return json_encode(['status'=>'success','msg'=>'Avatar updated.','path'=> base_url . $path . '?t=' . time()]);
	}

	public function remove_avatar(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		$id = (int)$_SESSION['pub_userdata']['id'];
		$old = $this->conn->prepare("SELECT avatar FROM registered_users WHERE id=? LIMIT 1");
		$old->bind_param('i', $id); $old->execute();
		$oldrow = $old->get_result()->fetch_assoc(); $old->close();
		if(!empty($oldrow['avatar'])){
			$oldpath = base_app . ltrim($oldrow['avatar'], '/');
			if(is_file($oldpath)) unlink($oldpath);
		}
		$n = null;
		$u = $this->conn->prepare("UPDATE registered_users SET avatar=NULL WHERE id=?");
		$u->bind_param('i', $id); $u->execute(); $u->close();
		$_SESSION['pub_userdata']['avatar'] = null;
		return json_encode(['status'=>'success','msg'=>'Photo removed.']);
	}

	public function change_password(){
		$id          = (int)$this->settings->userdata('id');
		$old         = $_POST['old_password'] ?? '';
		$new         = $_POST['new_password'] ?? '';
		$confirm     = $_POST['confirm_password'] ?? '';

		if(!$old || !$new || !$confirm){
			return json_encode(['status'=>'failed','msg'=>'All fields are required.']);
		}
		if(strlen($new) < 8){
			return json_encode(['status'=>'failed','msg'=>'New password must be at least 8 characters.']);
		}
		if($new !== $confirm){
			return json_encode(['status'=>'failed','msg'=>'New passwords do not match.']);
		}

		$stmt = $this->conn->prepare("SELECT password FROM users WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if(!$row || !password_verify($old, $row['password'])){
			return json_encode(['status'=>'failed','msg'=>'Current password is incorrect.']);
		}

		$hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
		$u = $this->conn->prepare("UPDATE users SET password=? WHERE id=?");
		$u->bind_param('si', $hash, $id);
		$u->execute();
		$u->close();

		return json_encode(['status'=>'success','msg'=>'Password changed successfully.']);
	}
}

$action = isset($_GET['f']) ? strtolower($_GET['f']) : 'none';
$auth = new Login();

// GET-only actions that don't change state — skip CSRF
$csrf_exempt = ['verify_email', 'resend_verification'];
if(!in_array($action, $csrf_exempt)) csrf_check();

switch($action){
	case 'login':            echo $auth->login(); break;
	case 'logout':           $auth->logout(); break;
	case 'register':         echo $auth->register(); break;
	case 'login_user':       echo $auth->login_user(); break;
	case 'logout_user':      $auth->logout_user(); break;
	case 'update_profile':      echo $auth->update_profile(); break;
	case 'upload_avatar':       echo $auth->upload_avatar();  break;
	case 'remove_avatar':       echo $auth->remove_avatar();  break;
	case 'set_avatar_preset':   echo $auth->set_avatar_preset(); break;
	case 'change_password':  echo $auth->change_password(); break;
	case 'request_reset':         echo $auth->request_reset(); break;
	case 'do_reset':              echo $auth->do_reset(); break;
	case 'verify_email':          echo $auth->verify_email(); break;
	case 'resend_verification':   echo $auth->resend_verification(); break;
	default: echo "<h1>Access Denied</h1><a href='".base_url."'>Go Back</a>"; break;
}
