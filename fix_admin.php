<?php
require_once 'config/database.php';
$db = getDB();
$db->exec("DELETE FROM admin_users WHERE username = 'admin'");
$stmt = $db->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
$stmt->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'abdullahadaileh957@gmail.com']);
echo "تم بنجاح!";
?>