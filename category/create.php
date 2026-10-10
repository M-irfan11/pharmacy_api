<?php
include '../connection.php';
$data = json_decode(file_get_contents('php://input'), true);
$res=[];
if($data['name']){
    $res=$cc->common_insert('categories', $data);
}
echo json_encode($res);
