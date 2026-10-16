<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;

$allowed = ['invoice_no',
    'supplier_id',
    'medicine_id',
    'user_id',
    'purchase_date',
    'quantity',
    'price',
    'subtotal'];
$data = [];
foreach ($allowed as $key) {
    if (isset($_POST[$key])) {
        $data[$key] = $_POST[$key];
    }
}

$res = $cc->common_update('purchases', $data, ['id' => $id]);

echo json_encode($res);
