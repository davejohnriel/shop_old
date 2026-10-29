<?php
require 'config.php';

// only the owner manages products
if (!is_logged_in() || !is_owner()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = (int)$_POST['quantity'];

    if ($name == '' || !is_numeric($price) || $price < 0 || $quantity < 0) {
        $error = 'Enter a valid name, price and quantity';
    } else {
        $upload = save_product_image($_FILES['image'] ?? null);

        if (!$upload['ok']) {
            $error = $upload['error'];
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, quantity, image) VALUES (?,?,?,?,?)")
                ->execute([$name, $description, $price, $quantity, $upload['filename']]);

            header('Location: index.php');
            exit;
        }
    }
}

$page_title = 'Add product';
$active = 'products';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Catalogue</span>
        <h1>Add product</h1>
        <p>Add a new item to the shop. It will appear on the storefront right away.</p>
    </div>
    <a class="btn btn-secondary" href="index.php">Back to shop</a>
</div>

<?php if ($error): ?>
    <p class="error" role="alert"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">
    <div class="form-grid">
        <div class="field field-full">
            <label for="name">Product name <span class="required">*</span></label>
            <input type="text" id="name" name="name" required autofocus>
        </div>

        <div class="field field-full">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="What should customers know about this product?"></textarea>
        </div>

        <div class="field">
            <label for="price">Price <span class="required">*</span></label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>

        <div class="field">
            <label for="quantity">Quantity in stock <span class="required">*</span></label>
            <input type="number" id="quantity" name="quantity" min="0" value="1" required>
        </div>

        <div class="field field-full">
            <label for="image">Product image <span class="required">*</span></label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png" required>
            <p class="field-help">JPG or PNG only. A wide image works best.</p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save product</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
    </div>
</form>

<?php require 'partials/footer.php'; ?>
