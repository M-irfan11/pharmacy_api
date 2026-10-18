<?php
include '../connection.php';
$id=$_GET['id'];

$result=$cc->common_select('medicines','*',['id'=>$id]);

echo json_encode($result);