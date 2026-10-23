<?php
require_once __DIR__ . '/endpoint.php';
accounts_require_method(['POST']);

$type = $_GET['type'] ?? '';
$actionByType = [
    'account' => 'save_account',
    'voucher' => 'save_voucher',
    'shop_expense' => 'save_shop_expense',
    'invoice_voucher' => 'create_invoice_voucher',
];
if (!isset($actionByType[$type])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Select a valid Accounts creation type.', 'data' => []]);
    exit;
}

$_GET['action'] = $actionByType[$type];
define('ACCOUNTS_API_ENTRY', true);
require __DIR__ . '/service.php';
