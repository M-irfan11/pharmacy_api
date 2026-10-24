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

$statement = $cc->conn->prepare('SELECT COUNT(*) FROM payroll WHERE employee_id = ?');
$statement->bind_param('i', $id);
$statement->execute();
$statement->bind_result($payrollCount);
$statement->fetch();
$statement->close();
if ($payrollCount > 0) {
    http_response_code(409);
    echo json_encode(['status' => false, 'message' => 'Payroll records exist; this employee cannot be deleted.']);
    exit;
}

$statement = $cc->conn->prepare('DELETE FROM employees WHERE id = ?');
$statement->bind_param('i', $id);
$statement->execute();
if ($statement->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => 'Employee not found.']);
    exit;
}
echo json_encode(['status' => true, 'data' => $statement->affected_rows, 'message' => 'Employee deleted.']);