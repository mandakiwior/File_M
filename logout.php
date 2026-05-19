<?php
// logout.php
require_once 'includes/session.php';
require_once 'includes/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit();
?>