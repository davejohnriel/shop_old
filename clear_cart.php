<?php
require 'config.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if ($cart) {
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart['id']]);
    }
}

header('Location: cart.php');
exit;
