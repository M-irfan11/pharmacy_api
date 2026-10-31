<?php
include '../connection.php';

function report_date_valid($value)
{
    if (!is_string($value) || $value === '') return true;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    return $date !== false && $date->format('Y-m-d') === $value
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function report_fetch_all(mysqli $conn, string $sql, array $params = [], string $types = ''): array
{
    if (!$params) return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $statement = $conn->prepare($sql);
    $statement->bind_param($types, ...array_values($params));
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}

function report_invoice_rows(mysqli $conn, string $type, string $fromDate, string $toDate, string $search): array
{
    $header = $type === 'purchase' ? 'purchases' : 'sales';
    $itemTable = $type === 'purchase' ? 'purchase_items' : 'sale_items';
    $parentKey = $type === 'purchase' ? 'purchase_id' : 'sale_id';
    $dateKey = $type === 'purchase' ? 'purchase_date' : 'sale_date';

    $sql = "SELECT h.id, h.invoice_no, h.`$dateKey` AS report_date, h.total_amount, "
        . ($type === 'purchase'
            ? "s.supplier_name AS party_name, "
            : "h.customer_name AS party_name, ")
        . "i.id AS item_id, i.medicine_id, m.medicine_name, i.quantity, i.unit_price, i.line_total "
        . "FROM `$header` h "
        . ($type === 'purchase' ? 'LEFT JOIN suppliers s ON s.id = h.supplier_id ' : '')
        . "LEFT JOIN `$itemTable` i ON i.`$parentKey` = h.id "
        . "LEFT JOIN medicines m ON m.id = i.medicine_id WHERE 1=1";

    $params = [];
    $types = '';
    if ($fromDate !== '') { $sql .= " AND h.`$dateKey` >= ?"; $params[] = $fromDate; $types .= 's'; }
    if ($toDate !== '') { $sql .= " AND h.`$dateKey` <= ?"; $params[] = $toDate; $types .= 's'; }
    if ($search !== '') {
        $sql .= ' AND (h.invoice_no LIKE ? OR m.medicine_name LIKE ? OR '
            . ($type === 'purchase' ? 's.supplier_name' : 'h.customer_name') . ' LIKE ?)';
        $needle = '%' . $search . '%';
        $params[] = $needle; $params[] = $needle; $params[] = $needle; $types .= 'sss';
    }
    $sql .= ' ORDER BY h.id DESC, i.id ASC';

    return report_fetch_all($conn, $sql, $params, $types);
}

function report_group_invoices(array $rows, string $type): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'id' => $id,
                'invoice_no' => $row['invoice_no'],
                'purchase_date' => $type === 'purchase' ? $row['report_date'] : null,
                'sale_date' => $type === 'sale' ? $row['report_date'] : null,
                'supplier_id' => null,
                'customer_name' => $type === 'sale' ? $row['party_name'] : '',
                'total_amount' => $row['total_amount'],
                'items' => [],
            ];
        }
        if (!empty($row['item_id'])) {
            $grouped[$id]['items'][] = [
                'id' => (int) $row['item_id'],
                'medicine_id' => (int) $row['medicine_id'],
                'medicine_name' => $row['medicine_name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => $row['unit_price'],
                'line_total' => $row['line_total'],
            ];
        }
    }
    return array_values($grouped);
}

function report_group_returns(array $rows, string $type): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $id = (int) $row['return_id'];
        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'id' => $id,
                'return_no' => $row['return_no'],
                'return_date' => $row['report_date'],
                'invoice_no' => $row['invoice_no'],
                'party_name' => $row['party_name'],
                'total' => $row['total'],
                'reason' => $row['reason'],
                'items' => [],
            ];
        }
        if (!empty($row['item_id'])) {
            $grouped[$id]['items'][] = [
                'id' => (int) $row['item_id'],
                'medicine_id' => (int) $row['medicine_id'],
                'medicine_name' => $row['medicine_name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => $row['unit_price'],
                'line_total' => $row['line_total'],
            ];
        }
    }
    return array_values($grouped);
}

function report_return_rows(mysqli $conn, string $type, string $fromDate, string $toDate, string $search): array
{
    if ($type === 'purchase') {
        $returnTable = 'purchase_returns'; $returnItemTable = 'purchase_return_items';
        $returnKey = 'purchase_return_id'; $invoiceKey = 'purchase_id';
        $itemKey = 'purchase_item_id'; $invoiceTable = 'purchases'; $dateKey = 'return_date'; $priceKey = 'unit_cost';
        $partyJoin = 'LEFT JOIN suppliers s ON s.id = h.supplier_id'; $partyColumn = 's.supplier_name';
    } else {
        $returnTable = 'sales_returns'; $returnItemTable = 'sales_return_items';
        $returnKey = 'sales_return_id'; $invoiceKey = 'sale_id';
        $itemKey = 'sale_item_id'; $invoiceTable = 'sales'; $dateKey = 'return_date'; $priceKey = 'unit_price';
        $partyJoin = ''; $partyColumn = 'h.customer_name';
    }

    $sql = "SELECT r.id AS return_id, r.return_no, r.`$dateKey` AS report_date, r.total, r.reason,
                   r.`$invoiceKey` AS invoice_id, h.invoice_no, $partyColumn AS party_name,
                   ri.id AS item_id, ri.medicine_id, m.medicine_name, ri.quantity,
                   ri.`$priceKey` AS unit_price, ri.line_total
            FROM `$returnTable` r
            JOIN `$invoiceTable` h ON h.id = r.`$invoiceKey`
            $partyJoin
            LEFT JOIN `$returnItemTable` ri ON ri.`$returnKey` = r.id
            LEFT JOIN medicines m ON m.id = ri.medicine_id
            WHERE 1=1";

    $params = []; $types = '';
    if ($fromDate !== '') { $sql .= " AND r.`$dateKey` >= ?"; $params[] = $fromDate; $types .= 's'; }
    if ($toDate !== '') { $sql .= " AND r.`$dateKey` <= ?"; $params[] = $toDate; $types .= 's'; }
    if ($search !== '') {
        $sql .= ' AND (r.return_no LIKE ? OR h.invoice_no LIKE ? OR m.medicine_name LIKE ? OR '
            . $partyColumn . ' LIKE ?)';
        $needle = '%' . $search . '%'; $params[] = $needle; $params[] = $needle; $params[] = $needle; $params[] = $needle; $types .= 'ssss';
    }
    $sql .= ' ORDER BY r.id DESC, ri.id ASC';
    return report_fetch_all($conn, $sql, $params, $types);
}

$action = $_GET['action'] ?? 'all';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$search = trim($_GET['search'] ?? '');

if (!report_date_valid($fromDate) || !report_date_valid($toDate)) {
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => 'Invalid date filter']);
    exit;
}

try {
    $conn = $cc->conn;
    $data = [];
    if (in_array($action, ['all', 'purchase'], true)) {
        $data['purchases'] = report_group_invoices(report_invoice_rows($conn, 'purchase', $fromDate, $toDate, $search), 'purchase');
    }
    if (in_array($action, ['all', 'sale'], true)) {
        $data['sales'] = report_group_invoices(report_invoice_rows($conn, 'sale', $fromDate, $toDate, $search), 'sale');
    }
    if (in_array($action, ['all', 'return'], true)) {
        $data['purchase_returns'] = report_group_returns(report_return_rows($conn, 'purchase', $fromDate, $toDate, $search), 'purchase');
        $data['sale_returns'] = report_group_returns(report_return_rows($conn, 'sale', $fromDate, $toDate, $search), 'sale');
    }
    if (in_array($action, ['all', 'stock'], true)) {
        $stockSql = 'SELECT id, medicine_name, batch_no, purchase_price, selling_price, stock_qty, expiry_date FROM medicines WHERE deleted_at IS NULL';
        $params = []; $types = '';
        if ($search !== '') { $stockSql .= ' AND (medicine_name LIKE ? OR batch_no LIKE ?)'; $needle = '%' . $search . '%'; $params = [$needle, $needle]; $types = 'ss'; }
        $stockSql .= ' ORDER BY medicine_name ASC';
        $data['medicines'] = report_fetch_all($conn, $stockSql, $params, $types);
    }
    echo json_encode(['status' => true, 'message' => 'Report data loaded', 'data' => $data]);
} catch (Throwable $error) {
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode(['status' => false, 'message' => 'Report data could not be loaded', 'data' => []]);
}
