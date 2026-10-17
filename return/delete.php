<?php
include '../connection.php';

$id = $_GET['id'] ?? 0;


$res = $cc->common_update('purchases', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);

echo json_encode($res);
