<?php
include '../connection.php';

$conn = $cc->conn;

try {
    $summaryResult = $conn->query(
        'SELECT '
        . '(SELECT COUNT(*) FROM medicines WHERE deleted_at IS NULL) AS total_medicines, '
        . '(SELECT COALESCE(SUM(total_amount), 0) FROM sales) AS total_sales, '
        . '(SELECT COALESCE(SUM(total_amount), 0) FROM purchases) AS total_purchase, '
        . '(SELECT COUNT(*) FROM medicines WHERE deleted_at IS NULL AND stock_qty <= 10) AS low_stock_count'
    );
    $summary = $summaryResult->fetch_assoc();

    $topSellingResult = $conn->query(
        'SELECT m.medicine_name AS name, COALESCE(sold.quantity, 0) AS quantity '
        . 'FROM medicines m '
        . 'LEFT JOIN ('
        . 'SELECT si.medicine_id, SUM(si.quantity) AS quantity '
        . 'FROM sale_items si '
        . 'INNER JOIN sales s ON s.id = si.sale_id '
        . 'WHERE s.sale_date >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\') '
        . 'AND s.sale_date < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY) '
        . 'GROUP BY si.medicine_id'
        . ') sold ON sold.medicine_id = m.id '
        . 'WHERE m.deleted_at IS NULL '
        . 'ORDER BY quantity DESC, m.medicine_name ASC'
    );

    $topSelling = [];
    $maxQuantity = 0;
    while ($row = $topSellingResult->fetch_assoc()) {
        $row['quantity'] = (int) $row['quantity'];
        $maxQuantity = max($maxQuantity, $row['quantity']);
        $topSelling[] = $row;
    }

    foreach ($topSelling as &$medicine) {
        $medicine['percent'] = $maxQuantity > 0
            ? (int) round(($medicine['quantity'] / $maxQuantity) * 100)
            : 0;
    }
    unset($medicine);

    echo json_encode([
        'status' => true,
        'message' => 'Dashboard summary loaded',
        'data' => [
            'summary' => $summary,
            'top_selling' => $topSelling,
        ],
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Dashboard data could not be loaded',
        'data' => [],
    ]);
}
