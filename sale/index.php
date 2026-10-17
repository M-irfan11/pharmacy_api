<?php
include '../connection.php';

$result = $cc->common_select('sales');


// if (!empty($result['data']) && is_array($result['data'])) {
//     $result['data'] = array_values(array_filter($result['data'], function ($row) {
//         return $row->deleted_at === null;
//     }));
// }

echo json_encode($result);
