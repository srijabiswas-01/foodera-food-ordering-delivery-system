<?php
include('app-functions.php');
session_start();
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

if(isset($_POST['login']))
{
    $response = login();
    $record = $response ? mysqli_num_rows($response) : 0;
    if($record > 0)
    {
        $data = mysqli_fetch_assoc($response);
        $_SESSION['email'] = $data['email'];
        $_SESSION['user'] = $data['user'];
        header('location:home.php');
        exit();
    }
    else {
        $login_error = 'invalid user name or password';
    }
}

if(isset($_POST['register']))
{
    if ($_POST['password'] !== ($_POST['confirm_password'] ?? '')) {
        $register_message = 'Password and confirm password do not match.';
    } else {
        $register_message = addNewCustomer($_POST);
    }
}
$showRegister = isset($_POST['register']) || isset($register_message);
$pageTitle = 'Login & Register';
$activePage = 'login';
$bodyClass = 'login-page';
$pageStyles = array('css/login.css', 'https://fonts.googleapis.com/css2?family=Kaushan+Script&display=swap');
include('includes/public-header.php');
?>
    <main class="auth-page">
        <div class="auth-shell" id="auth-shell">
            <section class="auth-panel">
                <div class="auth-tabs" role="tablist" aria-label="Account forms">
                    <button type="button" class="auth-tab <?php echo $showRegister ? '' : 'active'; ?>" data-auth-target="login">Login</button>
                    <button type="button" class="auth-tab <?php echo $showRegister ? 'active' : ''; ?>" data-auth-target="register">Register</button>
                </div>

                <div class="form-slider">
                    <form class="auth-form login-form <?php echo $showRegister ? '' : 'active-form'; ?>" action="" method="post" data-auth-form="login">
                        <p class="auth-eyebrow">Welcome back</p>
                        <h1 class="main-heading">Login to Foodera</h1>
                        <label>
                            <span>User name</span>
                            <input type="text" name="user" placeholder="Enter your user name" required>
                        </label>
                        <label>
                            <span>Password</span>
                            <div class="password-field">
                                <input type="password" name="password" placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" title="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </label>
                        <button type="submit" name="login">Login</button>
                        <?php if(isset($login_error)) { echo "<p class='form-alert error'>".$login_error."</p>"; } ?>
                        <p class="message">New to Foodera? <a href="#" data-auth-target="register">Create an account</a></p>
                    </form>

                    <form class="auth-form register-form <?php echo $showRegister ? 'active-form' : ''; ?>" action="" method="POST" data-auth-form="register">
                        <p class="auth-eyebrow">Start ordering</p>
                        <h1 class="main-heading">Create Account</h1>
                        <label>
                            <span>User name</span>
                            <input type="text" name="user" placeholder="Choose a user name" required>
                        </label>
                        <label>
                            <span>Address</span>
                            <input type="text" name="address" placeholder="Delivery address" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </label>
                        <label>
                            <span>Password</span>
                            <div class="password-field">
                                <input type="password" name="password" id="register-password" placeholder="Create a password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" title="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <div class="password-field">
                                <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" title="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </label>
                        <label>
                            <span>Mobile number</span>
                            <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="10 digit mobile number" required>
                        </label>
                        <button type="submit" name="register">Register</button>
                        <?php if(isset($register_message)) { echo "<p class='form-alert'>".$register_message."</p>"; } ?>
                        <p class="message">Already registered? <a href="#" data-auth-target="login">Login here</a></p>
                    </form>
                </div>
            </section>

            <aside class="auth-copy">
                <p class="auth-kicker">Foodera</p>
                <h2>Order faster with one clean account.</h2>
                <p>Save your details, manage your cart, complete checkout, and keep your food ordering flow organized.</p>
                <div class="auth-benefits">
                    <span><i class="fas fa-utensils"></i> Browse menu first</span>
                    <span><i class="fas fa-shopping-cart"></i> Save cart items</span>
                    <span><i class="fas fa-receipt"></i> Track bill details</span>
                </div>
            </aside>
        </div>
    </main>
   
    <script>
        function showAuthForm(target) {
            document.querySelectorAll('[data-auth-form]').forEach(function (form) {
                form.classList.toggle('active-form', form.getAttribute('data-auth-form') === target);
            });
            document.querySelectorAll('.auth-tab').forEach(function (tab) {
                tab.classList.toggle('active', tab.getAttribute('data-auth-target') === target);
            });
        }

        document.querySelectorAll('[data-auth-target]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                showAuthForm(trigger.getAttribute('data-auth-target'));
            });
        });

        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = button.parentElement.querySelector('input');
                const icon = button.querySelector('i');
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                button.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', String(isPassword));
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });

        const registerForm = document.querySelector('.register-form');
        if (registerForm) {
            const confirmPassword = document.getElementById('confirm-password');
            if (confirmPassword) confirmPassword.addEventListener('input', function () {
                confirmPassword.setCustomValidity('');
            });
            registerForm.addEventListener('submit', function (event) {
                const password = document.getElementById('register-password');
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    event.preventDefault();
                    confirmPassword.setCustomValidity('Passwords do not match.');
                    confirmPassword.reportValidity();
                } else if (confirmPassword) {
                    confirmPassword.setCustomValidity('');
                }
            });
        }

        <?php if(isset($_POST['register']) || isset($register_message)) { ?>
        showAuthForm('register');
        <?php } ?>
    </script>
<?php include('includes/public-footer.php'); ?>
