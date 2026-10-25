<?php
include '../connection.php';
include '../auth_check.php';

header('Content-Type: application/json; charset=UTF-8');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid employee id.']);
    exit;
}

$statement = $cc->conn->prepare(
    'SELECT id, employee_code, full_name, phone, nid_number, photo_path, designation, department, basic_salary, join_date, status, created_at, updated_at
     FROM employees WHERE id = ?'
);
$statement->bind_param('i', $id);
$statement->execute();
$employee = $statement->get_result()->fetch_assoc();
if (!$employee) {
    http_response_code(404);
    echo json_encode(['status' => false, 'data' => null, 'message' => 'Employee not found.']);
    exit;
}

echo json_encode(['status' => true, 'data' => $employee, 'message' => 'Employee found.']);