<?php
require_once __DIR__ . '/endpoint.php';
accounts_require_method(['GET']);

$view = $_GET['view'] ?? $_GET['action'] ?? '';
$allowedViews = ['accounts', 'vouchers', 'ledger', 'profit_loss'];
if (!in_array($view, $allowedViews, true)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Select a valid Accounts listing or report.', 'data' => []]);
    exit;
}

$_GET['action'] = $view;
define('ACCOUNTS_API_ENTRY', true);
require __DIR__ . '/service.php';
