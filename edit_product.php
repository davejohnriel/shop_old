<?php
require 'config.php';

// only the owner manages products
if (!is_logged_in() || !is_owner()) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) die('Product not found');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $filename = $p['image'];

    if ($name == '' || !is_numeric($price) || $price < 0 || $quantity < 0) {
        $error = 'Enter a valid name, price and quantity';
    } else {
        $file_error = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($file_error !== UPLOAD_ERR_NO_FILE) {
            $upload = save_product_image($_FILES['image']);

            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $filename = $upload['filename'];
            }
        }

        if (!$error) {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, quantity=?, image=? WHERE id=?")
                ->execute([$name, $description, $price, $quantity, $filename, $id]);

            // remove the old image file when it was replaced
            if ($filename !== $p['image'] && $p['image'] !== '' && file_exists('uploads/' . $p['image'])) {
                unlink('uploads/' . $p['image']);
            }

            header('Location: index.php');
            exit;
        }
    }
}

$page_title = 'Edit product';
$active = 'products';
require 'partials/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Catalogue</span>
        <h1>Edit product</h1>
        <p>Update the details, stock or image for this item.</p>
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
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required autofocus>
        </div>

        <div class="field field-full">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($p['description']); ?></textarea>
        </div>

        <div class="field">
            <label for="price">Price <span class="required">*</span></label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($p['price']); ?>" required>
        </div>

        <div class="field">
            <label for="quantity">Quantity in stock <span class="required">*</span></label>
            <input type="number" id="quantity" name="quantity" min="0" value="<?php echo (int)$p['quantity']; ?>" required>
        </div>

        <div class="field field-full">
            <label>Current image</label>
            <div class="image-preview">
                <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <span>Leave the file field empty to keep this image.</span>
            </div>
        </div>

        <div class="field field-full">
            <label for="image">Replace image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png">
            <p class="field-help">Optional. JPG or PNG only.</p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
    </div>
</form>

<?php require 'partials/footer.php'; ?>
