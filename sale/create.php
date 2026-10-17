<?php
include '../connection.php';


$required = [
    'invoice_no',
    'customer_name',
    'medicine_id',
    'user_id',
    'sale_date',
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

$customer_name = trim((string) $_POST['customer_name']);
$medicineId = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
$userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
$invoiceNo = trim((string) $_POST['invoice_no']);
$saleDate = trim((string) $_POST['sale_date']);

if (
    $customer_name === '' ||
    $medicineId === false || $medicineId < 1 ||
    $userId === false || $userId < 1 ||
    $quantity === false || $quantity < 1 ||
    $price === false || $price < 0 ||
    $invoiceNo === ''
) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid sale information'
    ]);
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $saleDate);
if (!$date || $date->format('Y-m-d') !== $saleDate) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid sale date'
    ]);
    exit;
}

$data = [
    'invoice_no' => $invoiceNo,
    'customer_name' => $customer_name,
    'medicine_id' => $medicineId,
    'user_id' => $userId,
    'sale_date' => $saleDate,
    'quantity' => $quantity,
    'price' => number_format($price, 2, '.', ''),
    'subtotal' => number_format($quantity * $price, 2, '.', '')
];

try {
    $cc->conn->begin_transaction();

    $res = $cc->common_insert('sales', $data);
    // if (empty($res['status'])) {
    //     $cc->conn->rollback();
    //     echo json_encode($res);
    //     exit;
    // }

    $stockUpdated = $cc->conn->query(
        "UPDATE medicines
         SET stock_qty = stock_qty - $quantity
         WHERE id = $medicineId"
    );

    if (!$stockUpdated || $cc->conn->affected_rows !== 1) {
        $cc->conn->rollback();
        echo json_encode([
            'status' => false,
            'message' => 'Sale was not saved because medicine stock could not be updated'
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
        'message' => 'Sale could not be saved. Please check the sale details and try again.'
    ]);
}
