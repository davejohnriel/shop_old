<?php
require 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$purchase_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT pm.purchase_id, pm.pdate, pm.id AS customer_id, u.name AS customer
    FROM purchase_master pm
    LEFT JOIN users u ON u.id = pm.id
    WHERE pm.purchase_id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    header('Location: orders.php');
    exit;
}

// only the owner or the customer who made it may see this purchase
if (!is_owner() && (int)$purchase['customer_id'] !== (int)$_SESSION['user_id']) {
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT pd.prod_id, pd.pqty, p.name, p.price
    FROM purchase_detail pd
    LEFT JOIN products p ON p.id = pd.prod_id
    WHERE pd.purchase_id = ?
    ORDER BY pd.p_id
");
$stmt->execute([$purchase_id]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $item) {
    if ($item['price'] !== null) {
        $total += $item['price'] * $item['pqty'];
    }
}

$page_title = 'Purchase confirmed';
$active = 'orders';
require 'partials/header.php';
?>

<div class="receipt" role="status">
    <span class="receipt-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5"></path>
        </svg>
    </span>

    <span class="eyebrow">Thank you</span>
    <h1>Purchase confirmed</h1>
    <p class="muted">Your order has been placed successfully.</p>

    <div class="receipt-meta">
        <span>Purchase #<?php echo (int)$purchase['purchase_id']; ?></span>
        <span><?php echo htmlspecialchars($purchase['pdate']); ?></span>
        <?php if (is_owner()): ?>
            <span>Customer: <?php echo htmlspecialchars($purchase['customer'] ?? 'Deleted customer'); ?></span>
        <?php endif; ?>
    </div>

    <?php if (count($items) == 0): ?>
        <p class="muted">No items are attached to this purchase.</p>
    <?php else: ?>
        <ul class="receipt-items">
            <?php foreach ($items as $item): ?>
                <li>
                    <span>
                        <?php
                        echo $item['name'] !== null
                            ? htmlspecialchars($item['name'])
                            : 'Deleted product #' . (int)$item['prod_id'];
                        ?>
                    </span>
                    <span class="qty">x <?php echo (int)$item['pqty']; ?></span>
                    <span class="amount">
                        <?php echo $item['price'] !== null ? '$' . number_format($item['price'] * $item['pqty'], 2) : '&mdash;'; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="receipt-total">
        <span>Total</span>
        <strong>$<?php echo number_format($total, 2); ?></strong>
    </div>

    <div class="receipt-actions">
        <a class="btn btn-primary" href="orders.php">View purchases</a>
        <a class="btn btn-secondary" href="index.php">Continue shopping</a>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
