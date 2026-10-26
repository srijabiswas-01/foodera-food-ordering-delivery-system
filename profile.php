<?php
require_once __DIR__ . '/app-functions.php';
session_start();
if (empty($_SESSION['email'])) { header('Location: login.php'); exit; }
ensureMisTables();
$email = $_SESSION['email'];
$response = getAllCustomer($email);
$customer = $response ? mysqli_fetch_assoc($response) : null;
if (!$customer) { header('Location: logout.php'); exit; }
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
function accountEscape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function accountRedirect($message) { $_SESSION['profile_notice'] = $message; header('Location: profile.php'); exit; }
function accountQuery($conn, $sql, $types = '', ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    return mysqli_stmt_execute($stmt) ? $stmt : false;
}
$zoneRows = array();
$zoneConn = dbConnect();
$zoneStatement = $zoneConn ? mysqli_prepare($zoneConn, 'SELECT zone_id,city,zone_name FROM delivery_zones WHERE is_active=1 ORDER BY city,zone_name') : false;
if ($zoneStatement && mysqli_stmt_execute($zoneStatement)) {
    $zoneResult = mysqli_stmt_get_result($zoneStatement);
    if ($zoneResult) $zoneRows = mysqli_fetch_all($zoneResult, MYSQLI_ASSOC);
}
function accountZone($zoneRows, $zoneId) { foreach ($zoneRows as $zone) if ((int)$zone['zone_id'] === (int)$zoneId) return $zone; return null; }
$errors = array();
$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['profile_csrf'], $_POST['csrf'] ?? '')) $errors['form'] = 'Request expired. Refresh and try again.';
    elseif ($action === 'details') {
        $name = trim($_POST['user'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || strlen($name) > 50) $errors['user'] = 'Enter a name under 50 characters.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors['phone'] = 'Enter a 10-digit phone number.';
        if (!$errors) {
            if (runQuery('UPDATE customers SET user=?,phone=? WHERE email=?', 'sss', $name, $phone, $email)) {
                $_SESSION['user'] = $name;
                accountRedirect('Details updated.');
            }
            $errors['form'] = 'Could not update your details.';
        }
    } elseif ($action === 'preferences') {
        $diet = trim($_POST['dietary_preference'] ?? 'none');
        $cuisines = trim($_POST['favorite_cuisines'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        if (!in_array($diet, array('none','vegetarian','vegan','halal','other'), true)) $errors['dietary_preference'] = 'Choose a valid preference.';
        if (strlen($cuisines) > 255) $errors['favorite_cuisines'] = 'Keep cuisines under 255 characters.';
        if (strlen($allergies) > 255) $errors['allergies'] = 'Keep allergies under 255 characters.';
        if (!$errors) {
            $sql = 'INSERT INTO customer_profiles (customer_email,dietary_preference,favorite_cuisines,allergies) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE dietary_preference=VALUES(dietary_preference),favorite_cuisines=VALUES(favorite_cuisines),allergies=VALUES(allergies)';
            if (runQuery($sql, 'ssss', $email, $diet, $cuisines, $allergies)) accountRedirect('Food preferences saved.');
            $errors['form'] = 'Could not save preferences. Run the customer profile migration if needed.';
        }
    } elseif ($action === 'photo') {
        $file = $_FILES['photo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3000000) $errors['photo'] = 'Choose an image under 3 MB.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');
            if (!isset($types[$mime])) $errors['photo'] = 'Use a JPG, PNG, or WebP image.';
            else {
                $dir = __DIR__ . '/uploads/profiles';
                if (!is_dir($dir) && !mkdir($dir, 0775, true)) $errors['photo'] = 'Could not prepare photo storage.';
                else {
                    $path = 'uploads/profiles/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
                    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $path)) $errors['photo'] = 'Photo upload failed.';
                    else {
                        $old = getCustomerProfile($email);
                        if (runQuery('INSERT INTO customer_profiles (customer_email,photo_path) VALUES (?,?) ON DUPLICATE KEY UPDATE photo_path=VALUES(photo_path)', 'ss', $email, $path)) {
                            if (!empty($old['photo_path']) && strpos($old['photo_path'], 'uploads/profiles/') === 0) @unlink(__DIR__ . '/' . $old['photo_path']);
                            accountRedirect('Profile photo updated.');
                        }
                        @unlink(__DIR__ . '/' . $path);
                        $errors['photo'] = 'Could not save your photo. Run the customer profile migration if needed.';
                    }
                }
            }
        }
    } elseif ($action === 'remove_photo') {
        $old = getCustomerProfile($email);
        if (runQuery('UPDATE customer_profiles SET photo_path=NULL WHERE customer_email=?', 's', $email)) {
            if (!empty($old['photo_path']) && strpos($old['photo_path'], 'uploads/profiles/') === 0) @unlink(__DIR__ . '/' . $old['photo_path']);
            accountRedirect('Profile photo removed.');
        }
        $errors['form'] = 'Could not remove photo.';
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify($current, $customer['password']) && !hash_equals($customer['password'], $current)) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors['new_password'] = 'Use at least 8 characters.';
        if ($new !== ($_POST['confirm_password'] ?? '')) $errors['confirm_password'] = 'Passwords do not match.';
        if (!$errors) {
            if (runQuery('UPDATE customers SET password=? WHERE email=?', 'ss', password_hash($new, PASSWORD_DEFAULT), $email)) accountRedirect('Password changed.');
            $errors['form'] = 'Could not change password.';
        }
    } elseif ($action === 'add_address') {
        $label = trim($_POST['label'] ?? '');
        $line = trim($_POST['address_line'] ?? '');
        $zone = accountZone($zoneRows, (int)($_POST['zone_id'] ?? 0));
        if ($label === '' || strlen($label) > 50) $errors['label'] = 'Enter a label under 50 characters.';
        if ($line === '' || strlen($line) > 255) $errors['address_line'] = 'Enter an address under 255 characters.';
        if ($zoneRows && !$zone) $errors['zone_id'] = 'Choose a delivery city and zone.';
        if (!$errors) {
            $existing = getCustomerAddresses($email);
            $default = $existing ? 0 : 1;
            $conn = dbConnect();
            if ($conn && mysqli_begin_transaction($conn)) {
                $city = $zone['city'] ?? ''; $deliveryZone = $zone['zone_name'] ?? '';
                $ok = accountQuery($conn, 'INSERT INTO customer_addresses (customer_email,label,address_line,city,delivery_zone,is_default) VALUES (?,?,?,?,?,?)', 'sssssi', $email, $label, $line, $city, $deliveryZone, $default);
                if ($ok && $default) $ok = accountQuery($conn, 'UPDATE customers SET address=? WHERE email=?', 'ss', $line, $email);
                if ($ok) { mysqli_commit($conn); accountRedirect('Address added.'); }
                mysqli_rollback($conn);
            }
            $errors['form'] = 'Could not add address. Run the customer profile migration if needed.';
        }
    } elseif (in_array($action, array('save_address','default_address','delete_address'), true)) {
        $id = max(0, (int) ($_POST['address_id'] ?? 0));
        $conn = dbConnect();
        $stmt = $conn ? mysqli_prepare($conn, 'SELECT address_id,is_default FROM customer_addresses WHERE address_id=? AND customer_email=?') : false;
        if ($stmt) { mysqli_stmt_bind_param($stmt, 'is', $id, $email); mysqli_stmt_execute($stmt); $owned = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); }
        if (empty($owned)) $errors['form'] = 'Address not found.';
        elseif ($action === 'save_address') {
            $label = trim($_POST['label'] ?? '');
            $line = trim($_POST['address_line'] ?? '');
            $zone = accountZone($zoneRows, (int)($_POST['zone_id'] ?? 0));
            if ($label === '' || strlen($label) > 50) $errors['form'] = 'Enter a label under 50 characters.';
            elseif ($line === '' || strlen($line) > 255) $errors['form'] = 'Enter an address under 255 characters.';
            elseif ($zoneRows && !$zone) $errors['form'] = 'Choose a delivery city and zone.';
            else {
                mysqli_begin_transaction($conn);
                $city = $zone['city'] ?? ''; $deliveryZone = $zone['zone_name'] ?? '';
                $ok = accountQuery($conn, 'UPDATE customer_addresses SET label=?,address_line=?,city=?,delivery_zone=? WHERE address_id=? AND customer_email=?', 'ssssis', $label, $line, $city, $deliveryZone, $id, $email);
                if ($ok && $owned['is_default']) $ok = accountQuery($conn, 'UPDATE customers SET address=? WHERE email=?', 'ss', $line, $email);
                if ($ok) { mysqli_commit($conn); accountRedirect('Address updated.'); }
                mysqli_rollback($conn);
                $errors['form'] = 'Could not update address.';
            }
        } else {
            mysqli_begin_transaction($conn);
            if ($action === 'default_address') {
                $ok = accountQuery($conn, 'UPDATE customer_addresses SET is_default=0 WHERE customer_email=?', 's', $email);
                $ok = $ok && accountQuery($conn, 'UPDATE customer_addresses SET is_default=1 WHERE address_id=? AND customer_email=?', 'is', $id, $email);
            } else $ok = accountQuery($conn, 'DELETE FROM customer_addresses WHERE address_id=? AND customer_email=?', 'is', $id, $email);
            if ($ok) {
                $remainingStmt = accountQuery($conn, 'SELECT address_id,address_line,is_default FROM customer_addresses WHERE customer_email=? ORDER BY is_default DESC,address_id DESC', 's', $email);
                $remaining = $remainingStmt ? mysqli_fetch_all(mysqli_stmt_get_result($remainingStmt), MYSQLI_ASSOC) : array();
                $ok = (bool) $remainingStmt;
                $chosen = null;
                foreach ($remaining as $candidate) if ($candidate['is_default']) { $chosen = $candidate; break; }
                if (!$chosen && $remaining) {
                    $chosen = $remaining[0];
                    $ok = accountQuery($conn, 'UPDATE customer_addresses SET is_default=1 WHERE address_id=? AND customer_email=?', 'is', $chosen['address_id'], $email);
                }
                if ($ok) $ok = accountQuery($conn, 'UPDATE customers SET address=? WHERE email=?', 'ss', $chosen['address_line'] ?? '', $email);
            }
            if ($ok) { mysqli_commit($conn); accountRedirect($action === 'default_address' ? 'Default address changed.' : 'Address deleted.'); }
            mysqli_rollback($conn);
            $errors['form'] = 'Could not update address.';
        }
    }
}
$customerResult = getAllCustomer($email);
$customer = $customerResult ? mysqli_fetch_assoc($customerResult) : $customer;
$profile = getCustomerProfile($email) ?: array('photo_path'=>null,'dietary_preference'=>'none','favorite_cuisines'=>'','allergies'=>'');
$addresses = getCustomerAddresses($email);
$photoPath = (string) ($profile['photo_path'] ?? '');
$photoExists = preg_match('~^uploads/profiles/[a-f0-9]{32}\.(jpg|png|webp)$~', $photoPath) && is_file(__DIR__ . '/' . $photoPath);
$nameParts = preg_split('/\s+/u', trim($customer['user']));
$initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
$notice = $_SESSION['profile_notice'] ?? '';
unset($_SESSION['profile_notice']);
$pageTitle = 'My Profile'; $activePage = 'profile'; $bodyClass = 'profile-page';
$pageStyles = array('css/account.css');
$pageScripts = array('js/account.js');
include __DIR__ . '/includes/public-header.php';
?>
<main class="account-main">
    <header class="account-heading">
        <div class="account-avatar" id="profile-preview" aria-label="Profile photo">
            <span><?php echo accountEscape($initials); ?></span>
            <?php if ($photoExists) { ?><img src="<?php echo accountEscape($photoPath); ?>" alt="" onerror="this.remove()" data-saved-photo><?php } ?>
        </div>
        <div class="account-heading-copy"><p>My account</p><h1><?php echo accountEscape($customer['user']); ?></h1><span><?php echo accountEscape($email); ?></span></div>
        <a class="account-menu-link" href="menu.php"><i class="bi bi-bag" aria-hidden="true"></i> Browse menu</a>
    </header>
    <?php if ($notice !== '') { ?><p class="account-alert" role="status"><?php echo accountEscape($notice); ?></p><?php } ?>
    <?php if (isset($errors['form'])) { ?><p class="account-alert" role="alert"><?php echo accountEscape($errors['form']); ?></p><?php } ?>

    <div class="account-columns">
        <section class="account-section" id="personal-details">
            <div class="account-section-heading"><div><p>Account</p><h2>Personal details</h2></div><i class="bi bi-person" aria-hidden="true"></i></div>
            <div class="account-photo-row">
                <form method="post" enctype="multipart/form-data" class="account-form account-photo-form">
                    <input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>">
                    <input type="hidden" name="action" value="photo">
                    <label>Profile photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="profile-preview" required><small class="field-error"><?php echo accountEscape($errors['photo'] ?? ''); ?></small></label>
                    <button type="submit"><i class="bi bi-camera" aria-hidden="true"></i> Upload photo</button>
                </form>
                <?php if (!empty($profile['photo_path'])) { ?><form method="post"><input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="remove_photo"><button type="submit" class="text-button">Remove photo</button></form><?php } ?>
            </div>
            <form method="post" class="account-form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="details">
                <label>Name<input name="user" maxlength="50" value="<?php echo accountEscape($action === 'details' ? ($_POST['user'] ?? '') : $customer['user']); ?>" required><small class="field-error"><?php echo accountEscape($errors['user'] ?? ''); ?></small></label>
                <label>Email<input value="<?php echo accountEscape($email); ?>" disabled></label>
                <label>Phone<input name="phone" inputmode="numeric" pattern="[0-9]{10}" value="<?php echo accountEscape($action === 'details' ? ($_POST['phone'] ?? '') : $customer['phone']); ?>" required><small class="field-error"><?php echo accountEscape($errors['phone'] ?? ''); ?></small></label>
                <button type="submit">Save details</button>
            </form>
        </section>
        <section class="account-section" id="food-preferences">
            <div class="account-section-heading"><div><p>Food</p><h2>Preferences</h2></div><i class="bi bi-heart" aria-hidden="true"></i></div>
            <form method="post" class="account-form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="preferences">
                <label>Dietary preference<select name="dietary_preference"><?php foreach (array('none'=>'No preference','vegetarian'=>'Vegetarian','vegan'=>'Vegan','halal'=>'Halal','other'=>'Other') as $value=>$label) { ?><option value="<?php echo $value; ?>" <?php echo ($action === 'preferences' ? ($_POST['dietary_preference'] ?? '') : $profile['dietary_preference']) === $value ? 'selected' : ''; ?>><?php echo $label; ?></option><?php } ?></select><small class="field-error"><?php echo accountEscape($errors['dietary_preference'] ?? ''); ?></small></label>
                <label>Favorite cuisines<input name="favorite_cuisines" maxlength="255" value="<?php echo accountEscape($action === 'preferences' ? ($_POST['favorite_cuisines'] ?? '') : $profile['favorite_cuisines']); ?>" placeholder="e.g. Indian, Chinese"><small class="field-error"><?php echo accountEscape($errors['favorite_cuisines'] ?? ''); ?></small></label>
                <label>Allergies or foods to avoid<textarea name="allergies" maxlength="255" rows="3" placeholder="Optional"><?php echo accountEscape($action === 'preferences' ? ($_POST['allergies'] ?? '') : $profile['allergies']); ?></textarea><small class="field-error"><?php echo accountEscape($errors['allergies'] ?? ''); ?></small></label>
                <button type="submit">Save preferences</button>
            </form>
        </section>
    </div>

    <div class="account-lower">
        <section class="account-section account-addresses" id="saved-addresses">
            <div class="account-section-heading"><div><p>Delivery</p><h2>Saved addresses</h2></div><span><?php echo count($addresses); ?> saved</span></div>
            <div class="address-grid">
                <?php foreach ($addresses as $address) { ?><article class="address-item"><form method="post" class="account-form" data-validate>
                    <input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="address_id" value="<?php echo (int) $address['address_id']; ?>">
                    <label>Label<input name="label" maxlength="50" value="<?php echo accountEscape($address['label']); ?>" required></label>
                    <label>Address<textarea name="address_line" maxlength="255" rows="3" required><?php echo accountEscape($address['address_line']); ?></textarea></label><?php if ($zoneRows) { ?><label>Delivery city and zone<select name="zone_id" required><option value="">Choose city and zone</option><?php foreach ($zoneRows as $zone) { $selected = ($address['city'] ?? '') === $zone['city'] && ($address['delivery_zone'] ?? '') === $zone['zone_name']; ?><option value="<?php echo (int)$zone['zone_id']; ?>"<?php echo $selected ? ' selected' : ''; ?>><?php echo accountEscape($zone['city'] . ' — ' . $zone['zone_name']); ?></option><?php } ?></select></label><?php } ?><div class="account-location-tools"><button type="button" class="secondary-button" data-use-location><i class="bi bi-crosshair" aria-hidden="true"></i> Use current location</button><p data-location-status aria-live="polite">We only use your location when you select this button.</p></div>
                    <div class="account-actions"><button name="action" value="save_address">Save</button><?php if (!$address['is_default']) { ?><button name="action" value="default_address" formnovalidate class="secondary-button">Set default</button><?php } else { ?><span class="default-label"><i class="bi bi-check-circle" aria-hidden="true"></i> Default</span><?php } ?><button name="action" value="delete_address" formnovalidate class="text-button" onclick="return confirm('Delete this address?')">Delete</button></div>
                </form></article><?php } ?>
            </div>
            <form method="post" class="account-form account-add-address" data-validate>
                <h3>Add another address</h3><input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="add_address">
                <label>Label<input name="label" maxlength="50" placeholder="Home, Work, etc." value="<?php echo accountEscape($action === 'add_address' ? ($_POST['label'] ?? '') : ''); ?>" required><small class="field-error"><?php echo accountEscape($errors['label'] ?? ''); ?></small></label>
                <label>Address<textarea name="address_line" maxlength="255" rows="3" required><?php echo accountEscape($action === 'add_address' ? ($_POST['address_line'] ?? '') : ''); ?></textarea><small class="field-error"><?php echo accountEscape($errors['address_line'] ?? ''); ?></small></label><?php if ($zoneRows) { ?><label>Delivery city and zone<select name="zone_id" required><option value="">Choose city and zone</option><?php foreach ($zoneRows as $zone) { ?><option value="<?php echo (int)$zone['zone_id']; ?>"<?php echo (int)($_POST['zone_id'] ?? 0) === (int)$zone['zone_id'] ? ' selected' : ''; ?>><?php echo accountEscape($zone['city'] . ' — ' . $zone['zone_name']); ?></option><?php } ?></select><small class="field-error"><?php echo accountEscape($errors['zone_id'] ?? ''); ?></small></label><?php } ?><div class="account-location-tools"><button type="button" class="secondary-button" data-use-location><i class="bi bi-crosshair" aria-hidden="true"></i> Use current location</button><p data-location-status aria-live="polite">We only use your location when you select this button.</p></div>
                <button type="submit"><i class="bi bi-save" aria-hidden="true"></i> Save address</button>
            </form>
        </section>
        <section class="account-section account-password" id="security">
            <div class="account-section-heading"><div><p>Security</p><h2>Change password</h2></div><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
            <form method="post" class="account-form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo accountEscape($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="password">
                <label>Current password<input type="password" name="current_password" autocomplete="current-password" required><small class="field-error"><?php echo accountEscape($errors['current_password'] ?? ''); ?></small></label>
                <label>New password<input type="password" name="new_password" minlength="8" autocomplete="new-password" required><small class="field-error"><?php echo accountEscape($errors['new_password'] ?? ''); ?></small></label>
                <label>Confirm new password<input type="password" name="confirm_password" data-match="new_password" autocomplete="new-password" required><small class="field-error"><?php echo accountEscape($errors['confirm_password'] ?? ''); ?></small></label>
                <button type="submit">Change password</button>
            </form>
        </section>
    </div>
</main>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
