<?php
require_once('../config.php');

// Admin-only endpoint
if(empty($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
    http_response_code(403);
    exit(json_encode(['status'=>'failed','msg'=>'Unauthorized']));
}

Class Users extends DBConnection {
    private $settings;
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    public function __destruct(){
        parent::__destruct();
    }

    public function save_users(){
        csrf_check();

        $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename= trim($_POST['middlename'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $type      = isset($_POST['type']) ? (int)$_POST['type'] : 2;
        $password  = $_POST['password'] ?? '';

        if(empty($firstname) || empty($lastname) || empty($username)){
            return 2;
        }

        // Check username uniqueness
        $chk = $this->conn->prepare("SELECT id FROM users WHERE username=? AND id!=? LIMIT 1");
        $chk->bind_param('si', $username, $id);
        $chk->execute();
        if($chk->get_result()->num_rows > 0){ $chk->close(); return 2; }
        $chk->close();

        if($id === 0){
            // INSERT
            if(empty($password)) return 2;
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (firstname, middlename, lastname, username, password, type) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('sssssi', $firstname, $middlename, $lastname, $username, $hash, $type);
            if(!$stmt->execute()){ $stmt->close(); return 2; }
            $id = $this->conn->insert_id;
            $stmt->close();
        } else {
            // UPDATE
            if(!empty($password)){
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("UPDATE users SET firstname=?,middlename=?,lastname=?,username=?,password=?,type=? WHERE id=?");
                $stmt->bind_param('sssssii', $firstname, $middlename, $lastname, $username, $hash, $type, $id);
            } else {
                $stmt = $this->conn->prepare("UPDATE users SET firstname=?,middlename=?,lastname=?,username=?,type=? WHERE id=?");
                $stmt->bind_param('ssssii', $firstname, $middlename, $lastname, $username, $type, $id);
            }
            if(!$stmt->execute()){ $stmt->close(); return 2; }
            $stmt->close();
        }

        // Sync session if editing own profile
        if($this->settings->userdata('id') == $id){
            $this->settings->set_userdata('firstname', $firstname);
            $this->settings->set_userdata('lastname', $lastname);
            $this->settings->set_userdata('username', $username);
        }

        // Avatar upload
        if(!empty($_FILES['img']['tmp_name'])){
            if(!is_dir(base_app.'uploads/avatars'))
                mkdir(base_app.'uploads/avatars', 0755, true);
            $fname  = "uploads/avatars/$id.png";
            $finfo  = new finfo(FILEINFO_MIME_TYPE);
            $mime   = $finfo->file($_FILES['img']['tmp_name']);
            if($mime === 'image/jpeg'){
                $src = imagecreatefromjpeg($_FILES['img']['tmp_name']);
            } elseif($mime === 'image/png'){
                $src = imagecreatefrompng($_FILES['img']['tmp_name']);
            } else {
                $src = false;
            }
            if($src){
                $thumb = imagescale($src, 200, 200);
                if(is_file(base_app.$fname)) unlink(base_app.$fname);
                if(imagepng($thumb, base_app.$fname)){
                    $v = time();
                    $av_stmt = $this->conn->prepare("UPDATE users SET avatar=CONCAT(?, '?v=', UNIX_TIMESTAMP()) WHERE id=?");
                    $av_stmt->bind_param('si', $fname, $id);
                    $av_stmt->execute();
                    $av_stmt->close();
                    if($this->settings->userdata('id') == $id)
                        $this->settings->set_userdata('avatar', "$fname?v=$v");
                }
                imagedestroy($thumb);
                imagedestroy($src);
            }
        }

        $this->settings->set_flashdata('success', 'User details successfully saved.');
        return 1;
    }

    public function delete_users(){
        csrf_check();
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if(!$id) return false;
        // Cannot delete yourself
        if($this->settings->userdata('id') == $id) return false;

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        if($stmt->execute()){
            $stmt->close();
            $this->settings->set_flashdata('success', 'User successfully deleted.');
            $avatar = base_app."uploads/avatars/$id.png";
            if(is_file($avatar)) unlink($avatar);
            return 1;
        }
        $stmt->close();
        return false;
    }
}

$users = new Users();
$action = isset($_GET['f']) ? strtolower($_GET['f']) : 'none';
switch($action){
    case 'save':   echo $users->save_users();  break;
    case 'delete': echo $users->delete_users(); break;
}
