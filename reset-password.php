<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$data     = json_decode(file_get_contents("php://input"), true);
$token    = trim($data['token'] ?? '');
$password = $data['password'] ?? '';

if ($token === '' || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid information. Password must be at least 6 characters long."]);
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=pharmacy;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// token হ্যাশ করে মেলানো + মেয়াদ আছে কিনা দেখা
$stmt = $pdo->prepare(
    "SELECT id FROM users
     WHERE reset_token = ? AND reset_expires > NOW()
       AND status = 1 AND deleted_at IS NULL"
);
$stmt->execute([hash('sha256', $token)]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(400);
    echo json_encode(["message" => "লিংকটির মেয়াদ শেষ হয়ে গেছে বা সঠিক নয়।"]);
    exit;
}

$upd = $pdo->prepare(
    "UPDATE users
     SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW()
     WHERE id = ?"
);
$upd->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

echo json_encode(["message" => "password reset successful. You can now log in with your new password."]);