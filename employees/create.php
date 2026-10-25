<?php
include '../connection.php';
include '../auth_check.php';

header('Content-Type: application/json; charset=UTF-8');

foreach (['employee_code', 'full_name', 'phone', 'nid_number'] as $field) {
    if (trim($_POST[$field] ?? '') === '') {
        http_response_code(422);
        echo json_encode(['status' => false, 'message' => $field . ' is required.']);
        exit;
    }
}

if (isset($_POST['status']) && !in_array($_POST['status'], ['Active', 'Inactive'], true)) {
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => 'Status must be Active or Inactive.']);
    exit;
}

$photo = $_FILES['photo'] ?? $_FILES['image'] ?? null;
$photoPath = null;
if ($photo && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
    $imageInfo = $photo['error'] === UPLOAD_ERR_OK ? getimagesize($photo['tmp_name']) : false;
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!$imageInfo || $photo['size'] > 5 * 1024 * 1024 || !isset($extensions[$imageInfo['mime']])) {
        http_response_code(422);
        echo json_encode(['status' => false, 'message' => 'Upload a JPG, PNG, GIF or WEBP image up to 5 MB.']);
        exit;
    }
    $uploadDir = __DIR__ . '/../images/employee/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Could not create employee photo directory.']);
        exit;
    }
    $fileName = bin2hex(random_bytes(12)) . '.' . $extensions[$imageInfo['mime']];
    if (!move_uploaded_file($photo['tmp_name'], $uploadDir . $fileName)) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Could not save employee photo.']);
        exit;
    }
    $photoPath = 'images/employee/' . $fileName;
}

$employeeCode = trim($_POST['employee_code']);
$fullName = trim($_POST['full_name']);
$phone = trim($_POST['phone']);
$nidNumber = trim($_POST['nid_number']);
$designation = trim($_POST['designation'] ?? '') ?: null;
$basicSalary = trim($_POST['basic_salary'] ?? '') ?: null;
$joinDate = trim($_POST['join_date'] ?? '') ?: null;
$status = $_POST['status'] ?? 'Active';

try {
    $statement = $cc->conn->prepare(
        'INSERT INTO employees (employee_code, full_name, phone, nid_number, photo_path, designation, basic_salary, join_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'sssssssss',
        $employeeCode,
        $fullName,
        $phone,
        $nidNumber,
        $photoPath,
        $designation,
        $basicSalary,
        $joinDate,
        $status
    );
    $statement->execute();
    echo json_encode(['status' => true, 'data' => $cc->conn->insert_id, 'message' => 'Employee created.']);
} catch (mysqli_sql_exception $error) {
    http_response_code($error->getCode() === 1062 ? 409 : 500);
    echo json_encode([
        'status' => false,
        'message' => $error->getCode() === 1062
            ? 'Employee code or NID number already exists.'
            : 'Could not create employee.',
    ]);
}
