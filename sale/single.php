<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;
$result = $cc->common_select('sales', '*', ['id' => $id]);

echo json_encode($result);
