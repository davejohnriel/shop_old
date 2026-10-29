<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: cart.php');
    exit;
}

$item_id = (int)($_POST['item_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';

try {
    $pdo->beginTransaction();

    // item must belong to this user; lock item + product rows
    $stmt = $pdo->prepare("
        SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.quantity AS stock
        FROM cart_items ci
        JOIN carts c ON c.id = ci.cart_id
        JOIN products p ON p.id = ci.product_id
        WHERE ci.id = ? AND c.user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $pdo->rollBack();
        $error = 'That cart item no longer exists.';
    } elseif ($item['quantity'] < 1 || $item['quantity'] > $item['stock']) {
        $pdo->rollBack();
        $error = 'There is not enough stock for that item.';
    } else {
        $qty = (int)$item['quantity'];

        $pdo->prepare("INSERT INTO purchase_master (id) VALUES (?)")->execute([$user_id]);
        $purchase_id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO purchase_detail (purchase_id, prod_id, pqty) VALUES (?,?,?)")
            ->execute([$purchase_id, $item['product_id'], $qty]);

        $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")
            ->execute([$qty, $item['product_id']]);

        $pdo->prepare("DELETE FROM cart_items WHERE id = ?")->execute([$item_id]);

        $pdo->commit();

        header('Location: purchase_done.php?id=' . $purchase_id);
        exit;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = 'Could not complete the purchase. Please try again.';
}
?>
<?php
$page_title = 'Purchase not completed';
$active = 'cart';
$main_class = 'page page-narrow';
require 'partials/header.php';
?>

<div class="empty-state">
    <span class="empty-icon" aria-hidden="true">!</span>
    <h1>Purchase not completed</h1>
    <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
    <p>Your cart has been left as it was so you can review the quantity and try again.</p>
    <div class="actions">
        <a class="btn btn-primary" href="cart.php">Back to cart</a>
        <a class="btn btn-secondary" href="index.php">Continue shopping</a>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
