<?php
require 'config.php';
require_staff();

$owner = is_owner();
$me = (int)$_SESSION['user_id'];
$message = '';
$message_class = '';

// admin can only touch customer accounts, owner can touch everyone (except self)
function can_touch_account($account, $owner, $me)
{
    if (!$account || (int)$account['id'] === $me) {
        return false;
    }
    if ($owner) {
        return true;
    }
    return $account['role'] === 'customer';
}

// ---- actions -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['user_id'] ?? 0);

    // load the account being changed
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();

    if ($action == 'create_account') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $new_role = $owner ? ($_POST['role'] ?? 'customer') : 'customer';

        if (!in_array($new_role, ['customer', 'admin', 'owner'], true)) {
            $message = 'Invalid role';
            $message_class = 'error';
        } elseif ($name == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid name and email';
            $message_class = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password needs 6+ characters';
            $message_class = 'error';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = 'Email already used';
                $message_class = 'error';
            } else {
                $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')")
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $new_role]);
                $message = 'Account created';
                $message_class = 'success';
            }
        }
    }

    if ($action == 'set_status') {
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['active', 'suspended', 'rejected'], true)) {
            $message = 'Invalid status';
            $message_class = 'error';
        } elseif (!can_touch_account($account, $owner, $me)) {
            $message = 'You cannot change that account';
            $message_class = 'error';
        } else {
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $id]);

            if ($status == 'active') {
                $message = 'Account accepted';
            } elseif ($status == 'rejected') {
                $message = 'Account rejected';
            } else {
                $message = 'Account suspended';
            }
            $message_class = 'success';
        }
    }

    if ($action == 'change_role') {
        $new_role = $_POST['role'] ?? '';

        if (!$owner) {
            $message = 'Only the owner can change roles';
            $message_class = 'error';
        } elseif (!in_array($new_role, ['customer', 'admin', 'owner'], true)) {
            $message = 'Invalid role';
            $message_class = 'error';
        } elseif (!can_touch_account($account, $owner, $me)) {
            $message = 'You cannot change that account';
            $message_class = 'error';
        } else {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $id]);
            $message = 'Role updated';
            $message_class = 'success';
        }
    }

    if ($action == 'delete_account') {
        if (!can_touch_account($account, $owner, $me)) {
            $message = 'You cannot delete that account';
            $message_class = 'error';
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM purchase_master WHERE id = ?");
            $check->execute([$id]);

            if ($check->fetchColumn() > 0) {
                $message = 'Account has purchases, so it was not deleted';
                $message_class = 'error';
            } else {
                $pdo->prepare("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?")
                    ->execute([$id]);
                $pdo->prepare("DELETE FROM carts WHERE user_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $message = 'Account deleted';
                $message_class = 'success';
            }
        }
    }
}

// ---- lists ---------------------------------------------------------
if ($owner) {
    $accounts = $pdo->query("
        SELECT id, name, email, role, status, created_at
        FROM users
        ORDER BY (status = 'pending') DESC, id
    ")->fetchAll();
} else {
    $accounts = $pdo->query("
        SELECT id, name, email, role, status, created_at
        FROM users
        WHERE role = 'customer'
        ORDER BY (status = 'pending') DESC, id
    ")->fetchAll();
}

$pending = array_filter($accounts, function ($a) {
    return $a['status'] === 'pending';
});
?>
<?php
$page_title = 'Accounts';
$active = 'accounts';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">People &amp; access</span>
        <h1>Accounts</h1>
        <p>Approve new customers, create accounts, and manage roles and status from one place.</p>
    </div>
    <span class="count-pill"><?php echo count($accounts); ?> account<?php echo count($accounts) === 1 ? '' : 's'; ?></span>
</div>

<?php if ($message): ?>
    <p class="<?php echo $message_class; ?>" role="<?php echo $message_class === 'error' ? 'alert' : 'status'; ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="section-head" style="margin-top:0;">
    <div>
        <h2>Pending accounts</h2>
        <p>New registrations wait here until an admin accepts them.</p>
    </div>
    <span class="count-pill"><?php echo count($pending); ?> waiting</span>
</div>

<?php if (count($pending) == 0): ?>
    <div class="alert alert-success">No accounts are waiting for approval right now.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $u): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td class="table-note"><?php echo htmlspecialchars($u['created_at']); ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="success">Accept</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="danger">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="section-head">
    <div>
        <h2>Create account</h2>
        <p>Add a customer<?php echo $owner ? ', admin or owner' : ''; ?> directly.</p>
    </div>
</div>

<form method="post" class="form-card">
    <input type="hidden" name="action" value="create_account">

    <div class="form-grid">
        <div class="field">
            <label for="new-name">Name</label>
            <input type="text" id="new-name" name="name" autocomplete="off" required>
        </div>
        <div class="field">
            <label for="new-email">Email</label>
            <input type="email" id="new-email" name="email" autocomplete="off" required>
        </div>
        <div class="field">
            <label for="new-password">Password</label>
            <input type="password" id="new-password" name="password" autocomplete="new-password" minlength="6" required>
            <p class="field-help">At least 6 characters.</p>
        </div>
        <?php if ($owner): ?>
            <div class="field">
                <label for="new-role">Role</label>
                <select id="new-role" name="role">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="owner">Owner</option>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create account</button>
    </div>
</form>

<div class="section-head">
    <div>
        <h2>All accounts</h2>
        <p>Change roles, suspend or reactivate accounts, and remove accounts that are no longer needed.</p>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Role change</th>
                <th>Status change</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($accounts as $u): ?>
            <tr>
                <td class="table-note"><?php echo (int)$u['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                <td><span class="status <?php echo htmlspecialchars($u['status']); ?>"><?php echo htmlspecialchars($u['status']); ?></span></td>
                <td>
                    <?php if ($owner && (int)$u['id'] !== $me): ?>
                        <form method="post" class="table-actions">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                            <select name="role" aria-label="Role for <?php echo htmlspecialchars($u['name']); ?>">
                                <option value="customer" <?php if ($u['role'] == 'customer') echo 'selected'; ?>>Customer</option>
                                <option value="admin" <?php if ($u['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                <option value="owner" <?php if ($u['role'] == 'owner') echo 'selected'; ?>>Owner</option>
                            </select>
                            <button type="submit">Save</button>
                        </form>
                    <?php elseif ((int)$u['id'] === $me): ?>
                        <span class="table-note">This is you</span>
                    <?php else: ?>
                        <span class="table-note">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (can_touch_account($u, $owner, $me)): ?>
                        <?php if ($u['status'] == 'active'): ?>
                            <form method="post" onsubmit="return confirm('Suspend this account?')">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="danger">Suspend</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="success">Activate</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="table-note">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (can_touch_account($u, $owner, $me)): ?>
                        <form method="post" onsubmit="return confirm('Delete this account?')">
                            <input type="hidden" name="action" value="delete_account">
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                            <button type="submit" class="danger">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="table-note">&mdash;</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'partials/footer.php'; ?>
