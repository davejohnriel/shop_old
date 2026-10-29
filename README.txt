OLD-STYLE SIMPLE PHP SHOP
=========================

This version is intentionally flatter and more "old PHP" style
so it is easier to explain.

ROLES
-----
customer - browses products, adds to cart, buys, sees own purchases
admin    - accepts/rejects new accounts and manages customer accounts
owner    - everything: products, all orders, accounts, roles

LOGINS
------
Owner:
Email: owner@shop.local
Password: owner123

Admin:
Email: admin@shop.local
Password: admin123

Customers register themselves. New accounts stay "pending" until
an admin accepts them, so a customer cannot log in before approval.

HOW TO INSTALL
1. Import schema.sql (or migration.sql if you already have tables)
2. Run migration_roles.sql (adds the role names + account status)
3. Run migration_purchases.sql (creates purchase_master + purchase_detail)
4. Put the whole shop_old folder in htdocs
5. Open http://localhost/shop_old/

FILE LIST (everything in one folder)
------------------------------------
config.php          - database connection + session + role helpers
index.php           - product list (dashboard)
register.php        - create account (starts as pending)
login.php           - login (blocks pending/suspended accounts)
logout.php          - logout
add_product.php     - owner adds product (form + process)
edit_product.php    - owner edits product
delete_product.php  - owner deletes product
accounts.php        - admin/owner: accept accounts, create/delete/suspend
owner.php           - owner: shop overview + change password
add_to_cart.php     - customer adds product to cart
cart.php            - shows master cart + cart items
update_cart.php     - change quantity
delete_cart_item.php- remove one item
clear_cart.php      - empty the cart
buy_now.php         - buy single product directly
buy_cart_item.php   - buy one item from cart
buy_all.php         - buy everything in cart
orders.php          - owner sees all purchases, customer sees own
purchase_done.php   - purchase confirmation page
style.css
uploads/            - product images go here

ONE-TO-MANY EXPLANATION
-----------------------
purchase_master table = one purchase (purchase_id, pdate, id = customer)
purchase_detail table = the products inside that purchase (p_id, purchase_id, prod_id, pqty)

purchase_master.purchase_id  ->  purchase_detail.purchase_id
One purchase = many detail lines.

carts table     = the cart itself (1 per user)
cart_items table= the products inside the cart (many)

When you open cart.php you see:
1. Master Cart table (Cart ID, User ID, etc.)
2. Cart Items table (all products that belong to that Cart ID)

When you open orders.php you see:
1. Purchase master (purchase_id, date, customer)
2. Purchase detail (product + quantity) for each purchase
