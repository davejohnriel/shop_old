<?php
require 'config.php';
require_owner();

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $me = $stmt->fetch();

        if (!$me || !password_verify($current, $me['password'])) {
            $message = 'Current password is wrong';
            $message_class = 'error';
        } elseif (strlen($new) < 6) {
            $message = 'New password must be at least 6 characters';
            $message_class = 'error';
        } elseif ($new !== $confirm) {
            $message = 'New passwords do not match';
            $message_class = 'error';
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $message = 'Password changed';
            $message_class = 'success';
        }
    }
}

$total_customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_pending = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_purchases = (int)$pdo->query("SELECT COUNT(*) FROM purchase_master")->fetchColumn();
$total_revenue = (float)$pdo->query("
    SELECT COALESCE(SUM(pd.pqty * p.price), 0)
    FROM purchase_detail pd
    JOIN products p ON p.id = pd.prod_id
")->fetchColumn();
?>
<?php
$page_title = 'Owner panel';
$active = 'owner';
require 'partials/header.php';

$revenue_full = '$' . number_format($total_revenue, 2);
if ($total_revenue >= 1000000) {
    $revenue_display = '$' . number_format($total_revenue / 1000000, 1) . 'M';
} else {
    $revenue_display = $revenue_full;
}
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Shop overview</span>
        <h1>Owner panel</h1>
        <p>A quick snapshot of the shop, plus your own account security.</p>
    </div>
    <span class="count-pill">Owner access</span>
</div>

<?php if ($message): ?>
    <p class="<?php echo $message_class; ?>" role="<?php echo $message_class === 'error' ? 'alert' : 'status'; ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-label">Customers</span>
        <strong class="stat-value"><?php echo $total_customers; ?></strong>
        <span class="stat-hint">Shopping accounts</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Admins</span>
        <strong class="stat-value"><?php echo $total_admins; ?></strong>
        <span class="stat-hint">Account managers</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Pending</span>
        <strong class="stat-value"><?php echo $total_pending; ?></strong>
        <span class="stat-hint">Waiting for approval</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Products</span>
        <strong class="stat-value"><?php echo $total_products; ?></strong>
        <span class="stat-hint">In the catalogue</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Purchases</span>
        <strong class="stat-value"><?php echo $total_purchases; ?></strong>
        <span class="stat-hint">Completed orders</span>
    </div>
    <div class="stat-card is-accent">
        <span class="stat-label">Revenue</span>
        <strong class="stat-value" title="<?php echo htmlspecialchars($revenue_full); ?>"><?php echo htmlspecialchars($revenue_display); ?></strong>
        <span class="stat-hint">Based on current prices</span>
    </div>
</div>

<div class="section-head">
    <div>
        <h2>Change my password</h2>
        <p>Use a password you do not reuse anywhere else.</p>
    </div>
</div>

<form method="post" class="form-card">
    <input type="hidden" name="action" value="change_password">

    <div class="form-grid">
        <div class="field field-full">
            <label for="current-password">Current password</label>
            <input type="password" id="current-password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="field">
            <label for="new-password">New password</label>
            <input type="password" id="new-password" name="new_password" autocomplete="new-password" minlength="6" required>
            <p class="field-help">At least 6 characters.</p>
        </div>
        <div class="field">
            <label for="confirm-password">Confirm new password</label>
            <input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" minlength="6" required>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Change password</button>
    </div>
</form>

<?php require 'partials/footer.php'; ?>
