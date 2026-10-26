<?php
require_once __DIR__ . '/includes/bootstrap.php';
adminRequireLogin();
$conn = adminDb();
$email = $_SESSION['admin_email'];
$admin = null;
if ($conn) {
    $stmt = mysqli_prepare($conn, 'SELECT user,email,address,phone,password FROM admins WHERE email=? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
if (!$admin) adminRedirect('logout.php');
$errors = array();
$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyPost()) $errors['form'] = 'Request expired. Refresh and try again.';
    elseif ($action === 'profile') {
        $name = trim($_POST['user'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || strlen($name) > 50) $errors['user'] = 'Enter a user name under 50 characters.';
        if (strlen($address) > 50) $errors['address'] = 'Address must be under 50 characters.';
        if ($phone !== '' && !preg_match('/^[0-9+() -]{7,30}$/', $phone)) $errors['phone'] = 'Enter a valid phone number.';
        if (!$errors) {
            $check = mysqli_prepare($conn, 'SELECT email FROM admins WHERE user=? AND email<>? LIMIT 1');
            mysqli_stmt_bind_param($check, 'ss', $name, $email);
            mysqli_stmt_execute($check);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) $errors['user'] = 'This user name is already used.';
            elseif (runQuery('UPDATE admins SET user=?,address=?,phone=? WHERE email=?', 'ssss', $name, $address, $phone, $email)) {
                $_SESSION['admin_name'] = $name;
                adminRedirect('admin-profile.php', 'Account details updated.');
            } else $errors['form'] = 'Could not update account details.';
        }
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (!password_verify($current, $admin['password']) && !hash_equals($admin['password'], $current)) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new) < 12) $errors['new_password'] = 'Use at least 12 characters.';
        if ($new !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
        if (!$errors) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            if (runQuery('UPDATE admins SET password=? WHERE email=?', 'ss', $hash, $email)) adminRedirect('admin-profile.php', 'Password changed.');
            $errors['form'] = 'Could not change password.';
        }
    } elseif ($action === 'new_admin') {
        $name = trim($_POST['new_user'] ?? '');
        $newEmail = trim($_POST['new_email'] ?? '');
        $newPassword = (string) ($_POST['new_admin_password'] ?? '');
        $confirm = (string) ($_POST['new_admin_confirm'] ?? '');
        $current = (string) ($_POST['admin_current_password'] ?? '');
        if ($name === '' || strlen($name) > 50) $errors['new_user'] = 'Enter a user name under 50 characters.';
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 50) $errors['new_email'] = 'Enter a valid email under 50 characters.';
        if (strlen($newPassword) < 12) $errors['new_admin_password'] = 'Use at least 12 characters.';
        if ($newPassword !== $confirm) $errors['new_admin_confirm'] = 'Passwords do not match.';
        if (!password_verify($current, $admin['password']) && !hash_equals($admin['password'], $current)) $errors['admin_current_password'] = 'Your password is incorrect.';
        if (!$errors) {
            $check = mysqli_prepare($conn, 'SELECT email FROM admins WHERE email=? OR user=? LIMIT 1');
            mysqli_stmt_bind_param($check, 'ss', $newEmail, $name);
            mysqli_stmt_execute($check);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) $errors['new_email'] = 'Email or user name is already in use.';
            else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $empty = '';
                if (runQuery('INSERT INTO admins (user,address,email,password,phone) VALUES (?, ?, ?, ?, ?)', 'sssss', $name, $empty, $newEmail, $hash, $empty)) adminRedirect('admin-profile.php', 'Admin account created.');
                $errors['form'] = 'Could not add the admin account.';
            }
        }
    }
}
$admins = adminRows('SELECT user,email FROM admins ORDER BY user');
$adminTitle = 'Account';
$adminPage = 'profile';
include __DIR__ . '/includes/header.php';
?>
<?php if (isset($errors['form'])) { ?><p class="admin-notice" role="alert"><?php echo adminEscape($errors['form']); ?></p><?php } ?>
<div class="admin-account-grid">
    <section class="admin-card">
        <h2>Account details</h2>
        <form method="post" class="admin-form" data-inline-validate>
            <input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="action" value="profile">
            <label>User name<input name="user" maxlength="50" value="<?php echo adminEscape($action === 'profile' ? ($_POST['user'] ?? '') : $admin['user']); ?>" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['user'] ?? ''); ?></small></label>
            <label>Email<input value="<?php echo adminEscape($admin['email']); ?>" disabled></label>
            <label>Address<input name="address" maxlength="50" value="<?php echo adminEscape($action === 'profile' ? ($_POST['address'] ?? '') : $admin['address']); ?>"><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['address'] ?? ''); ?></small></label>
            <label>Phone<input name="phone" maxlength="30" pattern="[0-9+() -]{7,30}" value="<?php echo adminEscape($action === 'profile' ? ($_POST['phone'] ?? '') : $admin['phone']); ?>"><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['phone'] ?? ''); ?></small></label>
            <button class="admin-btn" type="submit">Save details</button>
        </form>
    </section>
    <section class="admin-card">
        <h2>Change password</h2>
        <form method="post" class="admin-form" data-inline-validate>
            <input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="action" value="password">
            <label>Current password<input type="password" name="current_password" autocomplete="current-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['current_password'] ?? ''); ?></small></label>
            <label>New password<input type="password" name="new_password" minlength="12" autocomplete="new-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['new_password'] ?? ''); ?></small></label>
            <label>Confirm new password<input type="password" name="confirm_password" data-match="new_password" autocomplete="new-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['confirm_password'] ?? ''); ?></small></label>
            <button class="admin-btn" type="submit">Change password</button>
        </form>
    </section>
</div>
<section class="admin-card">
    <h2>Add another admin</h2>
    <form method="post" class="admin-form" data-inline-validate>
        <input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="action" value="new_admin">
        <label>User name<input name="new_user" maxlength="50" value="<?php echo adminEscape($action === 'new_admin' ? ($_POST['new_user'] ?? '') : ''); ?>" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['new_user'] ?? ''); ?></small></label>
        <label>Email<input type="email" name="new_email" maxlength="50" value="<?php echo adminEscape($action === 'new_admin' ? ($_POST['new_email'] ?? '') : ''); ?>" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['new_email'] ?? ''); ?></small></label>
        <label>Password<input type="password" name="new_admin_password" minlength="12" autocomplete="new-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['new_admin_password'] ?? ''); ?></small></label>
        <label>Confirm password<input type="password" name="new_admin_confirm" data-match="new_admin_password" autocomplete="new-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['new_admin_confirm'] ?? ''); ?></small></label>
        <label>Your current password<input type="password" name="admin_current_password" autocomplete="current-password" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['admin_current_password'] ?? ''); ?></small></label>
        <button class="admin-btn" type="submit">Add admin</button>
    </form>
</section>
<section class="admin-card"><h2>Administrators</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>User name</th><th>Email</th></tr></thead><tbody><?php foreach ($admins as $member) { ?><tr><td><?php echo adminEscape($member['user']); ?></td><td><?php echo adminEscape($member['email']); ?></td></tr><?php } ?></tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
