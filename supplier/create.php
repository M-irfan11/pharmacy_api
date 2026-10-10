<?php
include '../connection.php';
$data = json_decode(file_get_contents('php://input'), true);

$res=[];
if($data['email']){
    $res=$cc->common_insert('suppliers', $data);
}
echo json_encode($res);
