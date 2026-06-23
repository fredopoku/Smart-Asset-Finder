<?php
require_once('../config.php');
require_once __DIR__.'/Mailer.php';
require_once __DIR__.'/Whatsapp.php';
require_once __DIR__.'/AiMatcher.php';
class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}

	// ── Categories ──────────────────────────────────────────────────────
	function save_category(){
		$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$name        = trim($_POST['name'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$status      = isset($_POST['status']) ? (int)$_POST['status'] : 1;

		if(empty($name)){
			return json_encode(['status'=>'failed','msg'=>'Category name is required.']);
		}

		// Duplicate check
		$stmt = $this->conn->prepare("SELECT id FROM category_list WHERE name=? AND id!=?");
		$stmt->bind_param('si', $name, $id);
		$stmt->execute();
		if($stmt->get_result()->num_rows > 0){
			return json_encode(['status'=>'failed','msg'=>'Category name already exists.']);
		}
		$stmt->close();

		if($id === 0){
			$stmt = $this->conn->prepare("INSERT INTO category_list (name,description,status) VALUES (?,?,?)");
			$stmt->bind_param('ssi', $name, $description, $status);
		} else {
			$stmt = $this->conn->prepare("UPDATE category_list SET name=?,description=?,status=? WHERE id=?");
			$stmt->bind_param('ssii', $name, $description, $status, $id);
		}
		$stmt->execute();
		$sid = $id ?: $this->conn->insert_id;
		$stmt->close();

		$msg = $id ? 'Category updated successfully.' : 'Category created successfully.';
		$this->settings->set_flashdata('success', $msg);
		return json_encode(['status'=>'success','sid'=>$sid,'msg'=>$msg]);
	}

	function delete_category(){
		$id = (int)($_POST['id'] ?? 0);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);
		$stmt = $this->conn->prepare("DELETE FROM category_list WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		$this->settings->set_flashdata('success', 'Category deleted successfully.');
		return json_encode(['status'=>'success']);
	}

	// ── Items ────────────────────────────────────────────────────────────
	function save_item(){
		$id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$category_id   = (int)($_POST['category_id'] ?? 0);
		$fullname      = trim($_POST['fullname'] ?? '');
		$title         = trim($_POST['title'] ?? '');
		$contact       = trim($_POST['contact'] ?? '');
		$description   = trim($_POST['description'] ?? '');
		$location      = trim($_POST['location'] ?? '');
		$item_type     = isset($_POST['item_type']) ? (int)$_POST['item_type'] : 1;
		$date_lf       = !empty($_POST['date_lost_found']) ? $_POST['date_lost_found'] : null;
		$is_public     = isset($_POST['founder']);
		$status        = $is_public ? 0 : (int)($_POST['status'] ?? 1);
		$user_id       = isset($_SESSION['pub_userdata']['id']) ? (int)$_SESSION['pub_userdata']['id'] : null;
		$serial_number    = !empty($_POST['serial_number'])    ? substr(trim($_POST['serial_number']), 0, 255) : null;
		$ownership_notes  = !empty($_POST['ownership_notes'])  ? trim($_POST['ownership_notes'])               : null;
		$lat              = is_numeric($_POST['lat'] ?? '') ? (float)$_POST['lat'] : null;
		$lng              = is_numeric($_POST['lng'] ?? '') ? (float)$_POST['lng'] : null;
		$is_travelling    = isset($_POST['is_travelling']) ? 1 : 0;
		$location_country = !empty($_POST['location_country']) ? trim($_POST['location_country']) : null;

		if(empty($title) || empty($fullname) || !$category_id){
			return json_encode(['status'=>'failed','msg'=>'Title, reporter name, and category are required.']);
		}

		// AI fraud check (new public submissions only)
		if($id === 0 && $is_public){
			$ai = new AiMatcher($this->conn);
			$check = $ai->checkItemReport(['title'=>$title,'description'=>$description], $user_id);
			if(!$check['ok']){
				return json_encode(['status'=>'failed','msg'=>$check['reason']]);
			}
		}

		if($id === 0){
			$stmt = $this->conn->prepare(
				"INSERT INTO item_list (user_id,category_id,item_type,fullname,title,contact,description,location,lat,lng,is_travelling,location_country,date_lost_found,serial_number,ownership_notes,status)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
			);
			$stmt->bind_param('iiisssssddiisssi',
				$user_id,$category_id,$item_type,$fullname,$title,$contact,$description,
				$location,$lat,$lng,$is_travelling,$location_country,$date_lf,$serial_number,$ownership_notes,$status
			);
		} else {
			$stmt = $this->conn->prepare(
				"UPDATE item_list SET category_id=?,item_type=?,fullname=?,title=?,contact=?,description=?,location=?,lat=?,lng=?,is_travelling=?,location_country=?,date_lost_found=?,serial_number=?,ownership_notes=?,status=? WHERE id=?"
			);
			$stmt->bind_param('iisssssddissssii',
				$category_id,$item_type,$fullname,$title,$contact,$description,
				$location,$lat,$lng,$is_travelling,$location_country,$date_lf,$serial_number,$ownership_notes,$status,$id
			);
		}
		$stmt->execute();
		$iid = $id ?: $this->conn->insert_id;
		$stmt->close();

		// Multi-media upload (public form: media[], admin form: image)
		$files_to_process = [];
		if(!empty($_FILES['media']['tmp_name']) && is_array($_FILES['media']['tmp_name'])){
			for($i=0; $i<count($_FILES['media']['tmp_name']); $i++){
				if($_FILES['media']['error'][$i] === UPLOAD_ERR_OK){
					$files_to_process[] = [
						'tmp_name' => $_FILES['media']['tmp_name'][$i],
						'name'     => $_FILES['media']['name'][$i],
						'size'     => $_FILES['media']['size'][$i],
						'error'    => UPLOAD_ERR_OK,
					];
				}
			}
		} elseif(!empty($_FILES['image']['tmp_name'])){
			$files_to_process[] = $_FILES['image'];
		}

		$sort = 0;
		$first_image_set = false;
		foreach($files_to_process as $file){
			if($sort >= 5) break;
			$result = $this->upload_media_file($file, $iid, $sort);
			if(!$result['success']) continue;
			$path  = $result['path'];
			$mtype = $result['media_type'];
			$ins = $this->conn->prepare("INSERT INTO item_media (item_id,path,media_type,sort_order) VALUES (?,?,?,?)");
			$ins->bind_param('issi', $iid, $path, $mtype, $sort);
			$ins->execute();
			$ins->close();
			if($mtype === 'image' && !$first_image_set){
				$thumb = $path.'?v='.time();
				$u = $this->conn->prepare("UPDATE item_list SET image_path=? WHERE id=?");
				$u->bind_param('si', $thumb, $iid);
				$u->execute();
				$u->close();
				$first_image_set = true;
			}
			$sort++;
		}

		// Save security Q&A (lost items from public form, new submissions only)
		if($id === 0 && $is_public && $item_type === 0){
			$questions = $_POST['sec_q'] ?? [];
			$customs   = $_POST['sec_q_custom'] ?? [];
			$answers   = $_POST['sec_a'] ?? [];
			$ai = $ai ?? new AiMatcher($this->conn);
			$sort = 0;
			foreach($questions as $i => $q){
				$q_text = ($q === '__custom__') ? trim($customs[$i] ?? '') : trim($q);
				$a_text = trim($answers[$i] ?? '');
				if(empty($q_text) || empty($a_text)) continue;
				$a_norm = $ai->normalize($a_text);
				$ins = $this->conn->prepare(
					"INSERT INTO item_security_qa (item_id,question,answer_normalized,sort_order) VALUES (?,?,?,?)"
				);
				$ins->bind_param('issi', $iid, $q_text, $a_norm, $sort);
				$ins->execute();
				$ins->close();
				$sort++;
			}
		}

		// AI auto-match: if this is a found item, scan lost reports for matches
		if($id === 0 && $is_public && $item_type === 1){
			$ai = $ai ?? new AiMatcher($this->conn);
			$ai->findAndNotifyMatches($iid);
		}

		// Award points for first-time item submission (new items only, logged-in users)
		if($id === 0 && $is_public && $user_id){
			award_points($user_id, 10, 'item_submitted', 'Submitted a lost/found item report', $iid);
		}

		$msg = $is_public
			? 'Your submission has been received. We will review and publish it shortly.'
			: ($id ? 'Item updated successfully.' : 'Item created successfully.');
		$this->settings->set_flashdata('success', $msg);
		return json_encode(['status'=>'success','iid'=>$iid,'msg'=>$msg]);
	}

	function delete_item(){
		$id = (int)($_POST['id'] ?? 0);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);

		// Remove all media files
		$r = $this->conn->prepare("SELECT image_path FROM item_list WHERE id=?");
		$r->bind_param('i', $id);
		$r->execute();
		$row = $r->get_result()->fetch_assoc();
		$r->close();
		if($row && $row['image_path']){
			$file = base_app.explode('?', $row['image_path'])[0];
			if(is_file($file)) unlink($file);
		}
		// Remove item_media files
		$rm = $this->conn->prepare("SELECT path FROM item_media WHERE item_id=?");
		$rm->bind_param('i', $id);
		$rm->execute();
		$mfiles = $rm->get_result()->fetch_all(MYSQLI_ASSOC);
		$rm->close();
		foreach($mfiles as $mf){
			$fp = base_app.explode('?',$mf['path'])[0];
			if(is_file($fp)) unlink($fp);
		}
		$this->conn->query("DELETE FROM item_media WHERE item_id=$id");

		$stmt = $this->conn->prepare("DELETE FROM item_list WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		$this->settings->set_flashdata('success', 'Item deleted successfully.');
		return json_encode(['status'=>'success']);
	}

	function update_item_status(){
		$id     = (int)($_POST['id'] ?? 0);
		$status = (int)($_POST['status'] ?? 0);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);
		$stmt = $this->conn->prepare("UPDATE item_list SET status=? WHERE id=?");
		$stmt->bind_param('ii', $status, $id);
		$stmt->execute();
		$stmt->close();
		$labels = [0=>'Pending', 1=>'Published', 2=>'Claimed'];
		$msg = 'Item marked as '.($labels[$status] ?? 'Unknown').'.';
		$this->settings->set_flashdata('success', $msg);
		return json_encode(['status'=>'success','msg'=>$msg]);
	}

	// ── Inquiries ────────────────────────────────────────────────────────
	function save_inquiry(){
		$id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$fullname = trim($_POST['fullname'] ?? '');
		$contact  = trim($_POST['contact'] ?? '');
		$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$message  = trim($_POST['message'] ?? '');
		$is_visitor = isset($_POST['visitor']);

		if(!$email || empty($fullname) || empty($message)){
			return json_encode(['status'=>'failed','msg'=>'Name, valid email, and message are required.']);
		}

		if($id === 0){
			$stmt = $this->conn->prepare("INSERT INTO inquiry_list (fullname,contact,email,message) VALUES (?,?,?,?)");
			$stmt->bind_param('ssss', $fullname, $contact, $email, $message);
		} else {
			$stmt = $this->conn->prepare("UPDATE inquiry_list SET fullname=?,contact=?,email=?,message=?,status=1 WHERE id=?");
			$stmt->bind_param('ssssi', $fullname, $contact, $email, $message, $id);
		}
		$stmt->execute();
		$stmt->close();

		$msg = $is_visitor
			? 'Thank you! Your message has been sent. We\'ll get back to you shortly.'
			: 'Inquiry updated successfully.';
		$this->settings->set_flashdata('success', $msg);
		return json_encode(['status'=>'success','msg'=>$msg]);
	}

	function delete_inquiry(){
		$id = (int)($_POST['id'] ?? 0);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);
		$stmt = $this->conn->prepare("DELETE FROM inquiry_list WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		$this->settings->set_flashdata('success', 'Message deleted successfully.');
		return json_encode(['status'=>'success']);
	}

	function mark_inquiry_read(){
		$id = (int)($_POST['id'] ?? 0);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);
		$stmt = $this->conn->prepare("UPDATE inquiry_list SET status=1 WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
		return json_encode(['status'=>'success']);
	}

	// ── Claims ───────────────────────────────────────────────────────────
	function save_claim(){
		$item_id = (int)($_POST['item_id'] ?? 0);
		$fullname = trim($_POST['fullname'] ?? '');
		$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$phone    = trim($_POST['phone'] ?? '');
		$message  = trim($_POST['message'] ?? '');
		$user_id  = isset($_SESSION['pub_userdata']['id']) ? (int)$_SESSION['pub_userdata']['id'] : null;
		$sec_answers = $_POST['sec_answer'] ?? [];

		if(!$item_id || !$email || empty($fullname) || empty($message)){
			return json_encode(['status'=>'failed','msg'=>'All fields are required.']);
		}

		// Prevent duplicate claim
		$chk = $this->conn->prepare("SELECT id FROM item_claims WHERE item_id=? AND email=?");
		$chk->bind_param('is', $item_id, $email);
		$chk->execute();
		if($chk->get_result()->num_rows > 0){
			return json_encode(['status'=>'failed','msg'=>'You have already submitted a claim for this item.']);
		}
		$chk->close();

		// Score security answers
		$ai = new AiMatcher($this->conn);
		$sec_score = null;
		$vstatus   = 'pending';
		$sq_count_stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM item_security_qa WHERE item_id=?");
		$sq_count_stmt->bind_param('i', $item_id);
		$sq_count_stmt->execute();
		$sq_count = (int)$sq_count_stmt->get_result()->fetch_assoc()['cnt'];
		$sq_count_stmt->close();

		if($sq_count > 0 && !empty($sec_answers)){
			$sec_score = $ai->scoreClaimAnswers($item_id, $sec_answers);
			if($sec_score < 35){
				return json_encode(['status'=>'failed',
					'msg'=>'Your answers don\'t match the owner\'s security questions. If you believe this is yours, contact support with proof of ownership.']);
			}
			$vstatus = $sec_score >= 70 ? 'verified' : 'pending';
		} elseif($sq_count > 0 && empty($sec_answers)){
			return json_encode(['status'=>'failed','msg'=>'This item has ownership verification questions. Please answer them to proceed.']);
		}

		// Handle proof image upload
		$proof_path = null;
		if(!empty($_FILES['proof_image']['tmp_name']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK){
			$allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
			$mime = mime_content_type($_FILES['proof_image']['tmp_name']);
			if(in_array($mime, $allowed)){
				$ext  = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
				$dir  = base_app.'uploads/claims/';
				if(!is_dir($dir)) mkdir($dir, 0755, true);
				$fname = 'proof_'.$item_id.'_'.time().'.'.$ext;
				if(move_uploaded_file($_FILES['proof_image']['tmp_name'], $dir.$fname)){
					$proof_path = 'uploads/claims/'.$fname;
				}
			}
		}

		$stmt = $this->conn->prepare(
			"INSERT INTO item_claims (item_id,user_id,fullname,email,phone,message,security_score,proof_image,verification_status)
			 VALUES (?,?,?,?,?,?,?,?,?)"
		);
		$stmt->bind_param('iissssdss', $item_id,$user_id,$fullname,$email,$phone,$message,$sec_score,$proof_path,$vstatus);
		$stmt->execute();
		$stmt->close();

		// Fetch item + reporter info for notifications
		$it = $this->conn->prepare("SELECT il.title, il.user_id as owner_id FROM item_list il WHERE il.id=? LIMIT 1");
		$it->bind_param('i', $item_id);
		$it->execute();
		$itemRow = $it->get_result()->fetch_assoc();
		$it->close();
		$itemTitle = $itemRow['title'] ?? 'Unknown Item';
		$app_url   = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/');
		$dash_url  = $app_url.'?page=my-items';

		// Email claimant: acknowledgment
		Mailer::claimReceived($email, $fullname, $itemTitle);

		// Notify item reporter (owner) — email + WhatsApp
		if(!empty($itemRow['owner_id'])){
			$own = $this->conn->prepare("SELECT firstname, lastname, email, phone FROM registered_users WHERE id=? LIMIT 1");
			$own->bind_param('i', $itemRow['owner_id']);
			$own->execute();
			$owner = $own->get_result()->fetch_assoc();
			$own->close();
			if($owner){
				$owner_name = $owner['firstname'].' '.$owner['lastname'];
				Mailer::newClaimOnItem($owner['email'], $owner_name, $itemTitle, $fullname, (float)($sec_score ?? 0), $dash_url);
				if(!empty($owner['phone'])){
					Whatsapp::newClaim($owner['phone'], $owner['firstname'], $itemTitle, $fullname, $dash_url);
				}
			}
		}

		// Email admin: new claim alert
		$adminEmail = $this->settings->info('email') ?: saf_env('MAIL_FROM_ADDRESS', '');
		if($adminEmail){
			$claimUrl = $app_url.'admin/?page=claims';
			Mailer::newClaimAdmin($adminEmail, $fullname, $itemTitle, $claimUrl);
		}

		$msg = 'Your claim has been submitted. A confirmation has been sent to your email.';
		$this->settings->set_flashdata('success', $msg);
		return json_encode(['status'=>'success','msg'=>$msg]);
	}

	function update_claim_status(){
		$id     = (int)($_POST['id'] ?? 0);
		$status = (int)($_POST['status'] ?? 0);
		$note   = trim($_POST['admin_note'] ?? '');
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid ID']);

		// Fetch claim + item info before updating (needed for email + notification)
		$info = $this->conn->prepare("
			SELECT ic.fullname, ic.email, ic.user_id, il.title as item_title, ic.item_id
			FROM item_claims ic
			LEFT JOIN item_list il ON il.id = ic.item_id
			WHERE ic.id=? LIMIT 1");
		$info->bind_param('i', $id);
		$info->execute();
		$claim = $info->get_result()->fetch_assoc();
		$info->close();

		$stmt = $this->conn->prepare("UPDATE item_claims SET status=?,admin_note=? WHERE id=?");
		$stmt->bind_param('isi', $status, $note, $id);
		$stmt->execute();
		$stmt->close();

		// If approved, mark item as claimed
		$dash_url = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/').'?page=my-items';
		if($status === 1 && $claim){
			$u = $this->conn->prepare("UPDATE item_list SET status=2 WHERE id=?");
			$u->bind_param('i', $claim['item_id']);
			$u->execute();
			$u->close();
			Mailer::claimApproved($claim['email'], $claim['fullname'], $claim['item_title'], $note);
			// WhatsApp to claimant if registered
			if(!empty($claim['user_id'])){
				$wp = $this->conn->prepare("SELECT phone FROM registered_users WHERE id=? LIMIT 1");
				$wp->bind_param('i', $claim['user_id']); $wp->execute();
				$wp_row = $wp->get_result()->fetch_assoc(); $wp->close();
				if(!empty($wp_row['phone'])) Whatsapp::claimApproved($wp_row['phone'], $claim['fullname'], $claim['item_title'], $dash_url);
			}
		} elseif($status === 2 && $claim){
			Mailer::claimRejected($claim['email'], $claim['fullname'], $claim['item_title'], $note);
			if(!empty($claim['user_id'])){
				$wp = $this->conn->prepare("SELECT phone FROM registered_users WHERE id=? LIMIT 1");
				$wp->bind_param('i', $claim['user_id']); $wp->execute();
				$wp_row = $wp->get_result()->fetch_assoc(); $wp->close();
				if(!empty($wp_row['phone'])) Whatsapp::claimRejected($wp_row['phone'], $claim['fullname'], $claim['item_title'], $dash_url);
			}
		}

		// In-app notification for registered users
		if($claim && !empty($claim['user_id'])){
			if($status === 1){
				$nmsg  = "Your claim for \"{$claim['item_title']}\" has been approved! 🎉";
				$ntype = 'success';
			} else {
				$nmsg  = "Your claim for \"{$claim['item_title']}\" was not approved.";
				$ntype = 'danger';
			}
			$nlink = '?page=my-items';
			$ni = $this->conn->prepare("INSERT INTO notifications (user_id,message,type,link) VALUES (?,?,?,?)");
			$ni->bind_param('isss', $claim['user_id'], $nmsg, $ntype, $nlink);
			$ni->execute();
			$ni->close();
		}

		return json_encode(['status'=>'success','msg'=>'Claim status updated.']);
	}

	// ── Rewards: point redemption request ────────────────────────────────
	function redeem_points(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Please log in.']);
		$uid    = (int)$_SESSION['pub_userdata']['id'];
		$cost   = (int)($_POST['cost'] ?? 0);
		$reward = substr(trim($_POST['reward'] ?? ''), 0, 255);

		if($cost <= 0 || empty($reward)) return json_encode(['status'=>'failed','msg'=>'Invalid redemption.']);

		// Check balance
		$chk = $this->conn->prepare("SELECT points FROM registered_users WHERE id=? LIMIT 1");
		$chk->bind_param('i', $uid);
		$chk->execute();
		$bal = (int)($chk->get_result()->fetch_assoc()['points'] ?? 0);
		$chk->close();

		if($bal < $cost) return json_encode(['status'=>'failed','msg'=>"Not enough points. You have {$bal}, need {$cost}."]);

		// Deduct points
		$upd = $this->conn->prepare("UPDATE registered_users SET points = GREATEST(0, CAST(points AS SIGNED) - ?) WHERE id=?");
		$upd->bind_param('ii', $cost, $uid);
		$upd->execute();
		$upd->close();

		// Log transaction
		$log = $this->conn->prepare("INSERT INTO point_transactions (user_id, points, action, description) VALUES (?, ?, 'redeem', ?)");
		$neg = -$cost;
		$desc = "Redeemed for: {$reward}";
		$log->bind_param('iis', $uid, $neg, $desc);
		$log->execute();
		$log->close();

		// Notify admin (create an inquiry so admin knows to fulfil)
		$email = $_SESSION['pub_userdata']['email'] ?? '';
		$name  = ($_SESSION['pub_userdata']['firstname'] ?? '').' '.($_SESSION['pub_userdata']['lastname'] ?? '');
		$note  = "REWARDS REDEMPTION\nUser: {$name} ({$email})\nReward: {$reward}\nPoints deducted: {$cost}";
		$ni = $this->conn->prepare("INSERT INTO inquiry_list (fullname, email, message, date_added) VALUES (?,?,?,NOW())");
		$ni->bind_param('sss', $name, $email, $note);
		$ni->execute();
		$ni->close();

		return json_encode(['status'=>'success','msg'=>"Redeemed! Our team will contact you at {$email} within 48 hours."]);
	}

	// ── Notifications ────────────────────────────────────────────────────
	function get_notifications(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','data'=>[]]);
		$uid = (int)$_SESSION['pub_userdata']['id'];
		$stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 15");
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$stmt->close();
		$this->conn->query("UPDATE notifications SET is_read=1 WHERE user_id={$uid} AND is_read=0");
		return json_encode(['status'=>'success','data'=>$rows]);
	}

	// ── Admin: Registered Users ───────────────────────────────────────────
	function toggle_user_status(){
		if(!isset($_SESSION['userdata'])) return json_encode(['status'=>'failed','msg'=>'Unauthorized']);
		$id     = (int)($_POST['id'] ?? 0);
		$cur    = (int)($_POST['status'] ?? 1);
		$newst  = $cur === 1 ? 0 : 1;
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid user ID.']);
		$stmt = $this->conn->prepare("UPDATE registered_users SET status=? WHERE id=?");
		$stmt->bind_param('ii', $newst, $id);
		$stmt->execute();
		$stmt->close();
		$msg = $newst === 1 ? 'User activated successfully.' : 'User has been banned.';
		return json_encode(['status'=>'success','msg'=>$msg]);
	}

	// ── Pages ────────────────────────────────────────────────────────────
	function save_page(){
		if(!is_dir(base_app.'pages')) mkdir(base_app.'pages', 0755, true);
		if(isset($_POST['page']['welcome'])){
			file_put_contents(base_app.'pages/welcome.html', $_POST['page']['welcome']);
		}
		if(isset($_POST['page']['about'])){
			file_put_contents(base_app.'pages/about.html', $_POST['page']['about']);
		}
		$this->settings->set_flashdata('success', 'Page content updated successfully.');
		return json_encode(['status'=>'success']);
	}

	// ── Search ───────────────────────────────────────────────────────────
	function search_items(){
		$q    = trim($_POST['q'] ?? $_GET['q'] ?? '');
		$cid  = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
		$type = isset($_POST['item_type']) ? (int)$_POST['item_type'] : -1;

		if(empty($q) && !$cid){
			return json_encode(['status'=>'ok','results'=>[],'q'=>'']);
		}

		$like = '%'.$q.'%';
		$params = [$like, $like, $like];
		$types  = 'sss';
		$where  = " AND (il.title LIKE ? OR il.description LIKE ? OR il.fullname LIKE ?) ";

		if($cid){ $where .= " AND il.category_id=? "; $params[] = $cid; $types .= 'i'; }
		if($type >= 0){ $where .= " AND il.item_type=? "; $params[] = $type; $types .= 'i'; }

		$sql = "SELECT il.*, cl.name as category_name
				FROM item_list il
				LEFT JOIN category_list cl ON cl.id = il.category_id
				WHERE il.status=1 $where
				ORDER BY il.created_at DESC LIMIT 50";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = [];
		while($row = $result->fetch_assoc()) $rows[] = $row;
		$stmt->close();
		return json_encode(['status'=>'ok','results'=>$rows,'q'=>htmlspecialchars($q)]);
	}

	// ── Media helper (images + videos) ───────────────────────────────────
	private function upload_media_file($file, $item_id, $sort = 0){
		$image_types = ['image/jpeg','image/png','image/webp','image/gif'];
		$video_types = ['video/mp4','video/webm','video/quicktime','video/x-msvideo','video/mpeg'];
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$real_type = $finfo->file($file['tmp_name']);

		$is_video = in_array($real_type, $video_types);
		$is_image = in_array($real_type, $image_types);
		if(!$is_image && !$is_video) return ['success'=>false,'error'=>'Unsupported file type'];

		$max_size = $is_video ? 30 * 1024 * 1024 : 5 * 1024 * 1024;
		if($file['size'] > $max_size) return ['success'=>false,'error'=>$is_video?'Video exceeds 30 MB limit':'Image exceeds 5 MB limit'];

		$dir = base_app.'uploads/items/';
		if(!is_dir($dir)) mkdir($dir, 0755, true);

		if($is_video){
			$ext_map = ['video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov','video/x-msvideo'=>'avi','video/mpeg'=>'mpg'];
			$ext  = $ext_map[$real_type] ?? 'mp4';
			$dest = 'uploads/items/'.$item_id.'_'.$sort.'_'.time().'.'.$ext;
			if(!move_uploaded_file($file['tmp_name'], base_app.$dest)) return ['success'=>false,'error'=>'Could not save video'];
			return ['success'=>true,'path'=>$dest,'media_type'=>'video'];
		}

		// Image — resize via GD
		$img = match($real_type){
			'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
			'image/png'  => imagecreatefrompng($file['tmp_name']),
			'image/webp' => imagecreatefromwebp($file['tmp_name']),
			'image/gif'  => imagecreatefromgif($file['tmp_name']),
			default      => null,
		};
		if(!$img) return ['success'=>false,'error'=>'Could not process image'];
		[$w,$h] = getimagesize($file['tmp_name']);
		if($w > 1200){ $h = (int)($h*(1200/$w)); $w = 1200; }
		$thumb = imagescale($img, $w, $h);
		$dest  = 'uploads/items/'.$item_id.'_'.$sort.'_'.time().'.png';
		imagepng($thumb, base_app.$dest);
		imagedestroy($img);
		imagedestroy($thumb);
		return ['success'=>true,'path'=>$dest,'media_type'=>'image'];
	}

	// ── QR scan sightings for admin map ──────────────────────────────────
	function get_qr_scans(){
		require_admin();
		$item_id = (int)($_GET['item_id'] ?? 0);
		if($item_id){
			$stmt = $this->conn->prepare(
				"SELECT qs.*, il.title as item_title
				 FROM qr_scans qs JOIN item_list il ON il.id=qs.item_id
				 WHERE qs.item_id=? ORDER BY qs.scanned_at DESC LIMIT 200"
			);
			$stmt->bind_param('i', $item_id);
			$stmt->execute();
			$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
			$stmt->close();
		} else {
			// Summary: all items with scan counts
			$rows = $this->conn->query(
				"SELECT il.id, il.title, il.item_type, COUNT(qs.id) as scan_count,
				 MAX(qs.scanned_at) as last_scan
				 FROM item_list il LEFT JOIN qr_scans qs ON qs.item_id=il.id
				 GROUP BY il.id ORDER BY scan_count DESC LIMIT 100"
			)->fetch_all(MYSQLI_ASSOC);
		}
		return json_encode(['status'=>'success','data'=>$rows]);
	}

	// ── Shop: place order ────────────────────────────────────────────────
	// ── Paystack: verify payment and create confirmed order ─────────────
	function verify_paystack_payment(){
		$ref     = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($_POST['reference'] ?? ''));
		$uid     = isset($_SESSION['pub_userdata']) ? (int)$_SESSION['pub_userdata']['id'] : null;
		$sku     = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_POST['sku'] ?? '')));
		$product = substr(trim($_POST['product'] ?? ''), 0, 100);
		$price   = max(0, (float)($_POST['unit_price'] ?? 0));
		$qty     = max(1, min(20, (int)($_POST['qty'] ?? 1)));
		$name    = trim($_POST['customer_name'] ?? '');
		$email   = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$phone   = trim($_POST['customer_phone'] ?? '');
		$address = trim($_POST['delivery_address'] ?? '');
		$notes   = trim($_POST['notes'] ?? '');

		if(!$ref || !$sku || !$product || $price<=0 || !$name || !$email || !$phone || !$address){
			return json_encode(['status'=>'failed','msg'=>'Invalid order data.']);
		}

		// Verify with Paystack API
		$secret = PAYSTACK_SECRET;
		if(!$secret || strpos($secret,'xxxxxx') !== false){
			// Dev mode: accept without verification
			$amount_paid = ($price * $qty + 5) * 100;
		} else {
			$ch = curl_init("https://api.paystack.co/transaction/verify/".urlencode($ref));
			curl_setopt_array($ch, [
				CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$secret}","Cache-Control: no-cache"],
				CURLOPT_RETURNTRANSFER=> true,
				CURLOPT_SSL_VERIFYPEER=> true,
				CURLOPT_TIMEOUT       => 15,
			]);
			$res = json_decode(curl_exec($ch), true);
			curl_close($ch);
			if(!$res || !$res['status'] || $res['data']['status'] !== 'success'){
				return json_encode(['status'=>'failed','msg'=>'Payment could not be verified. Please contact support.']);
			}
			$amount_paid = $res['data']['amount']; // in pesewas
		}

		$delivery  = 5.00;
		$subtotal  = round($price * $qty, 2);
		$total     = $subtotal + $delivery;
		$expected  = (int)round($total * 100);
		if($secret && strpos($secret,'xxxxxx') === false && $amount_paid < $expected){
			return json_encode(['status'=>'failed','msg'=>'Payment amount mismatch. Please contact support with ref: '.$ref]);
		}

		$items_json = json_encode([['sku'=>$sku,'name'=>$product,'qty'=>$qty,'price'=>$price]]);
		$order_ref  = strtoupper(bin2hex(random_bytes(4)));

		$stmt = $this->conn->prepare(
			"INSERT INTO orders (user_id, order_ref, customer_name, customer_email, customer_phone,
			 delivery_address, items, subtotal, delivery_fee, total, payment_method, payment_status, order_status, notes)
			 VALUES (?,?,?,?,?,?,?,?,?,?,'card','paid','processing',?)"
		);
		$stmt->bind_param('issssssdds', $uid,$order_ref,$name,$email,$phone,$address,$items_json,$subtotal,$delivery,$total,$notes);
		$stmt->execute();
		$order_id = $this->conn->insert_id;
		$stmt->close();

		if(!$order_id) return json_encode(['status'=>'failed','msg'=>'Could not save order. Please contact support.']);

		if($uid){ award_points($uid, max(10,(int)round($total)), 'purchase', "Points on order #{$order_ref}"); }

		$this->_send_order_email($name, $email, $order_ref, $product, $qty, $total, 'card', $address, true);
		$this->_notify_admin_order($order_ref, $product, $qty, $total);

		return json_encode(['status'=>'success','ref'=>$order_ref,'msg'=>"Payment confirmed! Order #{$order_ref} is being processed."]);
	}

	private function _send_order_email($name,$email,$ref,$product,$qty,$total,$method,$address,$paid=false){
		$status_line = $paid
		  ? "<div style='background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:10px 14px;margin:12px 0;color:#065f46;font-weight:600;font-size:.9em'><span style='font-size:1.1em'>✅</span> Payment confirmed — your order is being processed.</div>"
		  : "<div style='background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin:12px 0;color:#92400e'><strong>⏳ Payment pending.</strong> Please complete payment and use <strong>#{$ref}</strong> as your reference.</div>";

		$pay_lines = '';
		if(!$paid){
			$mtn = $_SESSION['system_info']['pay_mtn_number'] ?? '';
			$voda= $_SESSION['system_info']['pay_vodafone_number'] ?? '';
			$bank= $_SESSION['system_info']['pay_bank_account_number'] ?? '';
			if($mtn)  $pay_lines .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:9px 13px;margin-bottom:6px'><strong>📱 MTN MoMo:</strong> {$mtn}</div>";
			if($voda) $pay_lines .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:9px 13px;margin-bottom:6px'><strong>📱 Vodafone Cash:</strong> {$voda}</div>";
			if($bank) $pay_lines .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:9px 13px;margin-bottom:6px'><strong>🏦 Bank:</strong> {$bank}</div>";
		}

		$body = "<p>Hi {$name},</p>"
		      . "<p>Your Smart Asset Finder order <strong>#{$ref}</strong> has been received.</p>"
		      . $status_line
		      . "<table style='border-collapse:collapse;width:100%;margin:10px 0'>"
		      . "<tr><td style='padding:5px 0;color:#64748b;width:38%'>Product</td><td style='font-weight:600'>{$product}</td></tr>"
		      . "<tr><td style='padding:5px 0;color:#64748b'>Qty</td><td>{$qty}</td></tr>"
		      . "<tr><td style='padding:5px 0;color:#64748b'>Total</td><td style='font-weight:700;color:#4f46e5'>GHS ".number_format($total,2)."</td></tr>"
		      . "<tr><td style='padding:5px 0;color:#64748b'>Deliver to</td><td>".htmlspecialchars($address)."</td></tr>"
		      . "</table>"
		      . $pay_lines
		      . "<p style='color:#64748b;font-size:.88em'>Your personalised SAF tags will be printed and dispatched within 1–2 business days of payment confirmation.</p>"
		      . "<p>Thank you,<br><strong>Smart Asset Finder Team</strong></p>";

		Mailer::send($email, $name, ($paid ? "Order Confirmed" : "Order Received")." — #{$ref}", $body);
	}

	private function _notify_admin_order($ref,$product,$qty,$total){
		$msg  = "New order #{$ref} — {$product} ×{$qty} — GHS ".number_format($total,2);
		$link = 'admin?page=orders';
		$stmt = $this->conn->prepare("INSERT INTO notifications (user_id,message,link,type) SELECT id,?,?,'info' FROM users WHERE login_type=1 LIMIT 3");
		$stmt->bind_param('ss', $msg, $link);
		$stmt->execute();
		$stmt->close();
	}

	function place_order(){
		$uid     = isset($_SESSION['pub_userdata']) ? (int)$_SESSION['pub_userdata']['id'] : null;
		$sku     = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_POST['sku']     ?? '')));
		$product = substr(trim($_POST['product']  ?? ''), 0, 100);
		$price   = max(0, (float)($_POST['unit_price'] ?? 0));
		$qty     = max(1, min(20, (int)($_POST['qty'] ?? 1)));
		$name    = trim($_POST['customer_name']    ?? '');
		$email   = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
		$phone   = trim($_POST['customer_phone']   ?? '');
		$address = trim($_POST['delivery_address'] ?? '');
		$method  = in_array($_POST['payment_method'] ?? '', ['mobile_money','card','cash_on_delivery'])
		           ? $_POST['payment_method'] : 'mobile_money';
		$notes   = trim($_POST['notes'] ?? '');

		if(!$sku || !$product || $price <= 0 || !$name || !$email || !$phone || !$address){
			return json_encode(['status'=>'failed','msg'=>'Please fill in all required fields.']);
		}

		$delivery  = 5.00;
		$subtotal  = round($price * $qty, 2);
		$total     = $subtotal + $delivery;
		$items_json = json_encode([['sku'=>$sku,'name'=>$product,'qty'=>$qty,'price'=>$price]]);
		$ref        = strtoupper(bin2hex(random_bytes(4))); // 8-char hex ref

		$stmt = $this->conn->prepare(
			"INSERT INTO orders (user_id, order_ref, customer_name, customer_email, customer_phone,
			 delivery_address, items, subtotal, delivery_fee, total, payment_method, notes)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
		);
		$stmt->bind_param('issssssdddss',
			$uid, $ref, $name, $email, $phone,
			$address, $items_json, $subtotal, $delivery, $total, $method, $notes
		);
		$stmt->execute();
		$order_id = $this->conn->insert_id;
		$stmt->close();

		if(!$order_id) return json_encode(['status'=>'failed','msg'=>'Could not place order. Please try again.']);

		// Notify admin
		$admin_notif = $this->conn->prepare(
			"INSERT INTO notifications (user_id, message, link, type)
			 SELECT id, ?, ?, 'info' FROM users WHERE login_type=1 LIMIT 3"
		);
		$notif_msg  = "New shop order #{$ref} — {$product} ×{$qty} — GHS ".number_format($total,2);
		$notif_link = 'admin?page=orders';
		$admin_notif->bind_param('ss', $notif_msg, $notif_link);
		$admin_notif->execute();
		$admin_notif->close();

		// Award purchase points to buyer
		if($uid){
			$pts = max(10, (int)round($total));
			award_points($uid, $pts, 'purchase', "Points earned on order #{$ref}");
		}

		// Build payment block for email
		$pay_block = '';
		$mtn_no  = $_SESSION['system_info']['pay_mtn_number']          ?? $this->conn->query("SELECT meta_value FROM system_info WHERE meta_field='pay_mtn_number' LIMIT 1")->fetch_row()[0] ?? '';
		$voda_no = $_SESSION['system_info']['pay_vodafone_number']      ?? '';
		$at_no   = $_SESSION['system_info']['pay_airteltigo_number']    ?? '';
		$bank_no = $_SESSION['system_info']['pay_bank_account_number']  ?? '';
		$bank_nm = $_SESSION['system_info']['pay_bank_name']            ?? '';
		$bank_an = $_SESSION['system_info']['pay_bank_account_name']    ?? '';
		$instruc = $_SESSION['system_info']['pay_instructions']         ?? 'Send payment to any of the details below and share your receipt with us to confirm your order.';
		if($mtn_no || $voda_no || $at_no || $bank_no){
			$pay_block .= "<div style='background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;margin:16px 0'>"
			            . "<strong style='color:#92400e'>Complete Your Payment</strong><br>"
			            . "<span style='font-size:.9em;color:#78350f'>{$instruc}</span>"
			            . "<br>Use your order number <strong>#{$ref}</strong> as reference.</div>";
			if($mtn_no){
				$mtn_nm = $_SESSION['system_info']['pay_mtn_name'] ?? '';
				$pay_block .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:8px'>"
				            . "<strong>📱 MTN Mobile Money</strong><br>"
				            . "<span style='font-size:1.1em;font-weight:700;letter-spacing:.05em'>{$mtn_no}</span>"
				            . ($mtn_nm ? "<br><span style='color:#64748b;font-size:.85em'>{$mtn_nm}</span>" : '')
				            . "</div>";
			}
			if($voda_no){
				$voda_nm = $_SESSION['system_info']['pay_vodafone_name'] ?? '';
				$pay_block .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:8px'>"
				            . "<strong>📱 Vodafone Cash</strong><br>"
				            . "<span style='font-size:1.1em;font-weight:700;letter-spacing:.05em'>{$voda_no}</span>"
				            . ($voda_nm ? "<br><span style='color:#64748b;font-size:.85em'>{$voda_nm}</span>" : '')
				            . "</div>";
			}
			if($at_no){
				$at_nm = $_SESSION['system_info']['pay_airteltigo_name'] ?? '';
				$pay_block .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:8px'>"
				            . "<strong>📱 AirtelTigo Money</strong><br>"
				            . "<span style='font-size:1.1em;font-weight:700;letter-spacing:.05em'>{$at_no}</span>"
				            . ($at_nm ? "<br><span style='color:#64748b;font-size:.85em'>{$at_nm}</span>" : '')
				            . "</div>";
			}
			if($bank_no){
				$pay_block .= "<div style='border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:8px'>"
				            . "<strong>🏦 Bank Transfer" . ($bank_nm ? " — {$bank_nm}" : '') . "</strong><br>"
				            . ($bank_an ? "<span style='color:#64748b;font-size:.85em'>Account name: {$bank_an}</span><br>" : '')
				            . "<span style='font-size:1.1em;font-weight:700;letter-spacing:.05em'>{$bank_no}</span>"
				            . "</div>";
			}
		}

		// Confirmation email
		$body = "<p>Hi {$name},</p>"
		      . "<p>Your Smart Asset Finder order <strong>#{$ref}</strong> has been received!</p>"
		      . "<table style='border-collapse:collapse;width:100%;margin-bottom:8px'>"
		      . "<tr><td style='padding:6px 0;color:#64748b;width:40%'>Product</td><td style='font-weight:600'>{$product}</td></tr>"
		      . "<tr><td style='padding:6px 0;color:#64748b'>Quantity</td><td>{$qty}</td></tr>"
		      . "<tr><td style='padding:6px 0;color:#64748b'>Total</td><td style='font-weight:700;color:#4f46e5'>GHS ".number_format($total,2)."</td></tr>"
		      . "<tr><td style='padding:6px 0;color:#64748b'>Payment</td><td>".ucwords(str_replace('_',' ',$method))."</td></tr>"
		      . "<tr><td style='padding:6px 0;color:#64748b'>Deliver to</td><td>".htmlspecialchars($address)."</td></tr>"
		      . "</table>"
		      . $pay_block
		      . "<p style='margin-top:16px;color:#64748b;font-size:.88em'>We'll confirm your order and begin printing within 24 hours of receiving payment.</p>"
		      . "<p>Thank you for choosing Smart Asset Finder.</p>";
		Mailer::send($email, $name, "Order Confirmed — #{$ref}", $body);

		return json_encode(['status'=>'success','ref'=>$ref,
			'msg'=>"Order #{$ref} placed successfully!"]);
	}

	// ── Shop: admin order status updates ────────────────────────────────
	function update_order_status(){
		require_admin();
		$id     = (int)($_POST['id'] ?? 0);
		$status = in_array($_POST['status'] ?? '', ['pending','processing','shipped','delivered','cancelled'])
		          ? $_POST['status'] : null;
		if(!$id || !$status) return json_encode(['status'=>'failed','msg'=>'Invalid request.']);
		$this->conn->prepare("UPDATE orders SET order_status=? WHERE id=?")->execute_and_close = false;
		$upd = $this->conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
		$upd->bind_param('si', $status, $id);
		$upd->execute();
		$upd->close();
		return json_encode(['status'=>'success']);
	}
	function update_order_payment(){
		require_admin();
		$id     = (int)($_POST['id'] ?? 0);
		$status = in_array($_POST['status'] ?? '', ['pending','paid','failed']) ? $_POST['status'] : null;
		if(!$id || !$status) return json_encode(['status'=>'failed','msg'=>'Invalid request.']);
		$upd = $this->conn->prepare("UPDATE orders SET payment_status=? WHERE id=?");
		$upd->bind_param('si', $status, $id);
		$upd->execute();
		$upd->close();
		return json_encode(['status'=>'success']);
	}

	// ── Admin: save payment settings to system_info ─────────────────────
	function save_payment_settings(){
		require_admin();
		$allowed = ['pay_mtn_number','pay_mtn_name','pay_vodafone_number','pay_vodafone_name',
		            'pay_airteltigo_number','pay_airteltigo_name','pay_bank_name',
		            'pay_bank_account_name','pay_bank_account_number','pay_bank_branch','pay_instructions'];
		$stmt = $this->conn->prepare(
			"INSERT INTO system_info (meta_field, meta_value) VALUES (?,?)
			 ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)"
		);
		foreach($allowed as $field){
			$val = trim($_POST[$field] ?? '');
			$stmt->bind_param('ss', $field, $val);
			$stmt->execute();
			$_SESSION['system_info'][$field] = $val;
		}
		$stmt->close();
		return json_encode(['status'=>'success']);
	}

	// ── QR Tag: finder notification when tag is scanned ─────────────────
	function tag_found_report(){
		$code    = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($_POST['tag_code'] ?? '')));
		$name    = trim($_POST['finder_name']    ?? '');
		$contact = trim($_POST['finder_contact'] ?? '');
		$loc     = trim($_POST['found_location'] ?? '');
		$msg     = trim($_POST['message']        ?? '');

		if(!$code || !$name || !$contact){
			return json_encode(['status'=>'failed','msg'=>'Please fill in your name and contact details.']);
		}

		// Verify tag exists and is active
		$stmt = $this->conn->prepare(
			"SELECT qt.user_id, qt.label, ru.firstname, ru.lastname, ru.email, ru.phone
			 FROM qr_tags qt JOIN registered_users ru ON ru.id=qt.user_id
			 WHERE qt.tag_code=? AND qt.status=1 AND ru.status=1 LIMIT 1"
		);
		$stmt->bind_param('s', $code);
		$stmt->execute();
		$tag = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if(!$tag) return json_encode(['status'=>'failed','msg'=>'Invalid or inactive QR tag.']);

		// Log as a notification to the owner
		$item_label = !empty($tag['label']) ? '"'.$tag['label'].'"' : 'your tagged item';
		$notif_msg  = "Someone found {$item_label}! Finder: {$name} ({$contact})".($loc ? " — Location: {$loc}" : '');
		$notif_msg  = substr($notif_msg, 0, 499);
		$link       = '?page=my-items';
		$notif = $this->conn->prepare(
			"INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'success')"
		);
		$notif->bind_param('iss', $tag['user_id'], $notif_msg, $link);
		$notif->execute();
		$notif->close();

		// Email + WhatsApp owner
		$item_name  = !empty($tag['label']) ? $tag['label'] : '';
		$owner_name = $tag['firstname'].' '.$tag['lastname'];
		$dash_url   = saf_env('APP_URL','http://localhost/Smart-Asset-Finder/').'?page=my-items';
		Mailer::qrScanned($tag['email'], $owner_name, $item_name, $name, $contact, $loc, $msg, $dash_url);
		if(!empty($tag['phone'])){
			Whatsapp::qrScanned($tag['phone'], $tag['firstname'], $item_name, $name, $contact, $loc, $msg, $dash_url);
		}

		return json_encode(['status'=>'success','msg'=>'The owner has been notified and will contact you soon. Thank you for your honesty!']);
	}

	// ── QR Tag: label/rename a tag from dashboard ─────────────────────────
	function update_qr_tag(){
		if(!isset($_SESSION['pub_userdata'])) return json_encode(['status'=>'failed','msg'=>'Not logged in.']);
		$uid   = (int)$_SESSION['pub_userdata']['id'];
		$id    = (int)($_POST['id'] ?? 0);
		$label = substr(trim($_POST['label'] ?? ''), 0, 100);
		$notes = substr(trim($_POST['notes'] ?? ''), 0, 255);
		if(!$id) return json_encode(['status'=>'failed','msg'=>'Invalid tag.']);
		$upd = $this->conn->prepare("UPDATE qr_tags SET label=?, notes=? WHERE id=? AND user_id=?");
		$upd->bind_param('ssii', $label, $notes, $id, $uid);
		$upd->execute();
		$upd->close();
		return json_encode(['status'=>'success','msg'=>'Tag updated.']);
	}

	// ── Delete image ─────────────────────────────────────────────────────
	function delete_img(){
		$path = trim($_POST['path'] ?? '');
		// Only allow deleting within uploads/
		$real = realpath(base_app.$path);
		$allowed_base = realpath(base_app.'uploads/');
		if(!$real || strpos($real, $allowed_base) !== 0){
			return json_encode(['status'=>'failed','error'=>'Invalid path']);
		}
		if(is_file($real) && unlink($real)){
			return json_encode(['status'=>'success']);
		}
		return json_encode(['status'=>'failed','error'=>'Could not delete file']);
	}
}

$Master = new Master();
$action = isset($_GET['f']) ? strtolower($_GET['f']) : 'none';

// GET-only actions that don't change state — skip CSRF
$csrf_exempt = ['get_notifications', 'search_items', 'get_qr_scans'];
// tag_found_report is public (no session needed) but DOES change state — CSRF still checked
if(!in_array($action, $csrf_exempt)) csrf_check();

// Actions that require an active admin session
$admin_actions = ['delete_img','save_category','delete_category','save_page',
                  'delete_item','update_item_status','delete_inquiry',
                  'mark_inquiry_read','update_claim_status','toggle_user_status'];
if(in_array($action, $admin_actions)) require_admin();

switch($action){
	case 'delete_img':         echo $Master->delete_img(); break;
	case 'save_category':      echo $Master->save_category(); break;
	case 'delete_category':    echo $Master->delete_category(); break;
	case 'save_page':          echo $Master->save_page(); break;
	case 'save_item':          echo $Master->save_item(); break;
	case 'delete_item':        echo $Master->delete_item(); break;
	case 'update_item_status': echo $Master->update_item_status(); break;
	case 'save_inquiry':       echo $Master->save_inquiry(); break;
	case 'delete_inquiry':     echo $Master->delete_inquiry(); break;
	case 'mark_inquiry_read':  echo $Master->mark_inquiry_read(); break;
	case 'save_claim':         echo $Master->save_claim(); break;
	case 'update_claim_status':echo $Master->update_claim_status(); break;
	case 'get_notifications':  echo $Master->get_notifications(); break;
case 'redeem_points':      echo $Master->redeem_points(); break;
	case 'toggle_user_status': echo $Master->toggle_user_status(); break;
	case 'search_items':       echo $Master->search_items(); break;
	case 'get_qr_scans':       echo $Master->get_qr_scans(); break;
	case 'place_order':              echo $Master->place_order(); break;
	case 'verify_paystack_payment':  echo $Master->verify_paystack_payment(); break;
	case 'update_order_status':    echo $Master->update_order_status(); break;
	case 'update_order_payment':    echo $Master->update_order_payment(); break;
	case 'save_payment_settings':   echo $Master->save_payment_settings(); break;
	case 'tag_found_report':        echo $Master->tag_found_report(); break;
	case 'update_qr_tag':      echo $Master->update_qr_tag(); break;
}
