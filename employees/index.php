<?php
include '../connection.php';
include '../auth_check.php';

header('Content-Type: application/json; charset=UTF-8');
$result = $cc->conn->query(
    'SELECT id, employee_code, full_name, phone, nid_number, photo_path, designation, department,basic_salary, join_date, status, created_at, updated_at
     FROM employees ORDER BY id DESC'
);
$employees = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => true, 'data' => $employees, 'message' => 'Employee list loaded.']);
