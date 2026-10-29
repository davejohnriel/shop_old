-- ============================================================
-- Roles update: customer / admin / owner  +  account approval
-- Run once in phpMyAdmin (SQL tab) or:
--   mysql -u root ecommerce < migration_roles.sql
--
-- customer = shops (cart, buy, own orders)
-- admin    = accepts / manages customer accounts only
-- owner    = everything (products, all orders, accounts, roles)
-- ============================================================

-- 1. New role names (works whether role was ENUM or VARCHAR)
ALTER TABLE users
    MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'customer';

-- 2. Account status: pending / active / suspended / rejected
ALTER TABLE users
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active';

UPDATE users SET role = 'customer' WHERE role = 'user';
UPDATE users SET role = 'owner'    WHERE role = 'super_admin';
UPDATE users SET status = 'active' WHERE status = '' OR status IS NULL;

-- 3. Turn the old super admin into the owner account
--    Login: owner@shop.local / owner123
UPDATE users
SET name = 'Owner',
    email = 'owner@shop.local',
    role = 'owner',
    status = 'active',
    password = '$2y$10$m59yzpg4xHBxMPwD8GM/sekUjC3hNZzuajbLXOZo/ayGJ2uTYCi.2'
WHERE email = 'superadmin@shop.local';

-- 4. Make sure an owner always exists (fresh install / no old super admin)
INSERT INTO users (name, email, password, role, status)
SELECT 'Owner', 'owner@shop.local',
       '$2y$10$m59yzpg4xHBxMPwD8GM/sekUjC3hNZzuajbLXOZo/ayGJ2uTYCi.2',
       'owner', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'owner');
