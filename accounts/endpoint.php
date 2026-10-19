<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function accounts_require_method(array $methods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, $methods, true)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', array_merge($methods, ['OPTIONS'])));
        echo json_encode([
            'status' => false,
            'message' => 'Method not allowed',
            'data' => [],
        ]);
        exit;
    }
}
