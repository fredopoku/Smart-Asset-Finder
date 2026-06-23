<?php
// My Claims is part of the My Items dashboard — redirect there
if(!isset($_SESSION['pub_userdata'])){ redirect('?page=login'); exit; }
echo '<script>location.replace("'.base_url.'?page=my-items#claims")</script>';
