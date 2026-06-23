<?php
if(!class_exists('DBConnection')){
	require_once('../config.php');
	require_once('DBConnection.php');
}
class SystemSettings extends DBConnection {
	public function __construct(){
		parent::__construct();
	}
	function __destruct(){}

	function load_system_info(){
		$sql = "SELECT * FROM system_info";
		$qry = $this->conn->query($sql);
		while($row = $qry->fetch_assoc()){
			$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
		}
	}

	function update_system_info(){
		$sql = "SELECT * FROM system_info";
		$qry = $this->conn->query($sql);
		while($row = $qry->fetch_assoc()){
			$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
		}
		return true;
	}

	function update_settings_info(){
		$resp = ['status' => 'failed'];
		$allowed_fields = ['name','short_name','phone','mobile','email','address','site_tagline','social_facebook','social_twitter','social_instagram','social_linkedin'];

		foreach($_POST as $key => $value){
			if(!in_array($key, $allowed_fields)) continue;
			$value = trim(strip_tags($value));
			if(isset($_SESSION['system_info'][$key])){
				$stmt = $this->conn->prepare("UPDATE system_info SET meta_value=? WHERE meta_field=?");
				$stmt->bind_param('ss', $value, $key);
				$stmt->execute();
				$stmt->close();
			} else {
				$stmt = $this->conn->prepare("INSERT INTO system_info (meta_value, meta_field) VALUES (?,?)");
				$stmt->bind_param('ss', $value, $key);
				$stmt->execute();
				$stmt->close();
			}
		}

		// Logo upload
		if(!empty($_FILES['img']['tmp_name'])){
			$result = $this->upload_image($_FILES['img'], 'uploads/logo.png', 200, 200);
			if($result['success']){
				$stmt = $this->conn->prepare("UPDATE system_info SET meta_value=? WHERE meta_field='logo'");
				$v = $result['path'].'?v='.time();
				$stmt->bind_param('s', $v);
				$stmt->execute();
				$stmt->close();
			}
		}

		// Cover upload
		if(!empty($_FILES['cover']['tmp_name'])){
			$result = $this->upload_image($_FILES['cover'], 'uploads/cover.png', 1920, 600);
			if($result['success']){
				$stmt = $this->conn->prepare("UPDATE system_info SET meta_value=? WHERE meta_field='cover'");
				$v = $result['path'].'?v='.time();
				$stmt->bind_param('s', $v);
				$stmt->execute();
				$stmt->close();
			}
		}

		// Banner uploads
		if(!empty($_FILES['banners']['tmp_name'])){
			$banner_dir = base_app.'uploads/banner/';
			if(!is_dir($banner_dir)) mkdir($banner_dir, 0755, true);
			foreach($_FILES['banners']['tmp_name'] as $k => $tmp){
				if(empty($tmp)) continue;
				$file = [
					'tmp_name' => $_FILES['banners']['tmp_name'][$k],
					'type'     => $_FILES['banners']['type'][$k],
					'name'     => $_FILES['banners']['name'][$k],
				];
				$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
				$dest = 'uploads/banner/'.$safe_name;
				$i = 1;
				while(is_file(base_app.$dest)){
					$dest = 'uploads/banner/'.$i.'_'.$safe_name;
					$i++;
				}
				$this->upload_image($file, $dest, 1200, 480);
			}
		}

		$this->update_system_info();
		$this->set_flashdata('success','System settings updated successfully.');
		$resp['status'] = 'success';
		return json_encode($resp);
	}

	private function upload_image($file, $dest_path, $max_w, $max_h){
		$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
		if(!in_array($file['type'], $allowed_types)){
			return ['success' => false, 'error' => 'Invalid file type'];
		}
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$real_type = $finfo->file($file['tmp_name']);
		if(!in_array($real_type, $allowed_types)){
			return ['success' => false, 'error' => 'Invalid file content'];
		}
		switch($real_type){
			case 'image/jpeg': $img = imagecreatefromjpeg($file['tmp_name']); break;
			case 'image/png':  $img = imagecreatefrompng($file['tmp_name']); break;
			case 'image/webp': $img = imagecreatefromwebp($file['tmp_name']); break;
			default: return ['success' => false, 'error' => 'Unsupported type'];
		}
		if(!$img) return ['success' => false, 'error' => 'Could not process image'];

		[$w, $h] = getimagesize($file['tmp_name']);
		if($w > $max_w || $h > $max_h){
			$ratio = min($max_w/$w, $max_h/$h);
			$w = (int)($w * $ratio);
			$h = (int)($h * $ratio);
		}
		$thumb = imagescale($img, $w, $h);
		$full_path = base_app.$dest_path;
		if(is_file($full_path)) unlink($full_path);
		imagepng($thumb, $full_path);
		imagedestroy($img);
		imagedestroy($thumb);
		return ['success' => true, 'path' => $dest_path];
	}

	function set_userdata($field='', $value=''){
		if(!empty($field)){
			$_SESSION['userdata'][$field] = $value;
		}
	}
	function userdata($field=''){
		return isset($_SESSION['userdata'][$field]) ? $_SESSION['userdata'][$field] : null;
	}
	function set_flashdata($flash='', $value=''){
		if(!empty($flash) && !empty($value)){
			$_SESSION['flashdata'][$flash] = $value;
			return true;
		}
	}
	function chk_flashdata($flash=''){
		return isset($_SESSION['flashdata'][$flash]);
	}
	function flashdata($flash=''){
		if(!empty($flash) && isset($_SESSION['flashdata'][$flash])){
			$tmp = $_SESSION['flashdata'][$flash];
			unset($_SESSION['flashdata'][$flash]);
			return $tmp;
		}
		return false;
	}
	function sess_des(){
		unset($_SESSION['userdata']);
		return true;
	}
	function info($field=''){
		return (!empty($field) && isset($_SESSION['system_info'][$field])) ? $_SESSION['system_info'][$field] : false;
	}
	function set_info($field='', $value=''){
		if(!empty($field)) $_SESSION['system_info'][$field] = $value;
	}
	function csrf_token(){
		if(empty($_SESSION['csrf_token'])){
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}
	function verify_csrf($token=''){
		return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
	}
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = isset($_GET['f']) ? strtolower($_GET['f']) : 'none';
switch($action){
	case 'update_settings':
		$sysset = new SystemSettings();
		echo $sysset->update_settings_info();
		break;
}
?>
