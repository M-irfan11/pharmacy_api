<?php
include '../connection.php';
$result=$cc->common_select('categories');
echo json_encode($result);