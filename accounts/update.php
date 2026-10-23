<?php
require_once __DIR__ . '/endpoint.php';
accounts_require_method(['POST']);

$action = $_POST['action'] ?? 'post_voucher';
if (!in_array($action, ['post_voucher', 'edit_voucher', 'update_account'], true)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Select a valid voucher update action.', 'data' => []]);
    exit;
}

$_GET['action'] = $action;
define('ACCOUNTS_API_ENTRY', true);
require __DIR__ . '/service.php';
