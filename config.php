<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$host = 'localhost';
$dbname = 'ecommerce';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// ---- account state stays in sync with the database ----------------
// (so a role change, suspension or pending account takes effect at once)

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT name, role, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $account = $stmt->fetch();

    if (!$account || $account['status'] !== 'active') {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $_SESSION['role'] = $account['role'];
    $_SESSION['name'] = $account['name'];
}

// ---- role helpers -------------------------------------------------

function current_role()
{
    return $_SESSION['role'] ?? '';
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// customer = shops (cart, buy, own purchases)
function is_customer()
{
    return current_role() === 'customer';
}

// admin = accepts / manages customer accounts only
function is_admin()
{
    return current_role() === 'admin';
}

// owner = the whole shop
function is_owner()
{
    return current_role() === 'owner';
}

// admin or owner (both work in the accounts page)
function is_staff()
{
    return in_array(current_role(), ['admin', 'owner'], true);
}

function require_owner()
{
    if (!is_owner()) {
        header('Location: index.php');
        exit;
    }
}

function require_staff()
{
    if (!is_staff()) {
        header('Location: index.php');
        exit;
    }
}

// ---- product image upload -----------------------------------------
// Returns ['ok' => bool, 'filename' => string, 'error' => string]
function save_product_image($file)
{
    $result = ['ok' => false, 'filename' => '', 'error' => ''];

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Please choose an image to upload';
        return $result;
    }

    // check the real image type, not just the file extension
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];

    if (!$info || !isset($allowed[$info[2]])) {
        $result['error'] = 'Only JPG and PNG images are allowed';
        return $result;
    }

    $filename = time() . '_' . rand(1000, 9999) . '.' . $allowed[$info[2]];

    if (!move_uploaded_file($file['tmp_name'], 'uploads/' . $filename)) {
        $result['error'] = 'Could not save the image';
        return $result;
    }

    $result['ok'] = true;
    $result['filename'] = $filename;
    return $result;
}
