<?php
include '../connection.php';
$id=$_GET['id'];
$res=$cc->common_delete('users',['id'=>$id]);

echo json_encode($res);