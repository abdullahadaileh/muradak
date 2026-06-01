<?php
// ============================================================
//  Muradak Admin — Authentication
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(['error' => 'Missing credentials']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['admin_id']   = $user['id'];
    $_SESSION['admin_user'] = $user['username'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid username or password']);
}
