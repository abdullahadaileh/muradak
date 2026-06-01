<?php
// ============================================================
//  Muradak Admin — API
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$db     = getDB();

switch ($action) {
    case 'admin_orders':   getOrders($db);       break;
    case 'order_detail':   getOrderDetail($db);  break;
    case 'update_status':  updateStatus($db);    break;
    case 'products':       getProducts($db);     break;
    case 'product_detail': getProduct($db);      break;
    case 'add_product':    addProduct($db);      break;
    case 'update_product': updateProduct($db);   break;
    case 'toggle_product': toggleProduct($db);   break;
    case 'settings':       getSettings($db);     break;
    case 'save_settings':  saveSettings($db);    break;
    default: echo json_encode(['error' => 'Unknown action']);
}

function getOrders(PDO $db): void {
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $limit  = (int)($_GET['limit'] ?? 100);
    $where  = ['1=1'];
    $params = [];
    if ($status) { $where[] = 'status = :status'; $params[':status'] = $status; }
    if ($search) { $where[] = '(customer_name LIKE :q OR order_number LIKE :q)'; $params[':q'] = "%$search%"; }
    $sql = "SELECT * FROM orders WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
}

function getOrderDetail(PDO $db): void {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    $stmt2 = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt2->execute([$id]);
    $items = $stmt2->fetchAll();
    echo json_encode(['order' => $order, 'items' => $items], JSON_UNESCAPED_UNICODE);
}

function updateStatus(PDO $db): void {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','confirmed','processing','out_for_delivery','delivered','cancelled'];
    if (!in_array($status, $allowed)) { echo json_encode(['error' => 'Invalid status']); return; }
    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);
    echo json_encode(['success' => true]);
}

function getProducts(PDO $db): void {
    $rows = $db->query("SELECT p.*, c.name_ar AS cat_ar FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC")->fetchAll();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
}

function getProduct(PDO $db): void {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(), JSON_UNESCAPED_UNICODE);
}

function addProduct(PDO $db): void {
    $stmt = $db->prepare("INSERT INTO products (category_id,name_ar,name_en,price,old_price,stock,is_featured) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([
        (int)$_POST['category_id'],
        trim($_POST['name_ar']),
        trim($_POST['name_en']),
        (float)$_POST['price'],
        !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null,
        (int)($_POST['stock'] ?? 100),
        (int)($_POST['is_featured'] ?? 0),
    ]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
}

function updateProduct(PDO $db): void {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("UPDATE products SET category_id=?,name_ar=?,name_en=?,price=?,old_price=?,stock=?,is_featured=? WHERE id=?");
    $stmt->execute([
        (int)$_POST['category_id'],
        trim($_POST['name_ar']),
        trim($_POST['name_en']),
        (float)$_POST['price'],
        !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null,
        (int)($_POST['stock'] ?? 100),
        (int)($_POST['is_featured'] ?? 0),
        $id,
    ]);
    echo json_encode(['success' => true]);
}

function toggleProduct(PDO $db): void {
    $id        = (int)($_POST['id'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 0);
    $db->prepare("UPDATE products SET is_active=? WHERE id=?")->execute([$is_active, $id]);
    echo json_encode(['success' => true]);
}

function getSettings(PDO $db): void {
    $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) { $out[$r['setting_key']] = $r['setting_value']; }
    echo json_encode($out);
}

function saveSettings(PDO $db): void {
    $keys = ['delivery_fee','min_order','whatsapp','store_open'];
    $stmt = $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($keys as $k) {
        if (isset($_POST[$k])) $stmt->execute([$k, $_POST[$k]]);
    }
    echo json_encode(['success' => true]);
}
