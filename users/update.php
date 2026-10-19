<?php
include '../connection.php';

$id=$_GET['id'];
$post = $_POST;

$res=$cc->common_update('users',$post,['id'=>$id]);

echo json_encode($res);



