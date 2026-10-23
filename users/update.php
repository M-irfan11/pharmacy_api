<?php
include '../connection.php';

header('Content-Type: application/json; charset=utf-8');

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$status = filter_var($_POST['status'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    echo json_encode(['status' => false, 'message' => 'A valid user ID is required.']);
    exit;
}

if ($name === '' || $status === false || $status === null || !in_array($status, [0, 1], true)) {
    echo json_encode(['status' => false, 'message' => 'A user name and a valid status (0 or 1) are required.']);
    exit;
}

$post = [
    'name' => $cc->conn->real_escape_string($name),
    'status' => $status
];

$res = $cc->common_update('users', $post, ['id' => $id]);

echo json_encode($res);

