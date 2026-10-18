<?php
include '../connection.php';




$sql = "SELECT count(*) as total FROM purchases";
$result = $cc->common_query($sql);
$pur_no = '0001';
if($result['status']){
    $pur_no = $result['data'][0]->total + 1;
    $pur_no = str_pad($pur_no, 4, '0', STR_PAD_LEFT);
}
echo json_encode(['pur_no' => $pur_no]);