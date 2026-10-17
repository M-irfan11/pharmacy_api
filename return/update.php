<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;

$allowed = [
    'purchase_id',
    'sale_id',
    'medicine_id',
    'user_id',
    'return_date',
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
