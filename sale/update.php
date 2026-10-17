<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;

$allowed = ['invoice_no',
    'customer_name',
    'medicine_id',
    'user_id',
    'sale_date',
    'quantity',
    'price',
    'subtotal'];
$data = [];
foreach ($allowed as $key) {
    if (isset($_POST[$key])) {
        $data[$key] = $_POST[$key];
    }
}

$res = $cc->common_update('sales', $data, ['id' => $id]);

echo json_encode($res);
