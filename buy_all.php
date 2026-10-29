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

$user_id = $_SESSION['user_id'];
$error = '';

try {
    $pdo->beginTransaction();

    // lock all cart rows and their product rows
    $stmt = $pdo->prepare("
        SELECT ci.id, ci.cart_id, ci.product_id, ci.quantity,
               p.name, p.price, p.quantity AS stock
        FROM cart_items ci
        JOIN carts c ON c.id = ci.cart_id
        JOIN products p ON p.id = ci.product_id
        WHERE c.user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    if (count($items) == 0) {
        $pdo->rollBack();
        header('Location: cart.php');
        exit;
    }

    // check every line before changing anything
    $enough_stock = true;
    foreach ($items as $i) {
        if ($i['quantity'] < 1 || $i['quantity'] > $i['stock']) {
            $enough_stock = false;
            break;
        }
    }

    if (!$enough_stock) {
        $pdo->rollBack();
        $error = 'Some items are no longer available in that quantity.';
    } else {
        $pdo->prepare("INSERT INTO purchase_master (id) VALUES (?)")->execute([$user_id]);
        $purchase_id = (int)$pdo->lastInsertId();

        foreach ($items as $i) {
            $pdo->prepare("INSERT INTO purchase_detail (purchase_id, prod_id, pqty) VALUES (?,?,?)")
                ->execute([$purchase_id, $i['product_id'], $i['quantity']]);

            $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")
                ->execute([$i['quantity'], $i['product_id']]);
        }

        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$items[0]['cart_id']]);

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
    <p>Your cart has been left as it was so you can review the quantities and try again.</p>
    <div class="actions">
        <a class="btn btn-primary" href="cart.php">Back to cart</a>
        <a class="btn btn-secondary" href="index.php">Continue shopping</a>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
