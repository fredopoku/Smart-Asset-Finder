<?php require_once('./config.php'); ?>
<?php
// Store referral token in session so it survives navigation to register page
if(!empty($_GET['ref']) && empty($_SESSION['saf_ref'])){
    $_SESSION['saf_ref'] = preg_replace('/[^A-Za-z0-9+\/=_\-]/', '', $_GET['ref']);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<body class="toggle-sidebar">
<?php
  $page = isset($_GET['page']) ? preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $_GET['page']) : 'home';
  $pageSplit = explode('/', $page);
  if(isset($pageSplit[1]))
    $pageSplit[1] = (strtolower($pageSplit[1]) === 'list') ? $pageSplit[0].' List' : $pageSplit[1];
?>

<?php require_once('inc/topBarNav.php') ?>

<main id="main" class="main">

  <?php /* Flash message — shown outside container so it overlays nicely */ ?>
  <?php if($_settings->chk_flashdata('success')): ?>
    <div style="position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:9999">
      <script>alert_toast("<?= addslashes($_settings->flashdata('success')) ?>",'success')</script>
    </div>
  <?php endif ?>

  <?php
    $page_file = $page.'.php';
    $page_dir  = $page.'/index.php';

    /* Pages that manage their own full-width layout */
    $fullwidth_pages = ['home', 'my-items', 'profile', 'my-orders', 'shop', 'search'];

    if(in_array($page, $fullwidth_pages)):
      if(is_dir($page)) include $page_dir;
      else              include $page_file;

    elseif(!file_exists($page_file) && !is_dir($page)):
      echo '<div class="container-xl px-4 py-4">';
      include '404.html';
      echo '</div>';

    else:
      echo '<div class="container-xl px-4">';
      if(is_dir($page))
        include $page_dir;
      else
        include $page_file;
      echo '</div>';
    endif;
  ?>

</main>

<!-- Modals (Bootstrap 5) -->
<div class="modal fade" id="uni_modal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary rounded-pill" id="submit" onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirm_modal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary rounded-pill" id="confirm">Continue</button>
        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="viewer_modal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-3 border-0 bg-transparent">
      <button type="button" class="btn-close btn-close-white ms-auto me-2 mt-2" data-bs-dismiss="modal"></button>
      <img src="" alt="" class="img-fluid rounded-3">
    </div>
  </div>
</div>

<!-- Alert modal (replaces native alert()) -->
<div class="modal fade" id="saf_alert_modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-body p-4 text-center">
        <div id="saf_alert_icon" class="mb-3" style="font-size:2rem"></div>
        <div id="saf_alert_msg" style="font-size:.9rem;color:#374151;line-height:1.6"></div>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center">
        <button class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Prompt modal (replaces native prompt()) -->
<div class="modal fade" id="saf_prompt_modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-bold" id="saf_prompt_title"></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3">
        <input type="text" id="saf_prompt_input" class="form-control rounded-pill" placeholder="">
        <div id="saf_prompt_hint" class="form-text mt-1"></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary rounded-pill px-4 fw-semibold" id="saf_prompt_ok">Save</button>
      </div>
    </div>
  </div>
</div>

<?php require_once('inc/footer.php') ?>
</body>
</html>
