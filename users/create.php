<?php
// include '../connection.php';

// $res=[];
// if($_POST['email']){
//     $res=$cc->common_insert('suppliers', $_POST);
// }
// echo json_encode($res);



include '../connection.php';

$post = $_POST;

$res = [];
if (!empty($post['name'])) {
    $res = $cc->common_insert('users', $post);
}
echo json_encode($res);
