<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)$_POST['item_id'];
    $user_id = $_SESSION['user_id'];

    // only delete if it belongs to this user
    $stmt = $pdo->prepare("
        DELETE ci FROM cart_items ci
        JOIN carts c ON c.id = ci.cart_id
        WHERE ci.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$item_id, $user_id]);
}

header('Location: cart.php');
exit;
