<?php
$page_title = $page_title ?? 'Shop';
$active = $active ?? '';
$main_class = $main_class ?? 'page';

$header_role = current_role();
$header_logged = is_logged_in();
$header_owner = is_owner();
$header_admin = is_admin();
$header_customer = is_customer();
$header_initial = $header_logged ? strtoupper(substr($_SESSION['name'] ?? '?', 0, 1)) : '';

$cart_count = 0;
if ($header_customer) {
    $cart_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0)
        FROM cart_items ci
        JOIN carts c ON c.id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_count = (int)$cart_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b5d4b">
    <title><?php echo htmlspecialchars($page_title); ?> &middot; My Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php" aria-label="My Shop home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8h12l-1 12H7L6 8Z"></path>
                    <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
                </svg>
            </span>
            <span class="brand-copy">
                <strong>My Shop</strong>
                <small>Curated everyday goods</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a class="nav-link <?php echo $active === 'shop' ? 'is-active' : ''; ?>" href="index.php"<?php echo $active === 'shop' ? ' aria-current="page"' : ''; ?>>Shop</a>

            <?php if ($header_owner): ?>
                <a class="nav-link <?php echo $active === 'products' ? 'is-active' : ''; ?>" href="add_product.php"<?php echo $active === 'products' ? ' aria-current="page"' : ''; ?>>Add product</a>
                <a class="nav-link <?php echo $active === 'orders' ? 'is-active' : ''; ?>" href="orders.php"<?php echo $active === 'orders' ? ' aria-current="page"' : ''; ?>>Purchases</a>
                <a class="nav-link <?php echo $active === 'accounts' ? 'is-active' : ''; ?>" href="accounts.php"<?php echo $active === 'accounts' ? ' aria-current="page"' : ''; ?>>Accounts</a>
                <a class="nav-link <?php echo $active === 'owner' ? 'is-active' : ''; ?>" href="owner.php"<?php echo $active === 'owner' ? ' aria-current="page"' : ''; ?>>Owner panel</a>
            <?php elseif ($header_admin): ?>
                <a class="nav-link <?php echo $active === 'accounts' ? 'is-active' : ''; ?>" href="accounts.php"<?php echo $active === 'accounts' ? ' aria-current="page"' : ''; ?>>Accounts</a>
            <?php elseif ($header_customer): ?>
                <a class="nav-link <?php echo $active === 'cart' ? 'is-active' : ''; ?>" href="cart.php"<?php echo $active === 'cart' ? ' aria-current="page"' : ''; ?>>
                    Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="nav-count" aria-label="<?php echo $cart_count; ?> items in cart"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?php echo $active === 'orders' ? 'is-active' : ''; ?>" href="orders.php"<?php echo $active === 'orders' ? ' aria-current="page"' : ''; ?>>My purchases</a>
            <?php endif; ?>
        </nav>

        <div class="user-menu">
            <?php if ($header_logged): ?>
                <span class="user-chip">
                    <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars($header_initial); ?></span>
                    <span class="user-meta">
                        <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                        <small><?php echo htmlspecialchars($header_role); ?></small>
                    </span>
                </span>
                <a class="btn btn-ghost btn-sm" href="logout.php">Log out</a>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="login.php">Log in</a>
                <a class="btn btn-primary btn-sm" href="register.php">Create account</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container <?php echo htmlspecialchars($main_class); ?>" id="main">
