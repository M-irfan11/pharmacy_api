<?php
include '../connection.php';
$result=$cc->common_select('users');
echo json_encode($result);