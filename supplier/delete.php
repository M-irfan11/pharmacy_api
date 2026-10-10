<?php
include '../connection.php';
$id=$_GET['id'];
$res=$cc->common_delete('suppliers',['id'=>$id]);

echo json_encode($res);