<?php
session_start();
$_SESSION['messages'] = [];
header("Location: index.php");
exit();
?>