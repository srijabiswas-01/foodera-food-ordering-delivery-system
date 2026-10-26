<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/site-content.php';
$conn = adminDb();
$error = '';
$setup = false;
if (!$conn) {
    $error = 'Database connection unavailable. Check config.php.';
} else {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admins (user VARCHAR(50) NOT NULL, address VARCHAR(100) NOT NULL DEFAULT '', email VARCHAR(100) NOT NULL PRIMARY KEY, password VARCHAR(255) NOT NULL, phone VARCHAR(30) NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $column = mysqli_query($conn, "SHOW COLUMNS FROM admins LIKE 'password'");
    $field = $column ? mysqli_fetch_assoc($column) : null;
    if ($field && preg_match('/varchar\\((\\d+)\\)/i', $field['Type'], $match) && (int) $match[1] < 255) {
        if (!mysqli_query($conn, 'ALTER TABLE admins MODIFY password VARCHAR(255) NOT NULL')) $error = 'Run db/admin_auth_migration.sql before signing in.';
    }
    $result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM admins');
    if ($result) $setup = (int) mysqli_fetch_assoc($result)['total'] === 0;
    else $error = 'Admin table is unavailable.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!adminVerifyPost()) {
        $error = 'Form expired. Refresh the page.';
    } elseif ($setup && isset($_POST['register'])) {
        if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
            $error = 'Passwords do not match.';
        } else {
            $error = addNewAdmin($_POST);
            if (strpos($error, 'created') !== false) $setup = false;
        }
    } elseif (!$setup && isset($_POST['login'])) {
        $admin = loginadmin();
        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['foodera_admin'] = true;
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['user'];
            adminRedirect('home.php');
        }
        $error = 'Invalid user name or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $setup ? 'First Admin Setup' : 'Admin Sign In'; ?> | Foodera</title>
    <link rel="icon" href="../<?php echo adminEscape(siteImage('branding.favicon')); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css">
    <link rel="stylesheet" href="css/panel.css">
</head>
<body>
    <header class="admin-topbar">
        <div class="admin-topbar-inner">
            <a href="../home.php"><img src="../<?php echo adminEscape(siteImage('branding.logo')); ?>" alt="Foodera"></a>
        </div>
    </header>
    <main class="admin-content">
        <div class="admin-card admin-auth-card">
            <h1><?php echo $setup ? 'Create first admin' : 'Admin sign in'; ?></h1>
            <p class="admin-muted"><?php echo $setup ? 'Set up the first administrator for this database.' : 'Manage Foodera orders, menu, customers, and blog.'; ?></p>
            <?php if ($error !== '') { ?><p class="admin-notice" role="alert"><?php echo adminEscape($error); ?></p><?php } ?>
            <?php if ($conn) { ?>
                <form method="post" class="admin-form" id="admin-auth-form">
                    <input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>">
                    <label>User name<input name="user" maxlength="50" required autocomplete="username"><small class="field-error" aria-live="polite"></small></label>
                    <?php if ($setup) { ?><label>Email<input type="email" name="email" maxlength="50" required autocomplete="email"><small class="field-error" aria-live="polite"></small></label><?php } ?>
                    <label for="admin-password">Password</label>
                    <div class="admin-password-field">
                        <input id="admin-password" type="password" name="password" <?php echo $setup ? 'minlength="12" autocomplete="new-password"' : 'autocomplete="current-password"'; ?> required>
                        <button type="button" class="admin-password-toggle" aria-label="Show password" aria-controls="admin-password" aria-pressed="false" title="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    </div>
                    <small class="field-error" id="admin-password-error" aria-live="polite"></small>
                    <?php if ($setup) { ?>
                        <label for="admin-confirm-password">Confirm password</label>
                        <div class="admin-password-field">
                            <input id="admin-confirm-password" type="password" name="confirm_password" minlength="12" autocomplete="new-password" required aria-describedby="admin-confirm-error">
                            <button type="button" class="admin-password-toggle" aria-label="Show confirm password" aria-controls="admin-confirm-password" aria-pressed="false" title="Show confirm password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                        <small class="field-error" id="admin-confirm-error" aria-live="polite"><?php echo $error === 'Passwords do not match.' ? adminEscape($error) : ''; ?></small>
                    <?php } ?>
                    <button class="admin-btn" type="submit" name="<?php echo $setup ? 'register' : 'login'; ?>"><?php echo $setup ? 'Create admin account' : 'Sign in'; ?></button>
                </form>
            <?php } ?>
        </div>
    </main>
    <script>
        document.querySelectorAll('.admin-password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                const label = input.name === 'confirm_password' ? 'confirm password' : 'password';
                button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + label);
                button.setAttribute('title', (visible ? 'Hide ' : 'Show ') + label);
                button.setAttribute('aria-pressed', String(visible));
                button.querySelector('i').classList.toggle('bi-eye', !visible);
                button.querySelector('i').classList.toggle('bi-eye-slash', visible);
            });
        });
        const adminAuthForm = document.getElementById('admin-auth-form');
        function showFieldError(input) {
            const output = input.closest('label')?.querySelector('.field-error') || input.parentElement.nextElementSibling;
            if (output && output.classList.contains('field-error')) output.textContent = input.validity.valid ? '' : input.validationMessage;
        }
        if (adminAuthForm) adminAuthForm.querySelectorAll('input:not([type="hidden"])').forEach(function (input) {
            input.addEventListener('input', function () {
                if (input.name === 'confirm_password') input.setCustomValidity('');
                showFieldError(input);
            });
            input.addEventListener('invalid', function () { showFieldError(input); });
        });
        if (adminAuthForm) adminAuthForm.addEventListener('submit', function (event) {
            const password = document.getElementById('admin-password');
            const confirmation = document.getElementById('admin-confirm-password');
            if (confirmation && password.value !== confirmation.value) {
                event.preventDefault();
                confirmation.setCustomValidity('Passwords do not match.');
                showFieldError(confirmation);
                confirmation.reportValidity();
            }
        });
        const confirmation = document.getElementById('admin-confirm-password');
    </script>
</body>
</html>
