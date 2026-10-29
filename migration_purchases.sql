-- ============================================================
-- Purchase master / purchase detail
-- Replaces the old orders / order_items tables.
--
-- purchase_master
--   purchase_id = purchase number (primary key)
--   pdate       = purchase date
--   id          = customer id (users.id)
--
-- purchase_detail
--   p_id        = detail line id (primary key)
--   purchase_id = which purchase it belongs to
--   prod_id     = product id
--   pqty        = how many
--
-- Run once in phpMyAdmin or:
--   mysql -u root ecommerce < migration_purchases.sql
-- ============================================================

CREATE TABLE purchase_master (
    purchase_id INT NOT NULL AUTO_INCREMENT,
    pdate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id INT NULL,
    PRIMARY KEY (purchase_id),
    KEY idx_purchase_customer (id),
    CONSTRAINT fk_purchase_customer
        FOREIGN KEY (id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_detail (
    p_id INT NOT NULL AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    prod_id INT NOT NULL,
    pqty INT NOT NULL DEFAULT 1,
    PRIMARY KEY (p_id),
    KEY idx_detail_purchase (purchase_id),
    KEY idx_detail_product (prod_id),
    CONSTRAINT fk_detail_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchase_master (purchase_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Move the old order data into the new tables
INSERT INTO purchase_master (purchase_id, pdate, id)
SELECT id, created_at, user_id FROM orders;

INSERT INTO purchase_detail (purchase_id, prod_id, pqty)
SELECT order_id, product_id, quantity FROM order_items;

-- Old tables are no longer used
DROP TABLE order_items;
DROP TABLE orders;
