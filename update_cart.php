<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)$_POST['item_id'];
    $qty = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    // make sure this item belongs to the user's cart + get the stock
    $stmt = $pdo->prepare("
        SELECT ci.id, p.quantity AS stock
        FROM cart_items ci
        JOIN carts c ON c.id = ci.cart_id
        JOIN products p ON p.id = ci.product_id
        WHERE ci.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$item_id, $user_id]);
    $row = $stmt->fetch();

    if ($row) {
        if ($qty <= 0) {
            $pdo->prepare("DELETE FROM cart_items WHERE id = ?")->execute([$item_id]);
        } elseif ($qty > $row['stock']) {
            // never allow more than the stock we have
            if ($row['stock'] > 0) {
                $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")
                    ->execute([$row['stock'], $item_id]);
            } else {
                $pdo->prepare("DELETE FROM cart_items WHERE id = ?")->execute([$item_id]);
            }
            header('Location: cart.php?error=stock');
            exit;
        } else {
            $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")->execute([$qty, $item_id]);
        }
    }
}

header('Location: cart.php');
exit;
