<?php
require_once __DIR__ . '/app-functions.php';
require_once __DIR__ . '/includes/offers.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
function orderFail($message) { $_SESSION['cart_notice'] = $message; header('Location: cart.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pay']) || empty($_SESSION['cart_csrf']) || !hash_equals($_SESSION['cart_csrf'], $_POST['csrf'] ?? '')) orderFail('Please retry checkout from your cart.');
if (empty($_SESSION['cart'])) orderFail('Your cart is empty.');
if (empty($_POST['policy_acceptance'])) orderFail('Please read and accept the Terms, Privacy Notice, Cancellation and Refund Policy, and Ethical Use Policy before placing an order.');
$email = $_SESSION['email'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cod';
$paymentMethods = array('cod' => 'Cash on delivery', 'upi' => 'UPI', 'card' => 'Card', 'netbanking' => 'Net banking');
if (!isset($paymentMethods[$paymentMethod])) orderFail('Choose a valid payment method.');
if ($paymentMethod === 'upi' && !preg_match('/^[A-Za-z0-9._-]{2,64}@[A-Za-z0-9.-]{2,64}$/', trim($_POST['upi_id'] ?? ''))) orderFail('Enter a valid UPI ID.');
if ($paymentMethod === 'card') {
    $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvv = trim($_POST['card_cvv'] ?? '');
    if (trim($_POST['card_holder'] ?? '') === '' || strlen($cardNumber) < 12 || strlen($cardNumber) > 19 || !preg_match('#^(0[1-9]|1[0-2])/\d{2}$#', $cardExpiry) || !preg_match('/^\d{3,4}$/', $cardCvv)) orderFail('Enter valid card, expiry date, and CVV details.');
}
if ($paymentMethod === 'netbanking' && (trim($_POST['bank_name'] ?? '') === '' || trim($_POST['bank_account_name'] ?? '') === '')) orderFail('Choose a bank and enter the account holder name.');
if ($name === '' || strlen($name) > 50 || !preg_match('/^[0-9]{10}$/', $phone) || strlen($address) > 255) orderFail('Check your name, phone and delivery address.');
$conn = dbConnect();
if (!$conn) orderFail('Database is unavailable. Try again shortly.');
if (!ensureOrderPaymentsTable()) orderFail('Payment options are temporarily unavailable.');
if (!ensureMisTables()) orderFail('Order tracking is temporarily unavailable.');
$customerStmt = mysqli_prepare($conn, 'SELECT email,is_active FROM customers WHERE email=?');
mysqli_stmt_bind_param($customerStmt, 's', $email);
mysqli_stmt_execute($customerStmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($customerStmt));
if (!$customer) orderFail('Your account could not be found.');
if (array_key_exists('is_active', $customer) && !(int) $customer['is_active']) orderFail('This account is currently paused. Contact Foodera support for help.');
if ($address === '' && !empty($_POST['address_id'])) {
    $addressId = (int) $_POST['address_id'];
    $addressStmt = mysqli_prepare($conn, 'SELECT address_line FROM customer_addresses WHERE address_id=? AND customer_email=?');
    if (!$addressStmt) orderFail('Saved addresses are unavailable.');
    mysqli_stmt_bind_param($addressStmt, 'is', $addressId, $email);
    mysqli_stmt_execute($addressStmt);
    $saved = mysqli_fetch_assoc(mysqli_stmt_get_result($addressStmt));
    if (!$saved) orderFail('Choose a valid saved address.');
    $address = $saved['address_line'];
}
if ($address === '') orderFail('Choose a saved address or enter a one-time address.');
$foodStmt = mysqli_prepare($conn, 'SELECT id,name,price,category FROM foods WHERE id=? AND is_active=1');
if (!$foodStmt) orderFail('Menu items are unavailable.');
$lines = array(); $subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $id = (int) ($item['Product_id'] ?? 0);
    $qty = (int) ($item['quantity'] ?? 0);
    if ($id <= 0 || $qty < 1 || $qty > 10) orderFail('A cart quantity is invalid.');
    mysqli_stmt_bind_param($foodStmt, 'i', $id);
    mysqli_stmt_execute($foodStmt);
    $food = mysqli_fetch_assoc(mysqli_stmt_get_result($foodStmt));
    if (!$food) orderFail('A menu item is no longer available. Remove it from your cart.');
    $lines[] = array((int) $food['id'], $food['name'], (float) $food['price'], $qty, $food['category']);
    $subtotal += (float) $food['price'] * $qty;
}
$offerItems = array();
foreach ($lines as $line) $offerItems[] = array('id' => $line[0], 'category' => $line[4], 'quantity' => $line[3], 'price' => $line[2]);
$offerResult = offerResolveCart($email, $offerItems, $subtotal, $_SESSION['offer_code'] ?? '');
$appliedOffer = $offerResult['offer']; $discount = (float) ($offerResult['discount'] ?? 0);
if (!mysqli_begin_transaction($conn)) orderFail('Could not start checkout.');
$orderStmt = mysqli_prepare($conn, 'INSERT INTO orders(name,email,phone,address) VALUES (?,?,?,?)');
$ok = $orderStmt && mysqli_stmt_bind_param($orderStmt, 'ssss', $name, $email, $phone, $address) && mysqli_stmt_execute($orderStmt);
if ($ok) {
    $orderId = mysqli_insert_id($conn);
    $itemStmt = mysqli_prepare($conn, 'INSERT INTO order_items(orderid,pname,pprice,quantity) VALUES (?,?,?,?)');
    $ok = (bool) $itemStmt;
    if ($ok) {
        foreach ($lines as $line) {
            [, $foodName, $foodPrice, $quantity] = $line;
            if (!mysqli_stmt_bind_param($itemStmt, 'isii', $orderId, $foodName, $foodPrice, $quantity) || !mysqli_stmt_execute($itemStmt)) { $ok = false; break; }
        }
    }
}
$payableTotal = max(0, $subtotal - $discount);
if ($ok) {
    $financialStmt = mysqli_prepare($conn, 'INSERT INTO order_financials(orderid,subtotal,discount_amount,total_amount,offer_id,offer_code) VALUES(?,?,?,?,?,?)');
    $offerId = $appliedOffer ? (int) $appliedOffer['offer_id'] : null;
    $offerCode = $appliedOffer['code'] ?? null;
    $ok = $financialStmt && mysqli_stmt_bind_param($financialStmt, 'idddis', $orderId, $subtotal, $discount, $payableTotal, $offerId, $offerCode) && mysqli_stmt_execute($financialStmt);
}
if ($ok) {
    $reference = null;
    $paymentStatus = $paymentMethod === 'cod' ? 'pending' : 'payment_requested';
    if ($paymentMethod === 'upi') $reference = 'UPI: ' . trim($_POST['upi_id']);
    if ($paymentMethod === 'card') $reference = 'Card ending ' . substr(preg_replace('/\D+/', '', $_POST['card_number'] ?? ''), -4);
    if ($paymentMethod === 'netbanking') $reference = 'Bank: ' . trim($_POST['bank_name']);
    $paymentStmt = mysqli_prepare($conn, 'INSERT INTO order_payments(orderid,payment_method,payment_status,reference_label) VALUES(?,?,?,?)');
    $ok = $paymentStmt && mysqli_stmt_bind_param($paymentStmt, 'isss', $orderId, $paymentMethod, $paymentStatus, $reference) && mysqli_stmt_execute($paymentStmt);
}
if ($ok) {
    $policyVersion = '2026-10-26';
    $policyStmt = mysqli_prepare($conn, 'INSERT INTO order_policy_acceptances(orderid,customer_email,policy_version) VALUES(?,?,?)');
    $ok = $policyStmt && mysqli_stmt_bind_param($policyStmt, 'iss', $orderId, $email, $policyVersion) && mysqli_stmt_execute($policyStmt);
}
if ($ok) {
    $initialStatus = $paymentMethod === 'cod' ? 'placed' : 'payment_pending';
    $trackingStmt = mysqli_prepare($conn, 'INSERT INTO order_tracking(orderid,status) VALUES (?, ?)');
    $ok = $trackingStmt && mysqli_stmt_bind_param($trackingStmt, 'is', $orderId, $initialStatus) && mysqli_stmt_execute($trackingStmt);
}
if ($ok) $ok = orderRecordStatus($conn, $orderId, $initialStatus, $paymentMethod === 'cod' ? 'Order placed' : 'Awaiting verified online payment', $email);
if ($ok && $appliedOffer && $discount > 0) $ok = offerRecordRedemption($conn, $appliedOffer, $email, $orderId, $subtotal, $discount);
if (!$ok || !mysqli_commit($conn)) {
    mysqli_rollback($conn);
    orderFail('Checkout could not be completed. Please try again.');
}
$_SESSION['last_order_id'] = $orderId;
$orderTitle = $paymentMethod === 'cod' ? 'Order placed' : 'Payment verification required';
$orderBody = $paymentMethod === 'cod' ? 'Your order #' . $orderId . ' was placed successfully and is awaiting restaurant confirmation.' : 'Order #' . $orderId . ' will be sent to the restaurant after payment is verified.';
createNotification('customer', $email, $orderTitle, $orderBody, 'track-order.php?order=' . $orderId);
createNotification('admin', '', 'New order #' . $orderId, $name . ' created an order for Rs ' . number_format($payableTotal, 0) . ($paymentMethod === 'cod' ? '.' : ' and it needs payment verification.'), 'order-details.php');
unset($_SESSION['cart']);
unset($_SESSION['offer_code']);
header('Location: payment.php');
exit;
