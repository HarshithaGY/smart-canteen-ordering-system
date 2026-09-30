<?php
session_start();
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'smart_canteen';

function db() {
    static $conn = null;
    if ($conn) return $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $root = mysqli_init();
        $root->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $root->real_connect(DB_HOST, DB_USER, DB_PASS);
        $root->set_charset('utf8mb4');
        $root->query('CREATE DATABASE IF NOT EXISTS '.DB_NAME.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $conn = mysqli_init();
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        init_db($conn);
        return $conn;
    } catch (Throwable $e) {
        die('<h2 style="font-family:Arial;margin:40px">Database connection failed</h2><p style="font-family:Arial;margin:40px">Start MySQL in XAMPP, then reload this page. '.$e->getMessage().'</p>');
    }
}

function init_db(mysqli $db) {
    $db->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL, email VARCHAR(160) NOT NULL UNIQUE, phone VARCHAR(30), password_hash VARCHAR(255) NOT NULL, role ENUM('user','admin') NOT NULL DEFAULT 'user', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->query("CREATE TABLE IF NOT EXISTS menu_items (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, price DECIMAL(10,2) NOT NULL, category VARCHAR(60) NOT NULL, image VARCHAR(255), is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->query("CREATE TABLE IF NOT EXISTS cart_items (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, menu_item_id INT NOT NULL, quantity INT NOT NULL DEFAULT 1, UNIQUE KEY unique_cart (user_id, menu_item_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE)");
    $db->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_number INT NOT NULL UNIQUE, total_amount DECIMAL(10,2) NOT NULL, status ENUM('Pending','Preparing','Ready','Delivered') NOT NULL DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    $db->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, menu_item_id INT NULL, item_name VARCHAR(120) NOT NULL, price DECIMAL(10,2) NOT NULL, quantity INT NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL)");

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $admin = $db->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetch_assoc();
    if ($admin) {
        $stmt = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $stmt->bind_param('si', $hash, $admin['id']);
        $stmt->execute();
    } else {
        $adminEmail = 'admin@smartcanteen.com';
        $name = 'Smart Canteen Admin'; $phone = '0000000000'; $role = 'admin';
        $ins = $db->prepare('INSERT INTO users(full_name,name,email,phone,password_hash,password,role) VALUES(?,?,?,?,?,?,?)');
        $ins->bind_param('sssssss', $name, $name, $adminEmail, $phone, $hash, $hash, $role);
        $ins->execute();
    }
    $count = $db->query('SELECT COUNT(*) c FROM menu_items')->fetch_assoc()['c'];
    if ((int)$count === 0) {
        $items = [
            ['Masala Dosa',40,'Breakfast','assets/dosa.svg'], ['Veg Burger',60,'Snacks','assets/burger.svg'],
            ['Veg Sandwich',50,'Breakfast','assets/sandwich.svg'], ['Pasta',70,'Lunch','assets/pasta.svg'],
            ['French Fries',40,'Snacks','assets/fries.svg'], ['Tea',15,'Beverages','assets/tea.svg']
        ];
        $ins = $db->prepare('INSERT INTO menu_items(name,price,category,image) VALUES(?,?,?,?)');
        foreach ($items as $i) { $ins->bind_param('sdss',$i[0],$i[1],$i[2],$i[3]); $ins->execute(); }
    }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($path) { header('Location: '.$path); exit; }
function flash($msg=null) { if ($msg !== null) { $_SESSION['flash']=$msg; return; } $m=$_SESSION['flash']??''; unset($_SESSION['flash']); return $m; }
function current_user() { return $_SESSION['user'] ?? null; }
function require_user() { if (!current_user() || current_user()['role'] !== 'user') redirect('login.php'); }
function require_admin() { if (!current_user() || current_user()['role'] !== 'admin') redirect('admin_login.php'); }
function cart_count($uid) { $s=db()->prepare('SELECT COALESCE(SUM(quantity),0) c FROM cart_items WHERE user_id=?'); $s->bind_param('i',$uid); $s->execute(); return (int)$s->get_result()->fetch_assoc()['c']; }
function wait_minutes($status) { return $status === 'Pending' ? 10 : ($status === 'Preparing' ? 5 : 0); }
function orders_ahead($order) {
    if (!in_array($order['status'], ['Pending','Preparing'], true)) return 0;
    $stmt=db()->prepare("SELECT COUNT(*) c FROM orders WHERE status IN ('Pending','Preparing') AND (created_at < ? OR (created_at = ? AND id < ?))");
    $stmt->bind_param('ssi',$order['created_at'],$order['created_at'],$order['id']);
    $stmt->execute(); return (int)$stmt->get_result()->fetch_assoc()['c'];
}
?>
