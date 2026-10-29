<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$notice = '';

if (isset($_GET['pending'])) {
    $notice = 'Account created. An admin must accept it before you can log in.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == 'pending') {
            $error = 'Your account is waiting for admin approval';
        } elseif ($user['status'] == 'rejected') {
            $error = 'Your account was rejected';
        } elseif ($user['status'] != 'active') {
            $error = 'Your account is suspended';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Wrong email or password';
    }
}

$page_title = 'Log in';
$main_class = 'auth-container';
require 'partials/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside">
        <span class="eyebrow">Welcome back</span>
        <h1>Your next favourite thing is waiting.</h1>
        <p>Sign in to keep browsing, pick up your cart where you left it, and check your past purchases.</p>
        <ul class="auth-points">
            <li>Save the products you like in one cart</li>
            <li>Check out in a few simple steps</li>
            <li>Keep a record of every purchase</li>
        </ul>
    </aside>

    <section class="auth-card">
        <h2>Log in</h2>
        <p class="auth-intro">Enter the email and password for your account.</p>

        <?php if ($notice): ?>
            <p class="success" role="status"><?php echo htmlspecialchars($notice); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <p class="auth-switch">New to My Shop? <a href="register.php">Create an account</a></p>
    </section>
</div>

<?php require 'partials/footer.php'; ?>
