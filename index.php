<?php
require 'config.php';

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

$role = current_role();
$logged = is_logged_in();
$owner = is_owner();
$admin = is_admin();

$featured = null;
$in_stock = 0;
foreach ($products as $product_row) {
    if ((int)$product_row['quantity'] > 0) {
        $in_stock++;
        if ($featured === null) {
            $featured = $product_row;
        }
    }
}

// If everything is sold out, still show the newest item in the hero.
if ($featured === null) {
    $featured = $products[0] ?? null;
}

$page_title = 'Shop';
$active = 'shop';
require 'partials/header.php';
?>

<section class="hero" aria-labelledby="hero-title">
    <div class="hero-copy">
        <span class="eyebrow">My Shop &middot; New arrivals</span>
        <h1 id="hero-title">Good things,<br>picked for you.</h1>
        <p class="hero-lead">A small, hand-picked catalogue of useful, fun and everyday finds &mdash; thoughtfully presented and ready to order.</p>

        <div class="hero-actions">
            <a class="btn btn-primary" href="#products">Browse products</a>
            <?php if (!$logged): ?>
                <a class="btn btn-ghost" href="register.php">Create an account</a>
            <?php elseif (is_customer()): ?>
                <a class="btn btn-ghost" href="cart.php">View my cart</a>
            <?php endif; ?>
        </div>

        <div class="hero-meta">
            <span><strong><?php echo count($products); ?></strong> products</span>
            <span><strong><?php echo $in_stock; ?></strong> ready to buy</span>
            <span><strong>Simple</strong> checkout</span>
        </div>
    </div>

    <?php if ($featured): ?>
        <aside class="hero-feature" aria-label="Featured product">
            <div class="hero-feature-top">
                <span><span class="dot"></span>Fresh arrival</span>
                <span>#<?php echo (int)$featured['id']; ?></span>
            </div>
            <img src="uploads/<?php echo htmlspecialchars($featured['image']); ?>" alt="<?php echo htmlspecialchars($featured['name']); ?>" fetchpriority="high">
            <div class="hero-feature-info">
                <div>
                    <strong><?php echo htmlspecialchars($featured['name']); ?></strong>
                    <small><?php echo (int)$featured['quantity'] > 0 ? 'In stock' : 'Currently sold out'; ?></small>
                </div>
                <span class="hero-price">$<?php echo number_format($featured['price'], 2); ?></span>
            </div>
        </aside>
    <?php endif; ?>
</section>

<div class="section-head" id="products">
    <div>
        <h2>All products</h2>
        <p>Browse the full collection and add your favourites to the cart.</p>
    </div>
    <span class="count-pill"><?php echo count($products); ?> item<?php echo count($products) === 1 ? '' : 's'; ?></span>
</div>

<?php if (count($products) == 0): ?>
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true">+</span>
        <h2>No products yet</h2>
        <p>The shelves are empty right now. Check back soon, or add the first product if you run the shop.</p>
        <?php if ($owner): ?>
            <div class="actions">
                <a class="btn btn-primary" href="add_product.php">Add your first product</a>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="products">
        <?php foreach ($products as $p): ?>
            <?php $stock = (int)$p['quantity']; ?>
            <article class="product-card">
                <div class="product-media">
                    <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy" decoding="async">
                    <?php if ($stock <= 0): ?>
                        <span class="stock-pill out">Sold out</span>
                    <?php elseif ($stock <= 3): ?>
                        <span class="stock-pill low">Only <?php echo $stock; ?> left</span>
                    <?php else: ?>
                        <span class="stock-pill">In stock &middot; <?php echo $stock; ?></span>
                    <?php endif; ?>
                </div>

                <div class="product-body">
                    <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                    <p class="product-desc"><?php echo htmlspecialchars($p['description']); ?></p>

                    <div class="product-foot">
                        <span class="price">$<?php echo number_format($p['price'], 2); ?></span>
                        <span class="muted" style="font-size:.74rem;font-weight:700;">Stock: <?php echo $stock; ?></span>
                    </div>

                    <?php if ($role == 'customer' && $stock > 0): ?>
                        <div class="product-actions">
                            <form method="post" action="add_to_cart.php">
                                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="btn-secondary">Add to cart</button>
                            </form>
                            <form method="post" action="buy_now.php">
                                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="success">Buy now</button>
                            </form>
                        </div>
                    <?php elseif ($role == 'customer' && $stock <= 0): ?>
                        <div class="product-actions">
                            <button type="button" class="btn-secondary" disabled>Sold out</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($owner): ?>
                        <div class="product-owner-actions">
                            <a class="btn btn-secondary btn-sm" href="edit_product.php?id=<?php echo (int)$p['id']; ?>" aria-label="Edit <?php echo htmlspecialchars($p['name']); ?>">Edit</a>
                            <form method="post" action="delete_product.php" onsubmit="return confirm('Delete this product?')">
                                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="danger" aria-label="Delete <?php echo htmlspecialchars($p['name']); ?>">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
