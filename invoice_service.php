<?php

function invoice_config(string $type): array
{
    if ($type === 'sale') {
        return [
            'header' => 'sales',
            'items' => 'sale_items',
            'parent_key' => 'sale_id',
            'date_key' => 'sale_date',
            'extra_key' => 'customer_name',
            'stock_direction' => -1,
        ];
    }

    if ($type === 'purchase') {
        return [
            'header' => 'purchases',
            'items' => 'purchase_items',
            'parent_key' => 'purchase_id',
            'date_key' => 'purchase_date',
            'extra_key' => 'supplier_id',
            'stock_direction' => 1,
        ];
    }

    throw new InvalidArgumentException('Unknown invoice type');
}

function invoice_response(bool $status, string $message, $data = []): array
{
    return ['status' => $status, 'message' => $message, 'data' => $data];
}

function invoice_payload(): ?array
{
    $raw = $_POST['payload'] ?? null;
    if (!is_string($raw)) {
        return null;
    }

    $payload = json_decode($raw, true);
    return is_array($payload) ? $payload : null;
}

function invoice_positive_int($value): ?int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    return $number !== false && $number > 0 ? $number : null;
}

function invoice_money_cents($value): ?int
{
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        return null;
    }

    $value = trim((string) $value);
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) {
        return null;
    }

    [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
    $fraction = str_pad($fraction, 2, '0');
    return ((int) $whole * 100) + (int) $fraction;
}

function invoice_money_string(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function invoice_valid_date($value): bool
{
    if (!is_string($value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    return $date !== false
        && $date->format('Y-m-d') === $value
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function invoice_validate_payload(string $type, array $payload): array
{
    $config = invoice_config($type);
    $invoiceNoValue = $payload['invoice_no'] ?? null;
    $userId = invoice_positive_int($payload['user_id'] ?? null);
    $date = $payload[$config['date_key']] ?? null;
    $items = $payload['items'] ?? null;

    if (!is_string($invoiceNoValue) && !is_int($invoiceNoValue)) {
        throw new DomainException('Invoice number, user, or date is invalid');
    }
    $invoiceNo = trim((string) $invoiceNoValue);
    if ($invoiceNo === '' || strlen($invoiceNo) > 50 || $userId === null || !invoice_valid_date($date)) {
        throw new DomainException('Invoice number, user, or date is invalid');
    }
    if (!is_array($items) || count($items) === 0 || count($items) > 100) {
        throw new DomainException('Add between 1 and 100 medicine items');
    }

    $extra = null;
    if ($type === 'sale') {
        $customerName = $payload['customer_name'] ?? null;
        if (!is_string($customerName)) {
            throw new DomainException('Customer name is required and must be 150 characters or fewer');
        }
        $extra = trim($customerName);
        if ($extra === '' || strlen($extra) > 150) {
            throw new DomainException('Customer name is required and must be 150 characters or fewer');
        }
    } else {
        $extra = invoice_positive_int($payload['supplier_id'] ?? null);
        if ($extra === null) {
            throw new DomainException('Select a valid supplier');
        }
    }

    $normalizedItems = [];
    $totalCents = 0;
    foreach ($items as $item) {
        if (!is_array($item)) {
            throw new DomainException('Invalid medicine item');
        }
        $medicineId = invoice_positive_int($item['medicine_id'] ?? null);
        $quantity = invoice_positive_int($item['quantity'] ?? null);
        $unitCents = invoice_money_cents($item['unit_price'] ?? null);
        if ($medicineId === null || $quantity === null || $unitCents === null) {
            throw new DomainException('Each item needs a valid medicine, positive quantity, and non-negative price');
        }
        if ($unitCents > 0 && $quantity > intdiv(999999999999, $unitCents)) {
            throw new DomainException('An item total exceeds the supported amount');
        }

        $lineCents = $unitCents * $quantity;
        if ($totalCents > 999999999999 - $lineCents) {
            throw new DomainException('Invoice total exceeds the supported amount');
        }
        $totalCents += $lineCents;
        $normalizedItems[] = [
            'medicine_id' => $medicineId,
            'quantity' => $quantity,
            'unit_price' => invoice_money_string($unitCents),
            'line_total' => invoice_money_string($lineCents),
        ];
    }

    return [
        'invoice_no' => $invoiceNo,
        'user_id' => $userId,
        'date' => $date,
        'extra' => $extra,
        'items' => $normalizedItems,
        'total_amount' => invoice_money_string($totalCents),
    ];
}

function invoice_read_header(mysqli $conn, array $config, int $id, bool $lock = false): ?array
{
    $sql = 'SELECT * FROM `' . $config['header'] . '` WHERE id = ?' . ($lock ? ' FOR UPDATE' : '');
    $statement = $conn->prepare($sql);
    $statement->bind_param('i', $id);
    $statement->execute();
    $header = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $header ?: null;
}

function invoice_read_items(mysqli $conn, array $config, int $id, bool $lock = false): array
{
    $sql = 'SELECT i.id, i.`' . $config['parent_key'] . '`, i.medicine_id, i.quantity, '
        . 'i.unit_price, i.line_total, m.medicine_name '
        . 'FROM `' . $config['items'] . '` i '
        . 'LEFT JOIN medicines m ON m.id = i.medicine_id '
        . 'WHERE i.`' . $config['parent_key'] . '` = ? ORDER BY i.id'
        . ($lock ? ' FOR UPDATE' : '');
    $statement = $conn->prepare($sql);
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $statement->close();
    return $items;
}

function invoice_insert_items(mysqli $conn, array $config, int $id, array $items): void
{
    $sql = 'INSERT INTO `' . $config['items'] . '` '
        . '(`' . $config['parent_key'] . '`, medicine_id, quantity, unit_price, line_total) '
        . 'VALUES (?, ?, ?, ?, ?)';
    $statement = $conn->prepare($sql);
    foreach ($items as $item) {
        $medicineId = $item['medicine_id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $lineTotal = $item['line_total'];
        $statement->bind_param('iiiss', $id, $medicineId, $quantity, $unitPrice, $lineTotal);
        $statement->execute();
    }
    $statement->close();
}

function invoice_stock_totals(array $items): array
{
    $totals = [];
    foreach ($items as $item) {
        $medicineId = (int) $item['medicine_id'];
        $quantity = (int) $item['quantity'];
        $totals[$medicineId] = ($totals[$medicineId] ?? 0) + $quantity;
        if ($totals[$medicineId] > 2147483647) {
            throw new DomainException('Combined quantity exceeds the supported stock amount');
        }
    }
    ksort($totals, SORT_NUMERIC);
    return $totals;
}

function invoice_adjust_stock(mysqli $conn, array $config, array $oldItems, array $newItems): void
{
    $oldTotals = invoice_stock_totals($oldItems);
    $newTotals = invoice_stock_totals($newItems);
    $medicineIds = array_values(array_unique(array_merge(array_keys($oldTotals), array_keys($newTotals))));
    sort($medicineIds, SORT_NUMERIC);
    $newStocks = [];

    foreach ($medicineIds as $medicineId) {
        $statement = $conn->prepare('SELECT stock_qty FROM medicines WHERE id = ? FOR UPDATE');
        $statement->bind_param('i', $medicineId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new DomainException('A selected medicine was not found');
        }

        $stock = (int) $row['stock_qty'];
        $oldQuantity = $oldTotals[$medicineId] ?? 0;
        $newQuantity = $newTotals[$medicineId] ?? 0;
        $adjusted = $stock + ($config['stock_direction'] * ($newQuantity - $oldQuantity));
        if ($adjusted < 0) {
            throw new DomainException('Insufficient stock for one or more medicines');
        }
        if ($adjusted > 2147483647) {
            throw new DomainException('Medicine stock exceeds the supported amount');
        }
        $newStocks[$medicineId] = $adjusted;
    }

    $update = $conn->prepare('UPDATE medicines SET stock_qty = ? WHERE id = ?');
    foreach ($newStocks as $medicineId => $stock) {
        if ($stock !== (int) $stock) {
            throw new DomainException('Invalid stock amount');
        }
        $update->bind_param('ii', $stock, $medicineId);
        $update->execute();
    }
    $update->close();
}

function invoice_account_voucher_config(string $type): array
{
    return $type === 'sale'
        ? ['table' => 'receive_vouchers', 'source_type' => 'sales_invoice']
        : ['table' => 'payment_vouchers', 'source_type' => 'purchase_invoice'];
}

function invoice_has_account_voucher(mysqli $conn, string $type, int $invoiceId): bool
{
    $voucherConfig = invoice_account_voucher_config($type);
    $statement = $conn->prepare(
        'SELECT id FROM `' . $voucherConfig['table'] . '`
         WHERE source_type = ? AND source_id = ? AND status = \'posted\'
           AND deleted_at IS NULL LIMIT 1'
    );
    $statement->bind_param('si', $voucherConfig['source_type'], $invoiceId);
    $statement->execute();
    $exists = $statement->get_result()->fetch_assoc() !== null;
    $statement->close();
    return $exists;
}

function invoice_handle(string $type, string $action, ?int $id = null): array
{
    global $cc;
    $config = invoice_config($type);
    $conn = $cc->conn;
    $transactionStarted = false;

    try {
        if ($action === 'list') {
            $headers = [];
            $voucherConfig = invoice_account_voucher_config($type);
            $voucherByInvoice = [];
            $voucherStatement = $conn->prepare(
                'SELECT source_id, id, voucher_no, status
                 FROM `' . $voucherConfig['table'] . '`
                 WHERE source_type = ? AND source_id IS NOT NULL AND deleted_at IS NULL'
            );
            $voucherStatement->bind_param('s', $voucherConfig['source_type']);
            $voucherStatement->execute();
            $voucherResult = $voucherStatement->get_result();
            while ($voucher = $voucherResult->fetch_assoc()) {
                $voucherByInvoice[(int) $voucher['source_id']] = [
                    'id' => (int) $voucher['id'],
                    'voucher_no' => $voucher['voucher_no'],
                    'status' => $voucher['status'],
                ];
            }
            $voucherStatement->close();
            $result = $conn->query('SELECT * FROM `' . $config['header'] . '` ORDER BY id DESC');
            while ($row = $result->fetch_assoc()) {
                $row['items'] = [];
                $row['account_voucher'] = $voucherByInvoice[(int) $row['id']] ?? null;
                $headers[(int) $row['id']] = $row;
            }
            $sql = 'SELECT i.id, i.`' . $config['parent_key'] . '`, i.medicine_id, i.quantity, '
                . 'i.unit_price, i.line_total, m.medicine_name '
                . 'FROM `' . $config['items'] . '` i '
                . 'LEFT JOIN medicines m ON m.id = i.medicine_id ORDER BY i.id';
            $items = $conn->query($sql);
            while ($item = $items->fetch_assoc()) {
                $parentId = (int) $item[$config['parent_key']];
                if (isset($headers[$parentId])) {
                    $headers[$parentId]['items'][] = $item;
                }
            }
            return invoice_response(true, count($headers) ? 'Records found' : 'No records found', array_values($headers));
        }

        if ($action === 'single') {
            if ($id === null) {
                return invoice_response(false, 'Invalid invoice id');
            }
            $header = invoice_read_header($conn, $config, $id);
            if (!$header) {
                return invoice_response(false, 'Invoice was not found');
            }
            $header['items'] = invoice_read_items($conn, $config, $id);
            return invoice_response(true, 'Record found', [$header]);
        }

        if ($action === 'create') {
            $payload = invoice_payload();
            if ($payload === null) {
                return invoice_response(false, 'Invoice data could not be read');
            }
            $data = invoice_validate_payload($type, $payload);
            $conn->begin_transaction();
            $transactionStarted = true;

            if ($type === 'sale') {
                $statement = $conn->prepare(
                    'INSERT INTO sales (invoice_no, customer_name, user_id, sale_date, total_amount) VALUES (?, ?, ?, ?, ?)'
                );
                $statement->bind_param('ssiss', $data['invoice_no'], $data['extra'], $data['user_id'], $data['date'], $data['total_amount']);
            } else {
                $statement = $conn->prepare(
                    'INSERT INTO purchases (invoice_no, supplier_id, user_id, purchase_date, total_amount) VALUES (?, ?, ?, ?, ?)'
                );
                $statement->bind_param('siiss', $data['invoice_no'], $data['extra'], $data['user_id'], $data['date'], $data['total_amount']);
            }
            $statement->execute();
            $invoiceId = (int) $conn->insert_id;
            $statement->close();

            invoice_adjust_stock($conn, $config, [], $data['items']);
            invoice_insert_items($conn, $config, $invoiceId, $data['items']);
            $conn->commit();
            $transactionStarted = false;
            return invoice_response(true, 'Invoice created successfully', ['id' => $invoiceId]);
        }

        if ($action === 'update') {
            if ($id === null) {
                return invoice_response(false, 'Invalid invoice id');
            }
            $payload = invoice_payload();
            if ($payload === null) {
                return invoice_response(false, 'Invoice data could not be read');
            }
            $data = invoice_validate_payload($type, $payload);
            $conn->begin_transaction();
            $transactionStarted = true;
            $header = invoice_read_header($conn, $config, $id, true);
            if (!$header) {
                $conn->rollback();
                $transactionStarted = false;
                return invoice_response(false, 'Invoice was not found');
            }
            if (invoice_has_account_voucher($conn, $type, $id)) {
                throw new DomainException('This invoice has a posted Accounts voucher and can no longer be edited');
            }
            $oldItems = invoice_read_items($conn, $config, $id, true);
            invoice_adjust_stock($conn, $config, $oldItems, $data['items']);

            if ($type === 'sale') {
                $statement = $conn->prepare(
                    'UPDATE sales SET invoice_no = ?, customer_name = ?, user_id = ?, sale_date = ?, total_amount = ? WHERE id = ?'
                );
                $statement->bind_param('ssissi', $data['invoice_no'], $data['extra'], $data['user_id'], $data['date'], $data['total_amount'], $id);
            } else {
                $statement = $conn->prepare(
                    'UPDATE purchases SET invoice_no = ?, supplier_id = ?, user_id = ?, purchase_date = ?, total_amount = ? WHERE id = ?'
                );
                $statement->bind_param('siissi', $data['invoice_no'], $data['extra'], $data['user_id'], $data['date'], $data['total_amount'], $id);
            }
            $statement->execute();
            $statement->close();

            $delete = $conn->prepare('DELETE FROM `' . $config['items'] . '` WHERE `' . $config['parent_key'] . '` = ?');
            $delete->bind_param('i', $id);
            $delete->execute();
            $delete->close();
            invoice_insert_items($conn, $config, $id, $data['items']);
            $conn->commit();
            $transactionStarted = false;
            return invoice_response(true, 'Invoice updated successfully', ['id' => $id]);
        }

        if ($action === 'delete') {
            if ($id === null) {
                return invoice_response(false, 'Invalid invoice id');
            }
            $conn->begin_transaction();
            $transactionStarted = true;
            $header = invoice_read_header($conn, $config, $id, true);
            if (!$header) {
                $conn->rollback();
                $transactionStarted = false;
                return invoice_response(false, 'Invoice was not found');
            }
            if (invoice_has_account_voucher($conn, $type, $id)) {
                throw new DomainException('This invoice has a posted Accounts voucher and can no longer be deleted');
            }
            $oldItems = invoice_read_items($conn, $config, $id, true);
            invoice_adjust_stock($conn, $config, $oldItems, []);
            $delete = $conn->prepare('DELETE FROM `' . $config['header'] . '` WHERE id = ?');
            $delete->bind_param('i', $id);
            $delete->execute();
            $delete->close();
            $conn->commit();
            $transactionStarted = false;
            return invoice_response(true, 'Invoice deleted successfully');
        }

        return invoice_response(false, 'Unsupported invoice operation');
    } catch (DomainException $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        return invoice_response(false, $exception->getMessage());
    } catch (mysqli_sql_exception $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log($exception->getMessage());
        return invoice_response(false, 'Invoice operation failed. Check the invoice number and related database records.');
    }
}

function invoice_endpoint(string $type, string $action): void
{
    $id = null;
    if (in_array($action, ['single', 'update', 'delete'], true)) {
        $id = invoice_positive_int($_GET['id'] ?? null);
    }
    echo json_encode(invoice_handle($type, $action, $id));
}
