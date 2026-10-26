<?php
require_once __DIR__ . '/app-functions.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
if (!ensureOrderPaymentsTable() || !ensureOrderManagementTables()) { http_response_code(503); exit('Order tracking is temporarily unavailable.'); }
$conn = dbConnect();
$email = $_SESSION['email'];
$notice = '';
/* Deprecated direct-cancellation endpoints are intentionally disabled. */
if (false && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['legacy_cancel_order'])) {
    $orderId = (int) ($_POST['orderid'] ?? 0); $reason = trim($_POST['reason'] ?? ''); $note = trim($_POST['customer_note'] ?? '');
    $allowedReasons = array('Ordered by mistake','Change delivery address','Delivery time too long','Want to change items','Other');
    $check = mysqli_prepare($conn, 'SELECT t.status,f.total_amount,p.payment_method FROM orders o JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN order_financials f ON f.orderid=o.orderid LEFT JOIN order_payments p ON p.orderid=o.orderid WHERE o.orderid=? AND o.email=?');
    mysqli_stmt_bind_param($check, 'is', $orderId, $email); mysqli_stmt_execute($check); $orderToCancel = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    if (!in_array($reason, $allowedReasons, true)) $notice = 'Choose a cancellation reason before submitting your request.';
    elseif (!$orderToCancel || !in_array($orderToCancel['status'], array('placed','confirmed'), true)) $notice = 'This order can no longer be cancelled online.';
    else { $stmt = mysqli_prepare($conn, "INSERT INTO order_cancellations(orderid,customer_email,reason,customer_note,status,decided_at) VALUES(?,?,?,?, 'approved', NOW()) ON DUPLICATE KEY UPDATE reason=VALUES(reason),customer_note=VALUES(customer_note)"); mysqli_stmt_bind_param($stmt, 'isss', $orderId, $email, $reason, $note); $ok = mysqli_stmt_execute($stmt); if ($ok) { $statusStmt = mysqli_prepare($conn, "UPDATE order_tracking SET status='cancelled' WHERE orderid=?"); mysqli_stmt_bind_param($statusStmt, 'i', $orderId); $ok = mysqli_stmt_execute($statusStmt) && orderRecordStatus($conn, $orderId, 'cancelled', 'Customer cancellation: ' . $reason, $email); $refundStatus = ($orderToCancel['payment_method'] ?? 'cod') === 'cod' ? 'not_required' : 'requested'; $amount = (float) ($orderToCancel['total_amount'] ?? 0); $refundStmt = mysqli_prepare($conn, 'INSERT INTO order_refunds(orderid,amount,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount),status=VALUES(status)'); mysqli_stmt_bind_param($refundStmt, 'ids', $orderId, $amount, $refundStatus); $ok = $ok && mysqli_stmt_execute($refundStmt); } $notice = $ok ? 'Your order was cancelled. Refund status is shown below.' : 'Cancellation could not be completed. Please try again.'; }
}
if (false && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['legacy_remove_dishes'])) {
    $orderId = (int) ($_POST['orderid'] ?? 0); $reason = trim($_POST['reason'] ?? ''); $note = trim($_POST['customer_note'] ?? ''); $selected = array_slice(array_unique(array_filter($_POST['dish_names'] ?? array(), 'is_string')), 0, 1);
    $allowedReasons = array('Ordered by mistake','Change delivery address','Delivery time too long','Want to change items','Other');
    $check = mysqli_prepare($conn, 'SELECT t.status,p.payment_method FROM orders o JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN order_payments p ON p.orderid=o.orderid WHERE o.orderid=? AND o.email=?'); mysqli_stmt_bind_param($check, 'is', $orderId, $email); mysqli_stmt_execute($check); $removalOrder = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    if (!in_array($reason, $allowedReasons, true) || !$selected) $notice = 'Choose at least one dish and a cancellation reason.';
    elseif (!$removalOrder || !in_array($removalOrder['status'], array('placed','confirmed'), true)) $notice = 'Dishes cannot be removed after preparation starts.';
    else { mysqli_begin_transaction($conn); $removed = 0; $ok = true; foreach ($selected as $dish) { $itemStmt = mysqli_prepare($conn, 'SELECT pname,pprice,quantity FROM order_items WHERE orderid=? AND pname=? LIMIT 1'); mysqli_stmt_bind_param($itemStmt, 'is', $orderId, $dish); mysqli_stmt_execute($itemStmt); $line = mysqli_fetch_assoc(mysqli_stmt_get_result($itemStmt)); if (!$line) continue; $amount = (float)$line['pprice'] * (int)$line['quantity']; $refundStatus = ($removalOrder['payment_method'] ?? 'cod') === 'cod' ? 'not_required' : 'requested'; $record = mysqli_prepare($conn, 'INSERT INTO order_item_cancellations(orderid,pname,quantity,amount,reason,customer_note,refund_status) VALUES(?,?,?,?,?,?,?)'); mysqli_stmt_bind_param($record, 'isidsss', $orderId, $line['pname'], $line['quantity'], $amount, $reason, $note, $refundStatus); $delete = mysqli_prepare($conn, 'DELETE FROM order_items WHERE orderid=? AND pname=?'); mysqli_stmt_bind_param($delete, 'is', $orderId, $dish); $ok = $ok && mysqli_stmt_execute($record) && mysqli_stmt_execute($delete); $removed += $amount; } if ($ok && $removed > 0) { $financial = mysqli_prepare($conn, 'UPDATE order_financials SET subtotal=GREATEST(0,subtotal-?),total_amount=GREATEST(0,total_amount-?) WHERE orderid=?'); mysqli_stmt_bind_param($financial, 'ddi', $removed, $removed, $orderId); $ok = mysqli_stmt_execute($financial) && orderRecordStatus($conn, $orderId, 'items_removed', 'Selected dishes removed: ' . $reason, $email); $remaining = mysqli_prepare($conn, 'SELECT COUNT(*) total FROM order_items WHERE orderid=?'); mysqli_stmt_bind_param($remaining, 'i', $orderId); mysqli_stmt_execute($remaining); $remainingRow = mysqli_fetch_assoc(mysqli_stmt_get_result($remaining)); if ($ok && empty($remainingRow['total'])) { $statusStmt = mysqli_prepare($conn, "UPDATE order_tracking SET status='cancelled' WHERE orderid=?"); mysqli_stmt_bind_param($statusStmt, 'i', $orderId); $ok = mysqli_stmt_execute($statusStmt) && orderRecordStatus($conn, $orderId, 'cancelled', 'All dishes removed by customer', $email); } } $ok ? mysqli_commit($conn) : mysqli_rollback($conn); $notice = $ok && $removed > 0 ? 'Selected dishes were removed. Any eligible refund is now recorded.' : 'Selected dishes could not be removed.'; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['request_full_cancellation']) || isset($_POST['cancel_order']))) {
    $orderId = (int) ($_POST['orderid'] ?? 0); $reason = trim($_POST['reason'] ?? ''); $note = trim($_POST['customer_note'] ?? '');
    $allowedReasons = array('Ordered by mistake','Change delivery address','Delivery time too long','Want to change items','Other');
    $check = mysqli_prepare($conn, 'SELECT t.status FROM orders o JOIN order_tracking t ON t.orderid=o.orderid WHERE o.orderid=? AND o.email=?'); mysqli_stmt_bind_param($check, 'is', $orderId, $email); mysqli_stmt_execute($check); $orderToCancel = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    if (!in_array($reason, $allowedReasons, true)) $notice = 'Choose a cancellation reason before submitting your request.';
    elseif (!$orderToCancel || !in_array($orderToCancel['status'], array('placed','confirmed'), true)) $notice = 'This order can no longer be cancelled online.';
    else { $stmt = mysqli_prepare($conn, "INSERT INTO order_cancellations(orderid,customer_email,reason,customer_note,status) VALUES(?,?,?,?, 'requested') ON DUPLICATE KEY UPDATE reason=VALUES(reason),customer_note=VALUES(customer_note),status='requested',admin_note=NULL,decided_at=NULL"); mysqli_stmt_bind_param($stmt, 'isss', $orderId, $email, $reason, $note); $notice = $stmt && mysqli_stmt_execute($stmt) ? 'Cancellation request sent. The restaurant will review it before any refund is started.' : 'Your cancellation request could not be submitted.'; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['request_item_cancellation']) || isset($_POST['remove_dishes']))) {
    $orderId = (int) ($_POST['orderid'] ?? 0); $reason = trim($_POST['reason'] ?? ''); $note = trim($_POST['customer_note'] ?? ''); $selected = array_slice(array_unique(array_filter($_POST['dish_names'] ?? array(), 'is_string')), 0, 1); $dish = $selected[0] ?? '';
    $allowedReasons = array('Ordered by mistake','Change delivery address','Delivery time too long','Want to change items','Other');
    $check = mysqli_prepare($conn, 'SELECT t.status,p.payment_method FROM orders o JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN order_payments p ON p.orderid=o.orderid WHERE o.orderid=? AND o.email=?'); mysqli_stmt_bind_param($check, 'is', $orderId, $email); mysqli_stmt_execute($check); $orderToChange = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    if (!$dish || !in_array($reason, $allowedReasons, true)) $notice = 'Select one dish and choose a cancellation reason.';
    elseif (!$orderToChange || !in_array($orderToChange['status'], array('placed','confirmed'), true)) $notice = 'Dishes cannot be removed after preparation starts.';
    else { $itemStmt = mysqli_prepare($conn, 'SELECT pname,pprice,quantity FROM order_items WHERE orderid=? AND pname=? LIMIT 1'); mysqli_stmt_bind_param($itemStmt, 'is', $orderId, $dish); mysqli_stmt_execute($itemStmt); $line = mysqli_fetch_assoc(mysqli_stmt_get_result($itemStmt)); $exists = mysqli_prepare($conn, "SELECT item_cancellation_id FROM order_item_cancellations WHERE orderid=? AND pname=? AND status IN ('requested','approved') LIMIT 1"); mysqli_stmt_bind_param($exists, 'is', $orderId, $dish); mysqli_stmt_execute($exists); $alreadyRequested = mysqli_fetch_assoc(mysqli_stmt_get_result($exists)); if (!$line || $alreadyRequested) $notice = 'That dish already has a cancellation request or has been cancelled.'; else { $amount = (float) $line['pprice'] * (int) $line['quantity']; $refundStatus = ($orderToChange['payment_method'] ?? 'cod') === 'cod' ? 'not_required' : 'requested'; $request = mysqli_prepare($conn, 'INSERT INTO order_item_cancellations(orderid,pname,quantity,amount,reason,customer_note,status,refund_status) VALUES(?,?,?,?,?,?,\'requested\',?)'); mysqli_stmt_bind_param($request, 'isidsss', $orderId, $line['pname'], $line['quantity'], $amount, $reason, $note, $refundStatus); $notice = $request && mysqli_stmt_execute($request) ? 'Dish cancellation request sent for restaurant review.' : 'Your dish cancellation request could not be submitted.'; } }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $notice !== '' && (str_starts_with($notice, 'Cancellation request sent') || str_starts_with($notice, 'Dish cancellation request sent'))) {
    $requestOrderId = (int) ($_POST['orderid'] ?? 0); $kind = isset($_POST['request_item_cancellation']) || isset($_POST['remove_dishes']) ? 'dish cancellation' : 'full-order cancellation';
    createNotification('admin', '', 'Customer ' . $kind . ' request', $email . ' requested a ' . $kind . ' for order #' . $requestOrderId . '.', 'order-details.php');
}
$requestedOrder = max(0, (int) ($_GET['order'] ?? 0));
$sql = 'SELECT o.orderid,o.name,o.address,f.total_amount,f.discount_amount,p.payment_method,p.payment_status,p.reference_label,t.status,t.updated_at,c.reason AS cancellation_reason,c.status AS cancellation_status,r.status AS refund_status,r.amount AS refund_amount FROM orders o LEFT JOIN order_financials f ON f.orderid=o.orderid LEFT JOIN order_payments p ON p.orderid=o.orderid LEFT JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN order_cancellations c ON c.orderid=o.orderid LEFT JOIN order_refunds r ON r.orderid=o.orderid WHERE o.email=?';
if ($requestedOrder) $sql .= ' AND o.orderid=?';
$sql .= ' ORDER BY o.orderid DESC';
$stmt = mysqli_prepare($conn, $sql);
if ($requestedOrder) mysqli_stmt_bind_param($stmt, 'si', $email, $requestedOrder); else mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
$orderItems = array();
if ($orders) {
    $itemStmt = mysqli_prepare($conn, "SELECT i.orderid,i.pname,i.pprice,i.quantity,COALESCE(NULLIF(f.picture,''),(SELECT g.image_path FROM site_gallery_images g WHERE g.category_name=f.category AND g.is_visible=1 ORDER BY g.sort_order,g.image_id LIMIT 1)) picture FROM order_items i JOIN orders o ON o.orderid=i.orderid LEFT JOIN foods f ON f.name=i.pname WHERE o.email=? ORDER BY i.orderid DESC");
    if ($itemStmt) {
        mysqli_stmt_bind_param($itemStmt, 's', $email); mysqli_stmt_execute($itemStmt);
        $itemResult = mysqli_stmt_get_result($itemStmt);
        while ($item = mysqli_fetch_assoc($itemResult)) $orderItems[(int) $item['orderid']][] = $item;
    }
}
$cancelledDishRecords = array();
if ($orders) {
    $cancelledStmt = mysqli_prepare($conn, 'SELECT c.orderid,c.pname,c.quantity,c.amount,c.reason,c.status,c.refund_status,c.created_at FROM order_item_cancellations c JOIN orders o ON o.orderid=c.orderid WHERE o.email=? ORDER BY c.created_at DESC,c.item_cancellation_id DESC');
    if ($cancelledStmt) {
        mysqli_stmt_bind_param($cancelledStmt, 's', $email); mysqli_stmt_execute($cancelledStmt);
        $cancelledResult = mysqli_stmt_get_result($cancelledStmt);
        while ($cancelledDish = mysqli_fetch_assoc($cancelledResult)) $cancelledDishRecords[(int) $cancelledDish['orderid']][] = $cancelledDish;
    }
}
$receiptPrompt = null;
$receiptStmt = mysqli_prepare($conn, "SELECT o.orderid FROM orders o JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN delivery_feedback df ON df.orderid=o.orderid AND df.customer_email=o.email WHERE o.email=? AND t.status='delivered' AND (df.feedback_id IS NULL OR df.receipt_confirmed=0) ORDER BY o.orderid DESC LIMIT 1");
if ($receiptStmt) {
    mysqli_stmt_bind_param($receiptStmt, 's', $email); mysqli_stmt_execute($receiptStmt);
    $receiptPrompt = mysqli_fetch_assoc(mysqli_stmt_get_result($receiptStmt));
}
$steps = array('placed' => 'Order placed', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'ready_for_pickup' => 'Ready for pickup', 'rider_assigned' => 'Rider assigned', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'completed' => 'Completed');
$statusRank = array('placed' => 1, 'confirmed' => 2, 'preparing' => 3, 'ready_for_pickup' => 4, 'rider_assigned' => 5, 'out_for_delivery' => 6, 'delivered' => 7, 'completed' => 8);
$methodLabels = array('cod' => 'Cash on delivery', 'upi' => 'UPI', 'card' => 'Card', 'netbanking' => 'Net banking');
$pageTitle = 'My Orders'; $activePage = 'orders'; $bodyClass = 'order-history-page'; $pageStyles = array('css/order-history.css', 'css/order-actions.css', 'css/order-details.css', 'css/order-dishes.css', 'css/order-cancellations.css', 'css/order-cancellation-controls.css', 'css/order-history-pro.css');
$pageStyles[] = 'css/order-history-selectors.css';
$pageStyles[] = 'css/order-history-tabs.css';
$pageStyles[] = 'css/delivery-feedback.css';
include __DIR__ . '/includes/public-header.php';
?>
<main class="order-history-main"><header class="order-history-heading"><div><p>Your food, in motion</p><h1>My orders</h1></div><a href="menu.php">Order again <i class="bi bi-arrow-right" aria-hidden="true"></i></a></header><?php if ($notice) { ?><p class="order-notice"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?><?php if (!$orders) { ?><section class="order-history-empty"><i class="bi bi-bag" aria-hidden="true"></i><h2>No orders yet</h2><p>Your placed orders and delivery progress will appear here.</p><a href="menu.php">Browse menu</a></section><?php } else { foreach ($orders as $order) { $status = $order['status'] ?: 'placed'; $rank = $statusRank[$status] ?? 1; $canEditItems=in_array($status,array('placed','confirmed'),true); ?><article class="order-track-card"><div class="order-track-top"><div><p>Order #<?php echo (int) $order['orderid']; ?></p><h2><?php echo $status === 'cancelled' ? 'Order cancelled' : ($steps[$status] ?? 'Order placed'); ?></h2></div><strong>Rs <?php echo number_format((float) ($order['total_amount'] ?? 0)); ?></strong></div><section class="order-items-detail"><h3>Your dishes<?php if($canEditItems){ ?><small>Select dishes to remove</small><?php } ?></h3><?php foreach ($orderItems[(int) $order['orderid']] ?? array() as $item) { ?><label class="order-dish"><input type="checkbox" name="dish_names[]" value="<?php echo htmlspecialchars($item['pname'], ENT_QUOTES, 'UTF-8'); ?>" form="remove-dishes-<?php echo (int)$order['orderid']; ?>" <?php echo $canEditItems?'':'disabled'; ?>><span class="order-dish-image"><?php if(!empty($item['picture'])){ ?><img src="<?php echo htmlspecialchars($item['picture'],ENT_QUOTES,'UTF-8'); ?>" alt=""><?php } else { ?><i class="bi bi-egg-fried" aria-hidden="true"></i><?php } ?></span><span class="order-dish-copy"><strong><?php echo htmlspecialchars($item['pname'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Quantity <?php echo (int)$item['quantity']; ?> · Rs <?php echo number_format((int)$item['pprice']); ?> each</small></span><b>Rs <?php echo number_format((int) $item['pprice'] * (int) $item['quantity']); ?></b></label><?php } ?></section><?php if ($status === 'cancelled') { ?><p class="order-cancelled"><i class="bi bi-x-circle" aria-hidden="true"></i> This order was cancelled.<?php if ($order['refund_status']) { ?> Refund: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['refund_status'])), ENT_QUOTES, 'UTF-8'); ?><?php } ?></p><?php } else { ?><ol class="order-timeline"><?php foreach ($steps as $key => $label) { $stepRank = $statusRank[$key]; ?><li class="<?php echo $stepRank <= $rank ? 'is-complete' : ''; ?> <?php echo $stepRank === $rank ? 'is-current' : ''; ?>"><i class="bi <?php echo $key === 'placed' ? 'bi-receipt' : ($key === 'confirmed' ? 'bi-patch-check' : ($key === 'preparing' ? 'bi-fire' : ($key === 'out_for_delivery' ? 'bi-truck' : 'bi-house-check'))); ?>" aria-hidden="true"></i><span><?php echo $label; ?></span></li><?php } ?></ol><?php if ($canEditItems) { ?><form method="post" id="remove-dishes-<?php echo (int)$order['orderid']; ?>" class="order-cancel-form"><input type="hidden" name="orderid" value="<?php echo (int) $order['orderid']; ?>"><label>Reason for dish removal <em aria-hidden="true">*</em><select name="reason" required><option value="" selected disabled>Choose a reason</option><option>Ordered by mistake</option><option>Change delivery address</option><option>Delivery time too long</option><option>Want to change items</option><option>Other</option></select></label><label>Note (optional)<input name="customer_note" maxlength="500" placeholder="Tell us anything useful"></label><button name="remove_dishes" value="1" onclick="return confirm('Remove the selected dishes?')"><i class="bi bi-trash" aria-hidden="true"></i> Remove selected dishes</button><a href="cancellation-refund-policy.php">Cancellation and refund policy</a></form><form method="post" class="order-cancel-form"><input type="hidden" name="orderid" value="<?php echo (int) $order['orderid']; ?>"><label>Cancel full order <em aria-hidden="true">*</em><select name="reason" required><option value="" selected disabled>Choose a reason</option><option>Ordered by mistake</option><option>Change delivery address</option><option>Delivery time too long</option><option>Want to change items</option><option>Other</option></select></label><label>Note (optional)<input name="customer_note" maxlength="500" placeholder="Tell us anything useful"></label><button name="cancel_order" value="1" onclick="return confirm('Cancel this full order?')"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancel full order</button></form><?php } else { ?><p class="order-policy-note">Dish removal and cancellation are no longer available online once preparation begins. <a href="cancellation-refund-policy.php">View policy</a></p><?php } ?><?php } ?><div class="order-track-meta"><span><i class="bi bi-credit-card" aria-hidden="true"></i> <?php echo htmlspecialchars($methodLabels[$order['payment_method']] ?? 'Payment pending', ENT_QUOTES, 'UTF-8'); ?></span><span><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></span><span><i class="bi bi-clock" aria-hidden="true"></i> Updated <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($order['updated_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span></div></article><?php } } ?></main>
<?php if ($receiptPrompt) { ?><dialog class="delivery-feedback-dialog" id="receipt-prompt"><section><p class="delivery-feedback-kicker">Delivery complete</p><h2>Have you received your order?</h2><p>Order #<?php echo (int) $receiptPrompt['orderid']; ?> is marked delivered. Confirm receipt or report a concern so Foodera can help.</p><div class="delivery-feedback-actions"><a class="delivery-feedback-primary" href="track-order.php?order=<?php echo (int) $receiptPrompt['orderid']; ?>">Review delivery</a><button type="button" class="delivery-feedback-link" data-dismiss-receipt>Not now</button></div></section></dialog><script>(()=>{const dialog=document.querySelector('#receipt-prompt');if(!dialog)return;dialog.showModal();dialog.querySelector('[data-dismiss-receipt]').addEventListener('click',()=>dialog.close());})();</script><?php } ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderMain = document.querySelector('.order-history-main');
    const heading = document.querySelector('.order-history-heading');
    const cards = Array.from(document.querySelectorAll('.order-track-card'));
    if (orderMain && heading && cards.length) {
        const isHistory = card => /^(Completed|Order cancelled)$/i.test((card.querySelector('.order-track-top h2') || {}).textContent.trim());
        const groups = { active: cards.filter(card => !isHistory(card)), history: cards.filter(isHistory) };
        const tabs = document.createElement('div'); tabs.className = 'order-history-tabs'; tabs.setAttribute('role', 'tablist'); tabs.setAttribute('aria-label', 'Order views');
        const empty = document.createElement('p'); empty.className = 'order-history-tab-empty'; empty.hidden = true;
        const buttons = {};
        [['active', 'Active orders'], ['history', 'History']].forEach(([key, label]) => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'order-history-tab'; button.setAttribute('role', 'tab'); button.dataset.view = key;
            button.innerHTML = label + '<span class="order-history-tab-count">' + groups[key].length + '</span>';
            button.addEventListener('click', () => show(key)); tabs.append(button); buttons[key] = button;
        });
        heading.after(tabs); tabs.after(empty);
        function show(view) {
            Object.keys(groups).forEach(key => groups[key].forEach(card => card.hidden = key !== view));
            Object.entries(buttons).forEach(([key, button]) => button.setAttribute('aria-selected', String(key === view)));
            empty.hidden = groups[view].length > 0;
            empty.innerHTML = view === 'history' ? '<strong>No completed orders yet</strong>Your completed and cancelled orders will be kept here.' : '<strong>No active orders right now</strong>Completed orders are available in History.';
            try { sessionStorage.setItem('foodera-order-view', view); } catch (error) {}
        }
        let saved = 'active'; try { saved = sessionStorage.getItem('foodera-order-view') || 'active'; } catch (error) {}
        show(saved === 'history' ? 'history' : 'active');
    }
    const cancelledByOrder = <?php echo json_encode($cancelledDishRecords, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const workflowByOrder = Object.fromEntries(<?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>.map(function (order) { return [String(order.orderid), order]; }));
    document.querySelectorAll('.order-track-card').forEach(function (card) {
        const orderMatch = (card.querySelector('.order-track-top p') || {}).textContent.match(/#(\d+)/);
        if (!orderMatch) return;
        const orderId = orderMatch[1];
        const form = card.querySelector('form[id^="remove-dishes-"]');
        const dishes = card.querySelector('.order-items-detail');
        const inputs = card.querySelectorAll('input[name="dish_names[]"]');
        const headingHint = dishes.querySelector('h3 small');

        if (form) {
            inputs.forEach(function (input) { input.type = 'radio'; input.name = 'dish_names[]'; });
            dishes.classList.add('is-editable');
            if (headingHint) headingHint.textContent = 'Select one dish to remove';
            const button = form.querySelector('button[name="remove_dishes"]');
            const selectionNote = document.createElement('p');
            selectionNote.className = 'order-dish-selection-note';
            selectionNote.innerHTML = '<i class="bi bi-info-circle" aria-hidden="true"></i> Select one dish to review its cancellation options.';
            form.classList.add('order-dish-removal');
            form.hidden = true;
            const dishChange = document.createElement('details');
            dishChange.className = 'order-dish-change';
            const dishChangeSummary = document.createElement('summary');
            dishChangeSummary.innerHTML = '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Need to remove a dish?</span><small>Available before preparation starts</small>';
            dishChange.append(dishChangeSummary, selectionNote, form);
            dishes.after(dishChange);
            inputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    dishChange.open = true;
                    form.hidden = false;
                    form.classList.add('is-ready');
                    selectionNote.innerHTML = '<i class="bi bi-check-circle" aria-hidden="true"></i> ' + input.closest('.order-dish').querySelector('.order-dish-copy strong').textContent + ' selected for removal.';
                });
            });
            if (button) {
                button.name = 'request_item_cancellation';
                button.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i> Remove selected dish';
                button.setAttribute('onclick', "return confirm('Send this dish cancellation request to the restaurant?')");
            }
        }

        const cancellationForms = Array.from(card.querySelectorAll('form.order-cancel-form'));
        const fullOrderForm = cancellationForms.find(function (candidate) { return candidate !== form; });
        if (fullOrderForm) {
            const fullCancel = document.createElement('details');
            fullCancel.className = 'order-full-cancel';
            const summary = document.createElement('summary');
            summary.innerHTML = '<i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>Need to cancel the entire order?</span><small>Review cancellation options</small>';
            const policyLink = document.createElement('a');
            policyLink.className = 'order-cancellation-policy-link';
            policyLink.href = 'cancellation-refund-policy.php';
            policyLink.innerHTML = '<i class="bi bi-file-earmark-text" aria-hidden="true"></i> Cancellation and refund policy';
            const fullCancelButton = fullOrderForm.querySelector('button[name="cancel_order"]');
            if (fullCancelButton) {
                fullCancelButton.name = 'request_full_cancellation';
                fullCancelButton.innerHTML = '<i class="bi bi-send" aria-hidden="true"></i> Send cancellation request';
                fullCancelButton.setAttribute('onclick', "return confirm('Send this cancellation request to the restaurant?')");
            }
            fullOrderForm.before(fullCancel);
            fullOrderForm.appendChild(policyLink);
            fullCancel.append(summary, fullOrderForm);
        }

        const cancelled = cancelledByOrder[orderId] || [];
        const workflow = workflowByOrder[orderId] || {};
        if (workflow.cancellation_status === 'requested' || workflow.refund_status) {
            const workflowNote = document.createElement('p');
            workflowNote.className = 'order-workflow-note';
            const cancellationText = workflow.cancellation_status === 'requested' ? 'Full-order cancellation request is awaiting restaurant review.' : '';
            const refundText = workflow.refund_status ? 'Refund status: ' + String(workflow.refund_status).replaceAll('_', ' ') + '.' : '';
            workflowNote.innerHTML = '<i class="bi bi-clock-history" aria-hidden="true"></i> ' + [cancellationText, refundText].filter(Boolean).join(' ');
            dishes.after(workflowNote);
        }
        if (!cancelled.length || !dishes) return;
        const totalLines = inputs.length + cancelled.length;
        const stack = document.createElement('section');
        stack.className = 'order-cancellation-stack';
        const title = document.createElement('h4');
        title.textContent = 'Dish cancellation record';
        const summary = document.createElement('p');
        summary.textContent = cancelled.length + ' of ' + totalLines + ' dish line' + (totalLines === 1 ? '' : 's') + ' cancelled';
        const list = document.createElement('ul');
        cancelled.forEach(function (item) {
            const row = document.createElement('li');
            const name = document.createElement('strong');
            name.textContent = item.quantity + ' x ' + item.pname;
            const details = document.createElement('span');
            details.textContent = 'Rs ' + Number(item.amount).toLocaleString('en-IN') + ' - ' + item.reason + ' - ' + item.status;
            row.append(name, details); list.appendChild(row);
        });
        stack.append(title, summary, list); dishes.appendChild(stack);
    });
});
</script>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
