<?php
require_once __DIR__ . '/includes/offers.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart_csrf']) || !hash_equals($_SESSION['cart_csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); exit('Invalid offer request.'); }

if (isset($_POST['clear_offer'])) {
    unset($_SESSION['offer_code']);
    $_SESSION['cart_notice'] = 'Offer code removed. Eligible automatic offers will still be checked.';
    header('Location: cart.php'); exit;
}

$code = strtoupper(trim($_POST['offer_code'] ?? ''));
if ($code === '') { $_SESSION['cart_notice'] = 'Enter an offer code or browse the current offers.'; header('Location: cart.php'); exit; }
$conn = dbConnect(); $items = array(); $subtotal = 0;
if ($conn && !empty($_SESSION['cart'])) {
    $stmt = mysqli_prepare($conn, 'SELECT id,name,price,picture,category FROM foods WHERE id=? AND is_active=1');
    foreach ($_SESSION['cart'] as $cartItem) {
        $id = (int) ($cartItem['Product_id'] ?? 0); $quantity = max(1, min(10, (int) ($cartItem['quantity'] ?? 1)));
        if (!$stmt || $id < 1) continue;
        mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt);
        $food = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$food) continue;
        $food['quantity'] = $quantity; $subtotal += (float) $food['price'] * $quantity; $items[] = $food;
    }
}
$result = offerResolveCart($_SESSION['email'], $items, $subtotal, $code);
if ($result['offer']) { unset($_SESSION['cart_offer_attempt']); $_SESSION['offer_code'] = $code; $_SESSION['cart_notice'] = $code . ' applied: ' . offerFormatValue($result['offer']) . '.'; }
else { unset($_SESSION['offer_code']); $_SESSION['cart_offer_attempt'] = $code; $_SESSION['cart_notice'] = $result['message'] ?: 'That offer could not be applied.'; }
header('Location: cart.php');
exit;
