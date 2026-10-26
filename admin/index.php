<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Location: ' . (!empty($_SESSION['foodera_admin']) && !empty($_SESSION['admin_email']) ? 'home.php' : 'login.php'));
exit;
