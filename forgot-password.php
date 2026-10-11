<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$data  = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Please enter a valid email address."]);
    exit;
}


$pdo = new PDO("mysql:host=localhost;dbname=pharmacy;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare(
    "SELECT id FROM users WHERE email = ? AND status = 1 AND deleted_at IS NULL"
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $token   = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", time() + 3600); 

    $upd = $pdo->prepare(
        "UPDATE users SET reset_token = ?, reset_expires = ?, updated_at = NOW() WHERE id = ?"
    );
    $upd->execute([hash('sha256', $token), $expires, $user['id']]);

    $link = "http://localhost:5173/reset-password?token=" . $token;

  
    file_put_contents(__DIR__ . "/reset-links.log", $link . PHP_EOL, FILE_APPEND);

    // @mail($email, "Password Reset", "Reset your password: " . $link);
}

echo json_encode([
    "message" => "If that email exists, a reset link has been sent."
]);