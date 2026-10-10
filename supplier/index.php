<?php
include '../connection.php';
$result=$cc->common_select('suppliers');
echo json_encode($result);