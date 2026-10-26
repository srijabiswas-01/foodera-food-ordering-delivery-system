<?php
require_once __DIR__ . '/../../app-functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function adminDb()
{
    return dbConnect();
}

function adminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminToken()
{
    if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['admin_csrf'];
}

function adminVerifyPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals(adminToken(), $_POST['csrf'] ?? '');
}

function adminRequireLogin()
{
    if (empty($_SESSION['foodera_admin']) || empty($_SESSION['admin_email'])) {
        header('Location: login.php');
        exit;
    }
}

function adminRedirect($page, $message = '')
{
    if ($message !== '') $_SESSION['admin_notice'] = $message;
    header('Location: ' . $page);
    exit;
}

function adminNotice()
{
    $notice = $_SESSION['admin_notice'] ?? '';
    unset($_SESSION['admin_notice']);
    return $notice;
}

function adminRows($sql)
{
    $conn = adminDb();
    if (!$conn) return array();
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function adminCategories()
{
    $conn = adminDb();
    if (!$conn) return array();
    if (!mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS food_categories (category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, is_active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4')) return array();
    $statusColumn = mysqli_query($conn, "SHOW COLUMNS FROM food_categories LIKE 'is_active'");
    if (!$statusColumn || mysqli_num_rows($statusColumn) === 0) mysqli_query($conn, 'ALTER TABLE food_categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER name');
    mysqli_query($conn, "INSERT IGNORE INTO food_categories (name) SELECT DISTINCT TRIM(category) FROM foods WHERE TRIM(category) <> ''");
    $result = mysqli_query($conn, 'SELECT category_id, name, is_active FROM food_categories ORDER BY name');
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function adminEnsureFoodStatuses()
{
    $conn = adminDb();
    if (!$conn) return false;
    $statusColumn = mysqli_query($conn, "SHOW COLUMNS FROM foods LIKE 'is_active'");
    if (!$statusColumn || mysqli_num_rows($statusColumn) === 0) {
        return (bool) mysqli_query($conn, 'ALTER TABLE foods ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER category');
    }
    return true;
}
