<?php
require_once __DIR__ . '/app-functions.php';
session_start();
if (empty($_SESSION['email']) || empty($_SESSION['last_order_id'])) { header('Location: menu.php'); exit; }
if (!ensureOrderPaymentsTable()) { header('Location: home.php'); exit; }
$conn = dbConnect();
$orderId = (int) $_SESSION['last_order_id'];
$email = $_SESSION['email'];
$stmt = mysqli_prepare($conn, 'SELECT o.orderid,o.name,o.address,financials.subtotal,financials.discount_amount,financials.total_amount,payment.payment_method,payment.payment_status,payment.reference_label FROM orders o JOIN order_financials financials ON financials.orderid=o.orderid JOIN order_payments payment ON payment.orderid=o.orderid WHERE o.orderid=? AND o.email=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'is', $orderId, $email);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) { header('Location: menu.php'); exit; }
$methodLabels = array('cod' => 'Cash on delivery', 'upi' => 'UPI', 'card' => 'Card', 'netbanking' => 'Net banking');
$methodIcons = array('cod' => 'bi-cash-stack', 'upi' => 'bi-qr-code', 'card' => 'bi-credit-card-2-front', 'netbanking' => 'bi-bank');
$isOnlinePending = $order['payment_method'] !== 'cod' && $order['payment_status'] !== 'paid';
$pageTitle = $isOnlinePending ? 'Payment verification' : 'Order placed'; $activePage = 'payment'; $bodyClass = 'payment-page'; $pageStyles = array('css/payment.css');
include __DIR__ . '/includes/public-header.php';
?>
<main class="payment-main"><section class="payment-confirmation"><div class="payment-success-icon"><i class="bi <?php echo $isOnlinePending ? 'bi-clock-history' : 'bi-check-lg'; ?>" aria-hidden="true"></i></div><p class="payment-kicker"><?php echo $isOnlinePending ? 'Payment verification' : 'Order confirmed'; ?></p><h1>Thanks, <?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8'); ?>.</h1><p class="payment-lead">Order <strong>#<?php echo (int) $order['orderid']; ?></strong> <?php echo $isOnlinePending ? 'is awaiting verified payment before it is sent to the restaurant.' : 'is in the restaurant queue. You can track every delivery update from your order page.'; ?></p><div class="payment-method-panel"><div class="payment-method-icon"><i class="bi <?php echo $methodIcons[$order['payment_method']]; ?>" aria-hidden="true"></i></div><div><span>Payment method</span><strong><?php echo htmlspecialchars($methodLabels[$order['payment_method']], ENT_QUOTES, 'UTF-8'); ?></strong><?php if (!empty($order['reference_label'])) { ?><small><?php echo htmlspecialchars($order['reference_label'], ENT_QUOTES, 'UTF-8'); ?></small><?php } ?></div><b class="payment-status <?php echo $order['payment_method'] === 'cod' ? 'is-cod' : ''; ?>"><?php echo $order['payment_method'] === 'cod' ? 'Pay on delivery' : ($isOnlinePending ? 'Verification pending' : 'Paid'); ?></b></div><section class="payment-summary"><h2>Bill details</h2><div><span>Food subtotal</span><strong>Rs <?php echo number_format((float) $order['subtotal']); ?></strong></div><div><span>Food discount</span><strong class="payment-discount">- Rs <?php echo number_format((float) $order['discount_amount']); ?></strong></div><div class="payment-total"><span>Total payable</span><strong>Rs <?php echo number_format((float) $order['total_amount']); ?></strong></div></section><section class="payment-delivery-panel"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><div><span>Delivery address</span><strong><?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></strong></div></section><div class="payment-actions"><a class="payment-action" href="track-order.php?order=<?php echo (int) $order['orderid']; ?>">Track order <i class="bi bi-truck" aria-hidden="true"></i></a><a class="payment-secondary-action" href="menu.php">Continue browsing <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></section></main>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
