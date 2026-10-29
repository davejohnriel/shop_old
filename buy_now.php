<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';

try {
    $pdo->beginTransaction();

    // lock the product row so two people cannot buy the last one
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch();

    if (!$p || $p['quantity'] < 1) {
        $pdo->rollBack();
        $error = 'That product is out of stock.';
    } else {
        $qty = 1;

        $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")
            ->execute([$qty, $product_id]);

        $pdo->prepare("INSERT INTO purchase_master (id) VALUES (?)")->execute([$user_id]);
        $purchase_id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO purchase_detail (purchase_id, prod_id, pqty) VALUES (?,?,?)")
            ->execute([$purchase_id, $product_id, $qty]);

        $pdo->commit();

        // redirect after POST so refreshing the page cannot buy again
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
$active = 'shop';
$main_class = 'page page-narrow';
require 'partials/header.php';
?>

<div class="empty-state">
    <span class="empty-icon" aria-hidden="true">!</span>
    <h1>Purchase not completed</h1>
    <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
    <p>Nothing was charged and your cart has not been changed.</p>
    <div class="actions">
        <a class="btn btn-primary" href="index.php">Back to the shop</a>
        <a class="btn btn-secondary" href="cart.php">View cart</a>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
