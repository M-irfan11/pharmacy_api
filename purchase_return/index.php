<?php
include '../connection.php';
$result=$cc->common_select('medicines');
echo json_encode($result);