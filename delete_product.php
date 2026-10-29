<?php
require 'config.php';

// only the owner manages products
if (!is_logged_in() || !is_owner()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['product_id'];

    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if ($p) {
        if ($p['image'] !== '' && file_exists('uploads/' . $p['image'])) {
            unlink('uploads/' . $p['image']);
        }
        $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}

header('Location: index.php');
exit;
