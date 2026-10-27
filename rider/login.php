<?php
require_once __DIR__ . '/includes/bootstrap.php';
ensureMisTables();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals(riderToken(), $_POST['csrf'] ?? '')) {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $conn = dbConnect();
    $stmt = mysqli_prepare($conn, 'SELECT rider_id,name,email,password_hash,is_active FROM delivery_riders WHERE email=? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $rider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($rider && $rider['is_active'] && $rider['password_hash'] && password_verify($password, $rider['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['foodera_rider'] = true;
        $_SESSION['rider_id'] = (int) $rider['rider_id'];
        $_SESSION['rider_name'] = $rider['name'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid rider email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#ff4b1f">
    <title>Rider sign in | Foodera</title>
    <link rel="icon" type="image/png" href="../assets/branding/foodera-icon.png">
    <link rel="apple-touch-icon" href="../assets/branding/foodera-icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/rider.css?v=<?php echo (int) @filemtime(__DIR__ . '/css/rider.css'); ?>">
</head>
<body>
    <main class="rider-auth">
        <section>
            <div class="rider-brand"><img src="../assets/branding/foodera-wordmark.png" alt="Foodera"></div>
            <p class="rider-kicker">Foodera delivery</p>
            <h1>Rider sign in</h1>
            <p>Use the credentials issued by the Foodera admin.</p>
            <?php if ($error) { ?><p class="rider-alert"><?php echo riderEscape($error); ?></p><?php } ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo riderEscape(riderToken()); ?>">
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
                <button>Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
            <a href="../home.php">Back to Foodera</a>
        </section>
    </main>
</body>
</html>
