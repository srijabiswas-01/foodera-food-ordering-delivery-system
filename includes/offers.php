<?php
require_once __DIR__ . '/../app-functions.php';

function ensureOfferTables()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $conn = dbConnect();
    if (!$conn) return $ready = false;

    $categories = "CREATE TABLE IF NOT EXISTS offer_categories (
        offer_category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        description VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY offer_categories_name_unique (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $offers = "CREATE TABLE IF NOT EXISTS offers (
        offer_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        offer_category_id INT NOT NULL,
        title VARCHAR(140) NOT NULL,
        description VARCHAR(600) NOT NULL,
        terms TEXT NOT NULL,
        code VARCHAR(40) DEFAULT NULL,
        discount_type ENUM('percentage','fixed','free_delivery') NOT NULL DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
        maximum_discount DECIMAL(10,2) DEFAULT NULL,
        minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0,
        applicable_food_category VARCHAR(80) DEFAULT NULL,
        customer_scope ENUM('all','first_order') NOT NULL DEFAULT 'all',
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        total_usage_limit INT DEFAULT NULL,
        per_customer_limit INT NOT NULL DEFAULT 1,
        daily_usage_limit INT DEFAULT NULL,
        is_automatic TINYINT(1) NOT NULL DEFAULT 0,
        is_combinable TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        image_path VARCHAR(255) NOT NULL DEFAULT '',
        priority INT NOT NULL DEFAULT 0,
        minimum_eligible_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
        days_of_week VARCHAR(32) NOT NULL DEFAULT '',
        daily_start_time TIME DEFAULT NULL,
        daily_end_time TIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY offers_code_unique (code),
        KEY offers_active_dates_idx (is_active, starts_at, ends_at),
        KEY offers_category_idx (offer_category_id),
        CONSTRAINT offers_category_fk FOREIGN KEY (offer_category_id) REFERENCES offer_categories(offer_category_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $redemptions = "CREATE TABLE IF NOT EXISTS offer_redemptions (
        redemption_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        offer_id INT NOT NULL,
        order_id INT DEFAULT NULL,
        customer_email VARCHAR(100) NOT NULL,
        offer_code VARCHAR(40) DEFAULT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) NOT NULL,
        redeemed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY offer_redemptions_offer_idx (offer_id, redeemed_at),
        KEY offer_redemptions_customer_idx (customer_email, offer_id, redeemed_at),
        KEY offer_redemptions_order_idx (order_id),
        CONSTRAINT offer_redemptions_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $orderFinancials = "CREATE TABLE IF NOT EXISTS order_financials (
        orderid INT NOT NULL PRIMARY KEY,
        subtotal DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL,
        offer_id INT DEFAULT NULL,
        offer_code VARCHAR(40) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY order_financials_offer_idx (offer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $foodCategories = "CREATE TABLE IF NOT EXISTS food_categories (
        category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $targets = "CREATE TABLE IF NOT EXISTS offer_food_categories (
        offer_id INT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (offer_id, category_id),
        CONSTRAINT offer_food_categories_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE CASCADE,
        CONSTRAINT offer_food_categories_category_fk FOREIGN KEY (category_id) REFERENCES food_categories(category_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $itemTargets = "CREATE TABLE IF NOT EXISTS offer_food_items (
        offer_id INT NOT NULL,
        food_id INT NOT NULL,
        PRIMARY KEY (offer_id, food_id),
        CONSTRAINT offer_food_items_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE CASCADE,
        CONSTRAINT offer_food_items_food_fk FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $ok = mysqli_query($conn, $categories) && mysqli_query($conn, $offers) && mysqli_query($conn, $redemptions) && mysqli_query($conn, $orderFinancials) && mysqli_query($conn, $foodCategories) && mysqli_query($conn, $targets) && mysqli_query($conn, $itemTargets);
    if (!$ok) return $ready = false;
    foreach (array(
        "ALTER TABLE offers ADD COLUMN priority INT NOT NULL DEFAULT 0 AFTER image_path",
        "ALTER TABLE offers ADD COLUMN minimum_eligible_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER priority",
        "ALTER TABLE offers ADD COLUMN days_of_week VARCHAR(32) NOT NULL DEFAULT '' AFTER minimum_eligible_subtotal",
        "ALTER TABLE offers ADD COLUMN daily_start_time TIME DEFAULT NULL AFTER days_of_week",
        "ALTER TABLE offers ADD COLUMN daily_end_time TIME DEFAULT NULL AFTER daily_start_time"
    ) as $migration) @mysqli_query($conn, $migration);
    return $ready = true;
}

function offerEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function offerCategories($activeOnly = false)
{
    if (!ensureOfferTables()) return array();
    $conn = dbConnect();
    if (!$conn) return array();
    $sql = 'SELECT * FROM offer_categories' . ($activeOnly ? ' WHERE is_active=1' : '') . ' ORDER BY sort_order, name';
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function offerGet($offerId)
{
    if (!ensureOfferTables()) return null;
    $conn = dbConnect();
    if (!$conn) return null;
    $stmt = mysqli_prepare($conn, 'SELECT o.*, c.name AS category_name, c.is_active AS offer_category_active FROM offers o JOIN offer_categories c ON c.offer_category_id=o.offer_category_id WHERE o.offer_id=?');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $offerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function offerPublicList()
{
    if (!ensureOfferTables()) return array();
    $conn = dbConnect();
    if (!$conn) return array();
    $sql = "SELECT o.*, c.name AS category_name, c.is_active AS offer_category_active FROM offers o JOIN offer_categories c ON c.offer_category_id=o.offer_category_id WHERE o.is_active=1 AND c.is_active=1 AND o.starts_at<=NOW() AND o.ends_at>=NOW() ORDER BY o.is_automatic DESC, o.minimum_order, o.ends_at";
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function offerFormatValue($offer)
{
    if ($offer['discount_type'] === 'percentage') return rtrim(rtrim(number_format((float) $offer['discount_value'], 2), '0'), '.') . '% off';
    if ($offer['discount_type'] === 'free_delivery') return 'Free delivery';
    return 'Rs ' . number_format((float) $offer['discount_value'], 0) . ' off';
}

function offerCartCategories($items)
{
    $categories = array();
    foreach ($items as $item) {
        $category = strtolower(trim((string) ($item['category'] ?? '')));
        if ($category !== '') $categories[$category] = true;
    }
    return $categories;
}

function offerTargetCategoryNames($offerId, $conn = null)
{
    $conn = $conn ?: dbConnect();
    if (!$conn || $offerId < 1) return array();
    $stmt = mysqli_prepare($conn, 'SELECT c.name FROM offer_food_categories t JOIN food_categories c ON c.category_id=t.category_id WHERE t.offer_id=?');
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 'i', $offerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $names = array();
    while ($result && ($row = mysqli_fetch_assoc($result))) $names[] = $row['name'];
    return $names;
}

function offerTargetFoodIds($offerId, $conn = null)
{
    $conn = $conn ?: dbConnect();
    if (!$conn || $offerId < 1) return array();
    $stmt = mysqli_prepare($conn, 'SELECT food_id FROM offer_food_items WHERE offer_id=?');
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 'i', $offerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? array_map('intval', array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'food_id')) : array();
}

function offerUsageCount($conn, $offerId, $email = null, $todayOnly = false)
{
    $sql = 'SELECT COUNT(*) AS total FROM offer_redemptions WHERE offer_id=?';
    $types = 'i'; $params = array($offerId);
    if ($email !== null) { $sql .= ' AND customer_email=?'; $types .= 's'; $params[] = $email; }
    if ($todayOnly) $sql .= ' AND DATE(redeemed_at)=CURDATE()';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (int) ($row['total'] ?? 0);
}

function offerIsFirstOrderCustomer($conn, $email)
{
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM orders WHERE email=?');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (int) ($row['total'] ?? 0) === 0;
}

function offerEligibility($offer, $email, $items, $subtotal, $conn = null)
{
    $conn = $conn ?: dbConnect();
    if (!$conn) return array('valid' => false, 'message' => 'Offers are unavailable while the database is offline.');
    if (!(int) $offer['is_active']) return array('valid' => false, 'message' => 'This offer is no longer active.');
    if (isset($offer['offer_category_active']) && !(int) $offer['offer_category_active']) return array('valid' => false, 'message' => 'This offer category is currently paused.');
    $timeStmt = mysqli_prepare($conn, 'SELECT NOW() BETWEEN ? AND ? AS is_current');
    if (!$timeStmt) return array('valid' => false, 'message' => 'Offer availability could not be checked.');
    mysqli_stmt_bind_param($timeStmt, 'ss', $offer['starts_at'], $offer['ends_at']);
    mysqli_stmt_execute($timeStmt);
    $timeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($timeStmt));
    if (empty($timeRow['is_current'])) return array('valid' => false, 'message' => 'This offer is not active at this time.');
    $day = (string) ((int) date('N'));
    $allowedDays = array_filter(explode(',', (string) ($offer['days_of_week'] ?? '')));
    if ($allowedDays && !in_array($day, $allowedDays, true)) return array('valid' => false, 'message' => 'This offer is not available today.');
    $currentTime = date('H:i:s');
    if (!empty($offer['daily_start_time']) && $currentTime < $offer['daily_start_time']) return array('valid' => false, 'message' => 'This offer is not available yet today.');
    if (!empty($offer['daily_end_time']) && $currentTime > $offer['daily_end_time']) return array('valid' => false, 'message' => 'This offer is no longer available today.');
    $targetNames = offerTargetCategoryNames((int) $offer['offer_id'], $conn);
    $targetFoodIds = array_flip(offerTargetFoodIds((int) $offer['offer_id'], $conn));
    if (!$targetNames && !empty($offer['applicable_food_category'])) $targetNames = array($offer['applicable_food_category']);
    $targetLookup = array();
    foreach ($targetNames as $name) $targetLookup[strtolower(trim($name))] = true;
    $eligibleSubtotal = 0.0;
    foreach ($items as $item) {
        $itemCategory = strtolower(trim((string) ($item['category'] ?? '')));
        $categoryMatches = !$targetLookup || isset($targetLookup[$itemCategory]);
        $itemMatches = !$targetFoodIds || isset($targetFoodIds[(int) ($item['id'] ?? 0)]);
        if ($categoryMatches && $itemMatches) $eligibleSubtotal += (float) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
    }
    if (($targetLookup || $targetFoodIds) && $eligibleSubtotal <= 0) return array('valid' => false, 'message' => 'Add an eligible menu item to use this offer.');
    if ($subtotal < (float) $offer['minimum_order']) return array('valid' => false, 'message' => 'Add Rs ' . number_format((float) $offer['minimum_order'] - $subtotal, 0) . ' to your cart to use this offer.');
    if ($eligibleSubtotal < (float) ($offer['minimum_eligible_subtotal'] ?? 0)) return array('valid' => false, 'message' => 'Add Rs ' . number_format((float) $offer['minimum_eligible_subtotal'] - $eligibleSubtotal, 0) . ' in eligible menu items to use this offer.');
    if ($offer['customer_scope'] === 'first_order' && !offerIsFirstOrderCustomer($conn, $email)) return array('valid' => false, 'message' => 'This offer is only for a first order.');
    if ($offer['total_usage_limit'] !== null && offerUsageCount($conn, (int) $offer['offer_id']) >= (int) $offer['total_usage_limit']) return array('valid' => false, 'message' => 'This offer has reached its redemption limit.');
    if ((int) $offer['per_customer_limit'] > 0 && offerUsageCount($conn, (int) $offer['offer_id'], $email) >= (int) $offer['per_customer_limit']) return array('valid' => false, 'message' => 'You have already used this offer.');
    if ($offer['daily_usage_limit'] !== null && offerUsageCount($conn, (int) $offer['offer_id'], null, true) >= (int) $offer['daily_usage_limit']) return array('valid' => false, 'message' => 'This offer has reached today\'s redemption limit.');
    $discount = 0.0;
    if ($offer['discount_type'] === 'percentage') {
        $discount = $eligibleSubtotal * ((float) $offer['discount_value'] / 100);
        if ($offer['maximum_discount'] !== null) $discount = min($discount, (float) $offer['maximum_discount']);
    } elseif ($offer['discount_type'] === 'fixed') {
        $discount = (float) $offer['discount_value'];
    }
    $discount = min(max(0, $discount), $eligibleSubtotal);
    return array('valid' => true, 'message' => '', 'discount' => round($discount, 2), 'eligible_subtotal' => $eligibleSubtotal);
}

function offerResolveCart($email, $items, $subtotal, $requestedCode = '')
{
    if (!ensureOfferTables()) return array('offer' => null, 'discount' => 0, 'message' => '');
    $conn = dbConnect();
    if (!$conn) return array('offer' => null, 'discount' => 0, 'message' => 'Offers are temporarily unavailable.');
    $requestedCode = strtoupper(trim($requestedCode));
    if ($requestedCode !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT o.*,c.name AS category_name,c.is_active AS offer_category_active FROM offers o JOIN offer_categories c ON c.offer_category_id=o.offer_category_id WHERE o.code=? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $requestedCode);
        mysqli_stmt_execute($stmt);
        $offer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$offer) return array('offer' => null, 'requested_offer' => null, 'discount' => 0, 'message' => 'That offer code was not found.');
        $check = offerEligibility($offer, $email, $items, $subtotal, $conn);
        return $check['valid']
            ? array('offer' => $offer, 'requested_offer' => $offer, 'discount' => $check['discount'], 'eligible_subtotal' => $check['eligible_subtotal'], 'message' => '')
            : array('offer' => null, 'requested_offer' => $offer, 'discount' => 0, 'eligible_subtotal' => 0, 'message' => $check['message']);
    }
    $result = mysqli_query($conn, "SELECT o.*,c.name AS category_name,c.is_active AS offer_category_active FROM offers o JOIN offer_categories c ON c.offer_category_id=o.offer_category_id WHERE o.is_automatic=1 AND o.is_active=1 AND c.is_active=1 AND o.starts_at<=NOW() AND o.ends_at>=NOW() ORDER BY o.priority DESC");
    $best = null; $bestDiscount = 0; $bestEligibleSubtotal = 0;
    while ($result && ($offer = mysqli_fetch_assoc($result))) {
        $check = offerEligibility($offer, $email, $items, $subtotal, $conn);
        if ($check['valid'] && ($best === null || (int) $offer['priority'] > (int) $best['priority'] || ((int) $offer['priority'] === (int) $best['priority'] && $check['discount'] > $bestDiscount))) { $best = $offer; $bestDiscount = $check['discount']; $bestEligibleSubtotal = $check['eligible_subtotal']; }
    }
    return array('offer' => $best, 'discount' => $bestDiscount, 'eligible_subtotal' => $bestEligibleSubtotal, 'message' => '');
}

function offerRecordRedemption($conn, $offer, $email, $orderId, $subtotal, $discount)
{
    $stmt = mysqli_prepare($conn, 'INSERT INTO offer_redemptions(offer_id,order_id,customer_email,offer_code,subtotal,discount_amount) VALUES(?,?,?,?,?,?)');
    if (!$stmt) return false;
    $offerId = (int) $offer['offer_id']; $code = $offer['code'] ?: null;
    mysqli_stmt_bind_param($stmt, 'iissdd', $offerId, $orderId, $email, $code, $subtotal, $discount);
    return mysqli_stmt_execute($stmt);
}
?>
