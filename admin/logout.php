<?php
require_once __DIR__ . '/includes/bootstrap.php';
unset($_SESSION['foodera_admin'], $_SESSION['admin_email'], $_SESSION['admin_name'], $_SESSION['admin_csrf']);
session_regenerate_id(true);
header('Location: login.php');
exit;
