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
    SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.image
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
?>
<?php
$page_title = 'Cart structure';
$active = 'cart';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Scratch view</span>
        <h1>Cart structure</h1>
        <p>A read-only look at the master cart row and its detail lines.</p>
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

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Product ID</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($items) == 0): ?>
            <tr>
                <td colspan="4" class="table-note">This cart is empty.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="cart-product">
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <small>Item #<?php echo (int)$item['id']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td>#<?php echo (int)$item['product_id']; ?></td>
                    <td><?php echo (int)$item['quantity']; ?></td>
                    <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'partials/footer.php'; ?>
