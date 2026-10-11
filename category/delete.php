<?php
include '../connection.php';
include '../auth_check.php';

$id=$_GET['id'];
$get_res=$cc->common_select('categories','*',['id'=>$id]);
if($get_res['status'] && count($get_res['data'])>0){
    $category=$get_res['data'][0];
    if(file_exists('../'.$category->image)){
        unlink('../'.$category->image);
    }
}
$res=$cc->common_delete('categories',['id'=>$id]);

echo json_encode($res);