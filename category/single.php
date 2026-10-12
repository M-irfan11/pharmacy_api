<?php
include '../connection.php';
include '../auth_check.php';

$id=$_GET['id'];

$result=$cc->common_select('categories','*',['id'=>$id]);

echo json_encode($result);