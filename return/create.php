<?php
include '../connection.php';

$required = [    
    'return_type',
    'purchase_id',
    'sale_id',
    'medicine_id',
    'return_date',    
    'user_id',
    'purchase_date',
    'quantity',
    'price',
    'subtotal'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        echo json_encode([
            'status' => false,
            'message' => "Missing required field: $field"
        ]);
        exit;
    }
}

$returnType = filter_var($_POST['return_type'], FILTER_VALIDATE_INT);
$purchaseId = filter_var($_POST['purchase_id'], FILTER_VALIDATE_INT);
$saleId = filter_var($_POST['sale_id'], FILTER_VALIDATE_INT);
$medicineId = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
$returnDate = filter_var($_POST['return_date'], FILTER_VALIDATE_INT);
$userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
$invoiceNo = trim((string) $_POST['invoice_no']);
$purchaseDate = trim((string) $_POST['purchase_date']);

if (
    $purchaseId === false || $purchaseId < 1 ||
    $saleId === false || $saleId < 1 ||
    $medicineId === false || $medicineId < 1 ||
    $userId === false || $userId < 1 ||
    $quantity === false || $quantity < 1 ||
    $price === false || $price < 0 ||
    $invoiceNo === ''
) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid purchase information'
    ]);
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $purchaseDate);
if (!$date || $date->format('Y-m-d') !== $purchaseDate) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid purchase date'
    ]);
    exit;
}

$data = [    
    'purchase_id' => $purchaseId,
    'sale_id' => $saleId,
    'medicine_id' => $medicineId,
    'user_id' => $userId,
    'return_date' => $returnDate,
    'quantity' => $quantity,
    'price' => number_format($price, 2, '.', ''),
    'subtotal' => number_format($quantity * $price, 2, '.', '')
];

try {
    $cc->conn->begin_transaction();

    $res = $cc->common_insert('purchases', $data);
    if (empty($res['status'])) {
        $cc->conn->rollback();
        echo json_encode($res);
        exit;
    }

    $stockUpdated = $cc->conn->query(
        "UPDATE medicines
         SET stock_qty = stock_qty + $quantity
         WHERE id = $medicineId"
    );

    if (!$stockUpdated || $cc->conn->affected_rows !== 1) {
        $cc->conn->rollback();
        echo json_encode([
            'status' => false,
            'message' => 'Purchase was not saved because medicine stock could not be updated'
        ]);
        exit;
    }

    $cc->conn->commit();
    echo json_encode($res);
} catch (mysqli_sql_exception $exception) {
    $cc->conn->rollback();
    error_log($exception->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Purchase could not be saved. Please check the purchase details and try again.'
    ]);
}
