<?php
// ============================================================
//  Muradak Market — Orders API
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'products':  getProducts();  break;
    case 'categories': getCategories(); break;
    case 'place_order': placeOrder();  break;
    case 'settings':  getSettings();  break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

// ─── Get Categories ─────────────────────────────────────────
function getCategories(): void {
    $db = getDB();
    $rows = $db->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
    jsonResponse($rows);
}

// ─── Get Products ───────────────────────────────────────────
function getProducts(): void {
    $db = getDB();
    $catSlug  = $_GET['category'] ?? '';
    $search   = $_GET['search']   ?? '';
    $featured = $_GET['featured'] ?? '';

    $where = ['p.is_active = 1'];
    $params = [];

    if ($catSlug) {
        $where[] = 'c.slug = :slug';
        $params[':slug'] = $catSlug;
    }
    if ($search) {
        $where[] = '(p.name_ar LIKE :q OR p.name_en LIKE :q)';
        $params[':q'] = "%$search%";
    }
    if ($featured === '1') {
        $where[] = 'p.is_featured = 1';
    }

    $sql = "SELECT p.*, c.name_ar AS cat_ar, c.name_en AS cat_en
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.is_featured DESC, p.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

// ─── Place Order ────────────────────────────────────────────
function placeOrder(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
        return;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
        return;
    }

    // Validate required fields
    $required = ['name', 'phone', 'address', 'items'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            jsonResponse(['error' => "Missing field: $field"], 422);
            return;
        }
    }

    $items = $body['items'];
    if (!is_array($items) || count($items) === 0) {
        jsonResponse(['error' => 'Cart is empty'], 422);
        return;
    }

    $db = getDB();

    // Fetch settings
    $settings = [];
    foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $deliveryFee = (float)($settings['delivery_fee'] ?? 0);

    // Validate & price items from DB
    $lineItems = [];
    $subtotal  = 0;

    foreach ($items as $item) {
        $pid = (int)($item['product_id'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 1));

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['error' => "Product not found: $pid"], 422);
            return;
        }

        $lineTotal  = $product['price'] * $qty;
        $subtotal  += $lineTotal;
        $lineItems[] = [
            'product_id' => $pid,
            'name_ar'    => $product['name_ar'],
            'name_en'    => $product['name_en'],
            'price'      => $product['price'],
            'qty'        => $qty,
            'subtotal'   => $lineTotal,
        ];
    }

    $total = $subtotal + $deliveryFee;

    // Generate order number
    $orderNum = 'MRK-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');

    $db->beginTransaction();
    try {
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO orders
              (order_number, customer_name, customer_phone, customer_email,
               delivery_address, notes, subtotal, delivery_fee, total, payment_method)
            VALUES (?,?,?,?,?,?,?,?,?,'cod')
        ");
        $stmt->execute([
            $orderNum,
            trim($body['name']),
            trim($body['phone']),
            trim($body['email'] ?? ''),
            trim($body['address']),
            trim($body['notes'] ?? ''),
            $subtotal,
            $deliveryFee,
            $total,
        ]);
        $orderId = $db->lastInsertId();

        // Insert order items
        $iStmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, name_ar, name_en, price, qty, subtotal)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($lineItems as $li) {
            $iStmt->execute([$orderId, $li['product_id'], $li['name_ar'], $li['name_en'],
                             $li['price'], $li['qty'], $li['subtotal']]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Order failed: ' . $e->getMessage()], 500);
        return;
    }

    // Send email notification
    sendOrderEmail($orderNum, $body, $lineItems, $subtotal, $deliveryFee, $total);

    jsonResponse([
        'success'      => true,
        'order_number' => $orderNum,
        'total'        => $total,
        'message'      => 'تم استلام طلبك بنجاح! Order received successfully!',
    ]);
}

// ─── Settings ───────────────────────────────────────────────
function getSettings(): void {
    $db = getDB();
    $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) { $out[$r['setting_key']] = $r['setting_value']; }
    jsonResponse($out);
}

// ─── Email Helper ────────────────────────────────────────────
function sendOrderEmail(string $orderNum, array $body, array $items,
                        float $sub, float $fee, float $total): void {
    $to      = STORE_EMAIL;
    $subject = "🛒 طلب جديد | New Order #$orderNum — Muradak Market";

    $itemRows = '';
    foreach ($items as $it) {
        $itemRows .= "<tr>
          <td style='padding:8px 12px;border-bottom:1px solid #f0e6d8;'>{$it['name_ar']}<br>
              <small style='color:#888;'>{$it['name_en']}</small></td>
          <td style='padding:8px 12px;border-bottom:1px solid #f0e6d8;text-align:center;'>{$it['qty']}</td>
          <td style='padding:8px 12px;border-bottom:1px solid #f0e6d8;text-align:right;'>
              KD " . number_format($it['price'], 3) . "</td>
          <td style='padding:8px 12px;border-bottom:1px solid #f0e6d8;text-align:right;'>
              KD " . number_format($it['subtotal'], 3) . "</td>
        </tr>";
    }

    $html = "<!DOCTYPE html><html dir='rtl'><head><meta charset='utf-8'></head><body
      style='font-family:Segoe UI,Arial,sans-serif;background:#fdf6ef;margin:0;padding:0;'>
      <div style='max-width:600px;margin:30px auto;background:#fff;border-radius:16px;
           overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);'>
        <div style='background:linear-gradient(135deg,#a0522d,#cd853f);padding:30px 24px;text-align:center;'>
          <h1 style='color:#fff;margin:0;font-size:24px;'>🛒 طلب جديد | New Order</h1>
          <p style='color:rgba(255,255,255,.85);margin:8px 0 0;font-size:18px;font-weight:bold;'>
             #$orderNum</p>
        </div>
        <div style='padding:24px;'>
          <table style='width:100%;border-collapse:collapse;margin-bottom:20px;'>
            <tr><td style='padding:6px 0;color:#888;width:140px;'>الاسم / Name</td>
                <td style='font-weight:600;'>{$body['name']}</td></tr>
            <tr><td style='padding:6px 0;color:#888;'>الهاتف / Phone</td>
                <td style='font-weight:600;'>{$body['phone']}</td></tr>
            <tr><td style='padding:6px 0;color:#888;'>العنوان / Address</td>
                <td>{$body['address']}</td></tr>
            " . (!empty($body['email']) ? "<tr><td style='padding:6px 0;color:#888;'>البريد / Email</td>
                <td>{$body['email']}</td></tr>" : "") . "
            " . (!empty($body['notes']) ? "<tr><td style='padding:6px 0;color:#888;'>ملاحظات / Notes</td>
                <td>{$body['notes']}</td></tr>" : "") . "
          </table>

          <table style='width:100%;border-collapse:collapse;'>
            <thead>
              <tr style='background:#fdf0e4;'>
                <th style='padding:10px 12px;text-align:right;color:#a0522d;'>المنتج / Product</th>
                <th style='padding:10px 12px;text-align:center;color:#a0522d;'>الكمية</th>
                <th style='padding:10px 12px;text-align:right;color:#a0522d;'>السعر</th>
                <th style='padding:10px 12px;text-align:right;color:#a0522d;'>الإجمالي</th>
              </tr>
            </thead>
            <tbody>$itemRows</tbody>
          </table>

          <div style='margin-top:16px;text-align:left;'>
            <p style='margin:4px 0;color:#666;'>Subtotal: <strong>KD " . number_format($sub,3) . "</strong></p>
            <p style='margin:4px 0;color:#666;'>Delivery: <strong>KD " . number_format($fee,3) . "</strong></p>
            <p style='margin:8px 0;font-size:20px;color:#a0522d;'>
               Total: <strong>KD " . number_format($total,3) . "</strong></p>
            <p style='margin:4px 0;color:#27ae60;font-weight:bold;'>💵 Payment: Cash on Delivery</p>
          </div>
        </div>
        <div style='background:#fdf0e4;padding:16px 24px;text-align:center;color:#888;font-size:13px;'>
          Muradak Market — U Crave, We Deliver
        </div>
      </div></body></html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Muradak Market <no-reply@muradak.com>\r\n";

    mail($to, $subject, $html, $headers);
}

// ─── Helper ─────────────────────────────────────────────────
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
