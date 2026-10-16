<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;

$allowed = ['supplier_id', 'invoice_no', 'purchase_date', 'total_amount', 'user_id'];
$data = [];
foreach ($allowed as $key) {
    if (isset($_POST[$key])) {
        $data[$key] = $_POST[$key];
    }
}

$res = $cc->common_update('purchases', $data, ['id' => $id]);

echo json_encode($res);
