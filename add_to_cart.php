<?php
require 'config.php';

// only normal user
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

// product must exist and be in stock
$stmt = $pdo->prepare("SELECT id, quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['quantity'] < 1) {
    header('Location: cart.php?error=out_of_stock');
    exit;
}

// get or create cart for this user
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

if ($cart) {
    $cart_id = $cart['id'];
} else {
    $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$user_id]);
    $cart_id = $pdo->lastInsertId();
}

// check if product already in cart
$stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
$stmt->execute([$cart_id, $product_id]);
$item = $stmt->fetch();

$wanted = ($item ? (int)$item['quantity'] : 0) + 1;

if ($wanted > $product['quantity']) {
    header('Location: cart.php?error=stock');
    exit;
}

if ($item) {
    // already there → increase quantity
    $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?")
        ->execute([$item['id']]);
} else {
    // new item
    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)")
        ->execute([$cart_id, $product_id]);
}

header('Location: cart.php');
exit;
