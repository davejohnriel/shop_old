</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <strong>My Shop</strong>
            <span>Simple shopping, thoughtfully presented.</span>
        </div>
        <nav class="footer-nav" aria-label="Footer navigation">
            <a href="index.php">Shop</a>
            <?php if (is_customer()): ?>
                <a href="cart.php">Cart</a>
                <a href="orders.php">My purchases</a>
            <?php elseif (is_owner()): ?>
                <a href="orders.php">Purchases</a>
                <a href="accounts.php">Accounts</a>
                <a href="owner.php">Owner panel</a>
            <?php elseif (is_admin()): ?>
                <a href="accounts.php">Accounts</a>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php">Create account</a>
            <?php endif; ?>
        </nav>
        <span class="footer-copy">&copy; <?php echo date('Y'); ?> My Shop</span>
    </div>
</footer>
</body>
</html>
