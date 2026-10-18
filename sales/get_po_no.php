<?php
include '../connection.php';




$sql = "SELECT count(*) as total FROM sales";
$result = $cc->common_query($sql);
$sale_no = '0001';
if($result['status']){
    $sale_no = $result['data'][0]->total + 1;
    $sale_no = str_pad($sale_no, 4, '0', STR_PAD_LEFT);
}
echo json_encode(['sale_no' => $sale_no]);