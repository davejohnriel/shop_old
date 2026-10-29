<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($name == '' || $email == '' || $password == '') {
        $error = 'Fill all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password != $confirm) {
        $error = 'Passwords do not match';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email already used';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'customer', 'pending')")
                ->execute([$name, $email, $hash]);
            header('Location: login.php?pending=1');
            exit;
        }
    }
}

$page_title = 'Create account';
$main_class = 'auth-container';
require 'partials/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside">
        <span class="eyebrow">Join the shop</span>
        <h1>Good finds, saved for later.</h1>
        <p>Create an account to build a cart, check out quickly, and keep all of your purchases in one place.</p>
        <ul class="auth-points">
            <li>One account for all of your orders</li>
            <li>A cart that waits for you</li>
            <li>No fuss, no clutter</li>
        </ul>
    </aside>

    <section class="auth-card">
        <h2>Create your account</h2>
        <p class="auth-intro">A few details and you are ready to shop.</p>

        <?php if ($error): ?>
            <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" autocomplete="name" placeholder="Your name" required autofocus>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="6" required>
                <p class="field-help">Use at least 6 characters.</p>
            </div>
            <div class="field">
                <label for="confirm">Confirm password</label>
                <input type="password" id="confirm" name="confirm" autocomplete="new-password" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
        <p class="form-note">New accounts are reviewed by an admin before the first login.</p>
    </section>
</div>

<?php require 'partials/footer.php'; ?>
