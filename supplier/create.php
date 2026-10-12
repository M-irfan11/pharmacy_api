<?php
include '../connection.php';


$post = json_decode(file_get_contents('php://input'), true);

$res=[];
if($post['email']){
    $res=$cc->common_insert('suppliers', $post);
}
echo json_encode($res);



// include '../connection.php';

// $post = $_POST;

// $res = [];
// if (!empty($post['email'])) {
//     $res = $cc->common_insert('suppliers', $post);
// }
// echo json_encode($res);
