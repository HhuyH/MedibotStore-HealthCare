<?php
session_start();
$_SESSION['messages'] = [];
header("Location: Index_Chat.php");
exit();
?>