<?php
require_once __DIR__ . '/app-functions.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart_csrf']) || !hash_equals($_SESSION['cart_csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); exit('Invalid cart request.'); }
$id = (int) ($_POST['pid'] ?? 0);
$return = isset($_POST['add']) ? 'menu.php' : 'cart.php';
if ($id <= 0) { header('Location: ' . $return); exit; }
$_SESSION['cart'] = $_SESSION['cart'] ?? array();
$index = null;
foreach ($_SESSION['cart'] as $key => $item) if ((int) ($item['Product_id'] ?? 0) === $id) { $index = $key; break; }
if (isset($_POST['add'])) {
    $conn = dbConnect();
    $stmt = $conn ? mysqli_prepare($conn, 'SELECT id,name,price,picture FROM foods WHERE id=? AND is_active=1') : false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt);
        $food = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($food) {
            if ($index !== null) $_SESSION['cart'][$index]['quantity'] = min(10, (int) $_SESSION['cart'][$index]['quantity'] + 1);
            else $_SESSION['cart'][] = array('Product_id'=>$id,'Product_name'=>$food['name'],'Product_price'=>$food['price'],'Product_picture'=>$food['picture'],'quantity'=>1);
            $_SESSION['cart_notice'] = 'Cart updated.';
        } else $_SESSION['cart_notice'] = 'That menu item is not currently available.';
    }
} elseif ($index !== null && isset($_POST['deletecart'])) {
    unset($_SESSION['cart'][$index]); $_SESSION['cart'] = array_values($_SESSION['cart']);
    $_SESSION['cart_notice'] = 'Item removed.';
} elseif ($index !== null && isset($_POST['Mod_Quantity'])) {
    $_SESSION['cart'][$index]['quantity'] = max(1, min(10, (int) $_POST['Mod_Quantity']));
    $_SESSION['cart_notice'] = 'Quantity updated.';
}
header('Location: ' . $return);
exit;
