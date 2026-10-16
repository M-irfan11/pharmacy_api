<?php
include '../connection.php';

// শুধু purchases table-এর column-গুলো নেওয়া হচ্ছে
$allowed = ['supplier_id', 'invoice_no', 'purchase_date', 'total_amount', 'user_id'];
$data = [];
foreach ($allowed as $key) {
    if (isset($_POST[$key])) {
        $data[$key] = $_POST[$key];
    }
}

$res = [];
if (!empty($data['supplier_id']) && !empty($data['invoice_no']) 
    && !empty($data['purchase_date']) && !empty($data['user_id'])) {
    $res = $cc->common_insert('purchases', $data);
} else {
    $res = ['status' => false, 'message' => 'সব তথ্য দিতে হবে, user_id সহ'];
}

echo json_encode($res);


