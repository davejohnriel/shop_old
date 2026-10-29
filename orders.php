<?php
require 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$role = current_role();
$user_id = $_SESSION['user_id'];

// total of a purchase = sum of (quantity x current product price)
$total_sql = "COALESCE((
    SELECT SUM(pd.pqty * p.price)
    FROM purchase_detail pd
    JOIN products p ON p.id = pd.prod_id
    WHERE pd.purchase_id = pm.purchase_id
), 0) AS total";

if (is_owner()) {
    $purchases = $pdo->query("
        SELECT pm.purchase_id, pm.pdate, pm.id AS customer_id, u.name AS customer, $total_sql
        FROM purchase_master pm
        LEFT JOIN users u ON u.id = pm.id
        ORDER BY pm.purchase_id DESC
    ")->fetchAll();
    $title = 'All purchases';
    $show_customer = true;
} elseif (is_customer()) {
    $stmt = $pdo->prepare("
        SELECT pm.purchase_id, pm.pdate, pm.id AS customer_id, u.name AS customer, $total_sql
        FROM purchase_master pm
        LEFT JOIN users u ON u.id = pm.id
        WHERE pm.id = ?
        ORDER BY pm.purchase_id DESC
    ");
    $stmt->execute([$user_id]);
    $purchases = $stmt->fetchAll();
    $title = 'My purchases';
    $show_customer = false;
} else {
    // admins work in accounts.php, not with purchases
    header('Location: accounts.php');
    exit;
}

$page_title = $title;
$active = 'orders';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow"><?php echo $show_customer ? 'Shop activity' : 'Your history'; ?></span>
        <h1><?php echo $title; ?></h1>
        <p><?php echo $show_customer ? 'Every purchase made across the shop, newest first.' : 'Every order you have placed, newest first.'; ?></p>
    </div>
    <span class="count-pill"><?php echo count($purchases); ?> order<?php echo count($purchases) === 1 ? '' : 's'; ?></span>
</div>

<?php if (count($purchases) == 0): ?>
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"></path>
                <path d="M9 8h6M9 12h6"></path>
            </svg>
        </span>
        <h2>No purchases yet</h2>
        <p><?php echo $show_customer ? 'Purchases will appear here as soon as customers check out.' : 'When you check out, your orders will appear here with their items and totals.'; ?></p>
        <div class="actions">
            <?php if ($show_customer): ?>
                <a class="btn btn-primary" href="index.php">Go to the shop</a>
            <?php else: ?>
                <a class="btn btn-primary" href="cart.php">Open my cart</a>
                <a class="btn btn-secondary" href="index.php">Browse products</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="order-list">
        <?php foreach ($purchases as $p): ?>
            <article class="order-card">
                <div class="order-head">
                    <div>
                        <span class="order-kicker">Purchase</span>
                        <h2>Order #<?php echo (int)$p['purchase_id']; ?></h2>
                        <p class="order-date"><?php echo htmlspecialchars($p['pdate']); ?></p>
                    </div>
                    <div class="order-total">
                        <span>Total</span>
                        <strong>$<?php echo number_format($p['total'], 2); ?></strong>
                    </div>
                </div>

                <?php if ($show_customer): ?>
                    <p class="order-customer">
                        Customer:
                        <strong><?php echo $p['customer'] !== null ? htmlspecialchars($p['customer']) : 'Deleted customer'; ?></strong>
                    </p>
                <?php endif; ?>

                <ul class="order-items">
                <?php
                $items = $pdo->prepare("
                    SELECT pd.p_id, pd.prod_id, pd.pqty, p.name, p.price
                    FROM purchase_detail pd
                    LEFT JOIN products p ON p.id = pd.prod_id
                    WHERE pd.purchase_id = ?
                    ORDER BY pd.p_id
                ");
                $items->execute([$p['purchase_id']]);
                foreach ($items->fetchAll() as $item):
                ?>
                    <li>
                        <span class="order-item-name">
                            <?php
                            echo $item['name'] !== null
                                ? htmlspecialchars($item['name'])
                                : 'Deleted product #' . (int)$item['prod_id'];
                            ?>
                        </span>
                        <span class="order-item-qty">x <?php echo (int)$item['pqty']; ?></span>
                        <span class="order-item-price">
                            <?php if ($item['price'] !== null): ?>
                                $<?php echo number_format($item['price'] * $item['pqty'], 2); ?>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
