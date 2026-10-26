<?php
require_once __DIR__ . '/app-functions.php';
require_once __DIR__ . '/includes/offers.php';
require_once __DIR__ . '/includes/site-content.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['cart_csrf'])) $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
function cartEscape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
$email = $_SESSION['email'];
$customerResult = getAllCustomer($email);
$customer = $customerResult ? mysqli_fetch_assoc($customerResult) : null;
if (!$customer) { header('Location: logout.php'); exit; }
$addresses = getCustomerAddresses($email);
$items = array(); $total = 0; $itemCount = 0; $unavailableItems = 0; $validCart = array();
$galleryImages = array();
foreach (siteGallery() as $galleryImage) {
    $category = strtolower(trim((string) ($galleryImage['category_name'] ?? '')));
    if ($category !== '' && empty($galleryImages[$category]) && !empty($galleryImage['image_path'])) $galleryImages[$category] = $galleryImage['image_path'];
}
$conn = dbConnect();
if ($conn && !empty($_SESSION['cart'])) {
    $stmt = mysqli_prepare($conn, 'SELECT id,name,price,picture,category FROM foods WHERE id=? AND is_active=1');
    foreach ($_SESSION['cart'] as $item) {
        $id = (int) ($item['Product_id'] ?? 0);
        $quantity = max(1, min(10, (int) ($item['quantity'] ?? 1)));
        if (!$stmt || $id <= 0) continue;
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $food = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$food) { $unavailableItems++; continue; }
        $food['quantity'] = $quantity;
        $food['line_total'] = (float) $food['price'] * $quantity;
        $food['display_picture'] = !empty($food['picture']) ? $food['picture'] : ($galleryImages[strtolower(trim($food['category']))] ?? '');
        $total += $food['line_total'];
        $itemCount += $quantity;
        $items[] = $food;
        $validCart[] = array('Product_id' => $food['id'], 'quantity' => $quantity);
    }
    if ($unavailableItems) {
        $_SESSION['cart'] = $validCart;
        unset($_SESSION['offer_code']);
    }
}
$offerResult = $items ? offerResolveCart($email, $items, $total, $_SESSION['offer_code'] ?? '') : array('offer' => null, 'discount' => 0, 'message' => '');
$appliedOffer = $offerResult['offer'];
$requestedOffer = $offerResult['requested_offer'] ?? null;
$discount = (float) ($offerResult['discount'] ?? 0);
$eligibleSubtotal = (float) ($offerResult['eligible_subtotal'] ?? 0);
$payableTotal = max(0, $total - $discount);
$couponInput = $_SESSION['offer_code'] ?? ($_SESSION['cart_offer_attempt'] ?? '');
$notice = $_SESSION['cart_notice'] ?? ($unavailableItems ? ($unavailableItems === 1 ? 'An unavailable menu item was removed from your cart.' : 'Unavailable menu items were removed from your cart.') : '');
unset($_SESSION['cart_notice']);
$pageTitle = 'My Cart'; $activePage = 'cart'; $bodyClass = 'cart-page'; $pageStyles = array('css/cart.css'); $pageScripts = array('js/cart-payment.js');
include __DIR__ . '/includes/public-header.php';
?>
<main class="cart-main">
    <header class="cart-heading"><div><p>Ready to order?</p><h1>Your cart</h1></div><a href="menu.php">Continue browsing <i class="bi bi-arrow-right" aria-hidden="true"></i></a></header>
    <?php if ($notice) { ?><p class="cart-notice" role="alert"><?php echo cartEscape($notice); ?></p><?php } ?>
    <?php if (!$conn) { ?><p class="cart-notice" role="alert">Cart is unavailable while the database is offline.</p><?php } elseif (!$items) { ?><section class="cart-empty"><i class="bi bi-bag" aria-hidden="true"></i><h2>Your cart is empty</h2><p>Find something delicious on the menu.</p><a class="cart-button" href="menu.php">Browse menu</a></section><?php } else { ?>
    <div class="cart-layout">
        <section class="cart-items" aria-label="Cart items">
            <?php foreach ($items as $food) { ?><article class="cart-item">
                <?php if (!empty($food['display_picture'])) { ?><img src="<?php echo cartEscape($food['display_picture']); ?>" alt="<?php echo cartEscape($food['name']); ?>" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'cart-item-placeholder',innerHTML:'<i class=&quot;bi bi-egg-fried&quot; aria-hidden=&quot;true&quot;></i>'}))"><?php } else { ?><div class="cart-item-placeholder" aria-hidden="true"><i class="bi bi-egg-fried"></i></div><?php } ?>
                <div class="cart-item-detail"><h2><?php echo cartEscape($food['name']); ?></h2><p><?php echo cartEscape($food['category']); ?> &middot; Rs <?php echo number_format((float) $food['price']); ?> each</p><strong>Line total: Rs <?php echo number_format($food['line_total']); ?></strong></div>
                <div class="cart-item-actions"><form method="post" action="manage-cart.php" class="cart-quantity"><input type="hidden" name="csrf" value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"><input type="hidden" name="pid" value="<?php echo (int) $food['id']; ?>"><label for="qty-<?php echo (int) $food['id']; ?>">Qty</label><input id="qty-<?php echo (int) $food['id']; ?>" type="number" name="Mod_Quantity" min="1" max="10" value="<?php echo $food['quantity']; ?>"><button type="submit" aria-label="Update quantity" title="Update quantity"><i class="bi bi-check-lg" aria-hidden="true"></i></button></form><form method="post" action="manage-cart.php"><input type="hidden" name="csrf" value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"><input type="hidden" name="pid" value="<?php echo (int) $food['id']; ?>"><button class="cart-remove" type="submit" name="deletecart" value="1" aria-label="Remove <?php echo cartEscape($food['name']); ?>" title="Remove item"><i class="bi bi-trash" aria-hidden="true"></i></button></form></div>
            </article><?php } ?>
        </section>
        <aside class="cart-checkout"><h2>Order summary</h2><div class="cart-summary-row"><span>Items (<?php echo (int) $itemCount; ?>)</span><strong>Rs <?php echo number_format($total); ?></strong></div>
            <form method="post" action="manage-offer.php" class="cart-offer-form"><input type="hidden" name="csrf" value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"><label for="offer-code">Coupon code</label><div><input id="offer-code" name="offer_code" value="<?php echo cartEscape($couponInput); ?>" maxlength="40" placeholder="e.g. WELCOME50"><button type="submit">Apply</button></div><small>Enter a code exactly as shown on the offer. Eligibility is checked against the current cart.</small><a href="offers.php">View active offers <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></form>
            <?php if ($appliedOffer) { ?><div class="cart-applied-offer"><div><i class="bi bi-patch-check-fill" aria-hidden="true"></i><span><strong><?php echo cartEscape($appliedOffer['title']); ?></strong><small><?php echo cartEscape(offerFormatValue($appliedOffer)); ?> on Rs <?php echo number_format($eligibleSubtotal); ?> eligible items</small></span></div><strong>- Rs <?php echo number_format($discount); ?></strong><?php if (!empty($_SESSION['offer_code'])) { ?><form method="post" action="manage-offer.php"><input type="hidden" name="csrf" value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"><button name="clear_offer" value="1" aria-label="Remove offer" title="Remove offer"><i class="bi bi-x-lg" aria-hidden="true"></i></button></form><?php } ?></div><?php } elseif ($offerResult['message']) { ?><div class="cart-offer-error" role="alert"><strong><?php echo cartEscape($couponInput ?: 'This coupon'); ?> cannot be applied yet.</strong><span><?php echo cartEscape($offerResult['message']); ?></span><?php if ($requestedOffer && (float) $requestedOffer['minimum_order'] > 0) { ?><small>Minimum cart value: Rs <?php echo number_format((float) $requestedOffer['minimum_order']); ?><?php echo $requestedOffer['customer_scope'] === 'first_order' ? ' · first order only' : ''; ?></small><?php } ?></div><?php } ?>
            <div class="cart-summary-row cart-discount-row"><span>Food discount</span><strong>- Rs <?php echo number_format($discount); ?></strong></div><div class="cart-grand-total"><span>Payable now</span><strong>Rs <?php echo number_format($payableTotal); ?></strong></div><p class="cart-total-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Prices and offer eligibility are checked again when the order is placed. Delivery fee will be shown when delivery zones are enabled.</p>
            <form method="post" action="order.php" class="cart-checkout-form" id="checkout-form"><input type="hidden" name="csrf" value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"><label>Name<input name="name" maxlength="50" value="<?php echo cartEscape($customer['user']); ?>" required></label><label>Phone<input name="phone" inputmode="numeric" pattern="[0-9]{10}" value="<?php echo cartEscape($customer['phone']); ?>" required></label>
                <?php if ($addresses) { ?><label>Delivery address<select name="address_id" id="saved-address"><option value="">Choose an address</option><?php foreach ($addresses as $address) { ?><option value="<?php echo (int) $address['address_id']; ?>" <?php echo $address['is_default'] ? 'selected' : ''; ?>><?php echo cartEscape($address['label'] . ' - ' . $address['address_line']); ?></option><?php } ?></select></label><?php } ?>
                <label>Or use a one-time address<textarea name="address" maxlength="255" rows="3" placeholder="Street, area, city and delivery details"></textarea></label><a class="cart-address-link" href="profile.php">Manage saved addresses <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                <fieldset class="payment-methods"><legend>Payment method</legend><p>Choose how you want to pay for this order.</p><div class="payment-method-grid"><label class="payment-method"><input type="radio" name="payment_method" value="cod" checked><span><i class="bi bi-cash-stack" aria-hidden="true"></i><strong>Cash on delivery</strong><small>Pay when your order arrives</small></span></label><label class="payment-method"><input type="radio" name="payment_method" value="upi"><span><i class="bi bi-qr-code" aria-hidden="true"></i><strong>UPI</strong><small>Google Pay, PhonePe, Paytm</small></span></label><label class="payment-method"><input type="radio" name="payment_method" value="card"><span><i class="bi bi-credit-card-2-front" aria-hidden="true"></i><strong>Card</strong><small>Credit or debit card</small></span></label><label class="payment-method"><input type="radio" name="payment_method" value="netbanking"><span><i class="bi bi-bank" aria-hidden="true"></i><strong>Net banking</strong><small>Pay through your bank</small></span></label></div><div class="payment-detail" data-payment-detail="upi" hidden><label for="upi-id">UPI ID<input id="upi-id" name="upi_id" maxlength="100" placeholder="name@bank" autocomplete="off" inputmode="email"></label><p class="payment-security-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> You will approve payment in your UPI app.</p></div><div class="payment-detail" data-payment-detail="card" hidden><label for="card-holder">Name on card<input id="card-holder" name="card_holder" maxlength="100" autocomplete="cc-name"></label><label for="card-number">Card number<input id="card-number" name="card_number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number"></label><div class="payment-field-row"><label for="card-expiry">Expiry date<input id="card-expiry" name="card_expiry" inputmode="numeric" maxlength="5" placeholder="MM/YY" autocomplete="cc-exp"></label><label for="card-cvv">CVV<input id="card-cvv" name="card_cvv" type="password" inputmode="numeric" maxlength="4" placeholder="123" autocomplete="cc-csc"></label></div><p class="payment-security-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> Card details are used only for this payment and are not saved.</p></div><div class="payment-detail" data-payment-detail="netbanking" hidden><label for="bank-name">Select bank<select id="bank-name" name="bank_name"><option value="">Choose your bank</option><option>State Bank of India</option><option>HDFC Bank</option><option>ICICI Bank</option><option>Axis Bank</option><option>Kotak Mahindra Bank</option><option>Other bank</option></select></label><label for="bank-account-name">Account holder name<input id="bank-account-name" name="bank_account_name" maxlength="100" placeholder="Name as registered with your bank"></label><p class="payment-security-note"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> You will securely sign in and approve payment on your bank's website. Never enter your bank password or PIN here.</p></div></fieldset><label class="policy-acceptance"><input type="checkbox" name="policy_acceptance" value="1" required><span>I have read and agree to the <a href="terms.php" target="_blank" rel="noopener">Terms and Conditions</a>, <a href="privacy.php" target="_blank" rel="noopener">Privacy Notice</a>, <a href="cancellation-refund-policy.php" target="_blank" rel="noopener">Cancellation and Refund Policy</a>, and <a href="ethical-use-policy.php" target="_blank" rel="noopener">Ethical Use Policy</a>.</span></label><p class="policy-acceptance-note">Your acceptance and the current policy version are recorded with this order.</p><button type="submit" name="pay" value="1" class="cart-button">Place order securely <i class="bi bi-lock-fill" aria-hidden="true"></i></button></form>
        </aside>
    </div><?php } ?>
</main>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
