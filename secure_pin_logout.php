<?php
// secure_pin_logout.php
session_start();
unset($_SESSION['secure_access']);
unset($_SESSION['secure_access_time']);
header('Location: files.php?tab=user');
exit();
?>