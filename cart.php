<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// get cart
$stmt = $pdo->prepare("SELECT * FROM carts WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

if (!$cart) {
    $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$user_id]);
    $cart_id = $pdo->lastInsertId();
    $cart = ['id' => $cart_id, 'user_id' => $user_id, 'created_at' => date('Y-m-d H:i:s')];
} else {
    $cart_id = $cart['id'];
}

// get items of this cart
$stmt = $pdo->prepare("
    SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.image, p.quantity AS stock
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cart_id]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $i) {
    $total += $i['price'] * $i['quantity'];
}

$page_title = 'My cart';
$active = 'cart';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Your cart</span>
        <h1>My cart</h1>
        <p>Review your items, adjust quantities, then check out when you are ready.</p>
    </div>
    <span class="count-pill"><?php echo count($items); ?> item<?php echo count($items) === 1 ? '' : 's'; ?> &middot; $<?php echo number_format($total, 2); ?></span>
</div>

<?php
$error_code = $_GET['error'] ?? '';
$error_messages = [
    'out_of_stock' => 'That product is out of stock.',
    'stock' => 'You cannot add more than the available stock.',
];
if (isset($error_messages[$error_code])) {
    echo '<p class="error" role="alert">' . htmlspecialchars($error_messages[$error_code]) . '</p>';
}
?>

<?php if (count($items) == 0): ?>
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8h12l-1 12H7L6 8Z"></path>
                <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
            </svg>
        </span>
        <h2>Your cart is empty</h2>
        <p>Add a few things you like and they will show up here, ready for checkout.</p>
        <div class="actions">
            <a class="btn btn-primary" href="index.php">Browse products</a>
        </div>
    </div>
<?php else: ?>
    <div class="section-head" style="margin-top:0;">
        <div>
            <h2>Master cart</h2>
            <p>The one cart record that groups all of the items below.</p>
        </div>
    </div>

    <div class="cart-meta-grid">
        <div>
            <span>Cart ID</span>
            <strong>#<?php echo (int)$cart['id']; ?></strong>
        </div>
        <div>
            <span>User ID</span>
            <strong>#<?php echo (int)$cart['user_id']; ?></strong>
        </div>
        <div>
            <span>Created</span>
            <strong style="font-size:.92rem;"><?php echo htmlspecialchars($cart['created_at']); ?></strong>
        </div>
        <div>
            <span>Products</span>
            <strong><?php echo count($items); ?></strong>
        </div>
    </div>

    <div class="section-head">
        <div>
            <h2>Cart items</h2>
            <p>Each row is one detail line linked to the master cart above.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="cart-product">
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <small>Item #<?php echo (int)$item['id']; ?> &middot; Product #<?php echo (int)$item['product_id']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>
                        <form method="post" action="update_cart.php" class="qty-form">
                            <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo (int)$item['stock']; ?>" aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                        </form>
                    </td>
                    <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                    <td>
                        <div class="cart-actions">
                            <form method="post" action="buy_cart_item.php">
                                <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="success" aria-label="Buy <?php echo htmlspecialchars($item['name']); ?>">Buy</button>
                            </form>
                            <form method="post" action="delete_cart_item.php">
                                <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="danger" aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="cart-summary">
        <div>
            <span>Order total</span>
            <strong>$<?php echo number_format($total, 2); ?></strong>
        </div>
        <div class="cart-summary-actions">
            <form method="post" action="buy_all.php">
                <button type="submit" class="success">Buy all items</button>
            </form>
            <form method="post" action="clear_cart.php">
                <button type="submit" class="btn btn-secondary">Clear cart</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
