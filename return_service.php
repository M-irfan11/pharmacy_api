<?php

function return_config(string $type): array
{
    if ($type === 'purchase') {
        return [
            'invoice' => 'purchases',
            'invoice_items' => 'purchase_items',
            'invoice_key' => 'purchase_id',
            'returns' => 'purchase_returns',
            'return_items' => 'purchase_return_items',
            'return_key' => 'purchase_return_id',
            'item_key' => 'purchase_item_id',
            'price_key' => 'unit_cost',
            'direction' => -1,
            'prefix' => 'PR',
        ];
    }
    if ($type === 'sale') {
        return [
            'invoice' => 'sales',
            'invoice_items' => 'sale_items',
            'invoice_key' => 'sale_id',
            'returns' => 'sales_returns',
            'return_items' => 'sales_return_items',
            'return_key' => 'sales_return_id',
            'item_key' => 'sale_item_id',
            'price_key' => 'unit_price',
            'direction' => 1,
            'prefix' => 'SR',
        ];
    }
    throw new InvalidArgumentException('Unknown return type');
}

function return_response(bool $status, string $message, $data = []): array
{
    return ['status' => $status, 'message' => $message, 'data' => $data];
}

function return_positive_id($value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 ? $id : null;
}

function return_payload(): ?array
{
    $raw = $_POST['payload'] ?? null;
    if (!is_string($raw)) {
        return null;
    }
    $payload = json_decode($raw, true);
    return is_array($payload) ? $payload : null;
}

function return_valid_date($value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    return $date !== false && $date->format('Y-m-d') === $value
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function return_money_cents($value): ?int
{
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        return null;
    }
    $value = trim((string) $value);
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $value)) {
        return null;
    }
    [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
    return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
}

function return_money_string(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function return_read_items(mysqli $conn, array $config, int $returnId, bool $lock = false): array
{
    $sql = 'SELECT ri.id, ri.`' . $config['item_key'] . '` AS invoice_item_id, ri.medicine_id, '
        . 'ri.quantity, ri.`' . $config['price_key'] . '` AS unit_price, ri.line_total '
        . 'FROM `' . $config['return_items'] . '` ri WHERE ri.`' . $config['return_key'] . '` = ? ORDER BY ri.id';
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }
    $statement = $conn->prepare($sql);
    $statement->bind_param('i', $returnId);
    $statement->execute();
    $items = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $items;
}

function return_validate_items(mysqli $conn, array $config, int $invoiceId, array $rawItems, ?int $excludeReturnId = null): array
{
    if (!$rawItems || count($rawItems) > 100) {
        throw new DomainException('Add between 1 and 100 return items');
    }
    $normalized = [];
    $seen = [];
    $totalCents = 0;
    foreach ($rawItems as $rawItem) {
        if (!is_array($rawItem)) {
            throw new DomainException('Invalid return item');
        }
        $invoiceItemId = return_positive_id($rawItem['invoice_item_id'] ?? null);
        $quantityValue = $rawItem['quantity'] ?? null;
        $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT);
        if ($invoiceItemId === null || $quantity === false || $quantity < 1 || isset($seen[$invoiceItemId])) {
            throw new DomainException('Each item needs a valid invoice item and positive whole-number quantity');
        }
        $seen[$invoiceItemId] = true;

        $sql = 'SELECT ii.id, ii.medicine_id, ii.quantity AS invoice_quantity, ii.unit_price, '
            . 'COALESCE((SELECT SUM(ri.quantity) FROM `' . $config['return_items'] . '` ri '
            . 'JOIN `' . $config['returns'] . '` r ON r.id = ri.`' . $config['return_key'] . '` '
            . 'WHERE ri.`' . $config['item_key'] . '` = ii.id AND (? = 0 OR r.id <> ?)), 0) AS returned_quantity '
            . 'FROM `' . $config['invoice_items'] . '` ii '
            . 'WHERE ii.id = ? AND ii.`' . $config['invoice_key'] . '` = ? FOR UPDATE';
        $statement = $conn->prepare($sql);
        $excludeId = $excludeReturnId ?? 0;
        $statement->bind_param('iiii', $excludeId, $excludeId, $invoiceItemId, $invoiceId);
        $statement->execute();
        $invoiceItem = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$invoiceItem) {
            throw new DomainException('A selected item does not belong to this invoice');
        }

        $available = (int) $invoiceItem['invoice_quantity'] - (int) $invoiceItem['returned_quantity'];
        if ($quantity > $available) {
            throw new DomainException('Return quantity exceeds the remaining quantity on the invoice');
        }
        $unitCents = return_money_cents($invoiceItem['unit_price']);
        if ($unitCents === null || ($unitCents > 0 && $quantity > intdiv(999999999999, $unitCents))) {
            throw new DomainException('Return item total exceeds the supported amount');
        }
        $lineCents = $quantity * $unitCents;
        if ($totalCents > 999999999999 - $lineCents) {
            throw new DomainException('Return total exceeds the supported amount');
        }
        $totalCents += $lineCents;
        $normalized[] = [
            'invoice_item_id' => $invoiceItemId,
            'medicine_id' => (int) $invoiceItem['medicine_id'],
            'quantity' => $quantity,
            'unit_price' => return_money_string($unitCents),
            'line_total' => return_money_string($lineCents),
        ];
    }
    return ['items' => $normalized, 'total' => return_money_string($totalCents)];
}

function return_apply_stock(mysqli $conn, array $config, array $oldItems, array $newItems): void
{
    $totals = [];
    foreach ([[$oldItems, -1], [$newItems, 1]] as [$items, $multiplier]) {
        foreach ($items as $item) {
            $medicineId = (int) $item['medicine_id'];
            $totals[$medicineId] = ($totals[$medicineId] ?? 0) + ($multiplier * (int) $item['quantity']);
        }
    }
    ksort($totals, SORT_NUMERIC);
    $stocks = [];
    foreach ($totals as $medicineId => $quantityDelta) {
        $statement = $conn->prepare('SELECT stock_qty FROM medicines WHERE id = ? FOR UPDATE');
        $statement->bind_param('i', $medicineId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new DomainException('A medicine linked to this return was not found');
        }
        $stock = (int) $row['stock_qty'] + ($config['direction'] * $quantityDelta);
        if ($stock < 0) {
            throw new DomainException('Insufficient stock to process this purchase return');
        }
        if ($stock > 2147483647) {
            throw new DomainException('Medicine stock exceeds the supported amount');
        }
        $stocks[(int) $medicineId] = $stock;
    }
    $update = $conn->prepare('UPDATE medicines SET stock_qty = ? WHERE id = ?');
    foreach ($stocks as $medicineId => $stock) {
        $update->bind_param('ii', $stock, $medicineId);
        $update->execute();
    }
    $update->close();
}

function return_insert_items(mysqli $conn, array $config, int $returnId, array $items): void
{
    $statement = $conn->prepare(
        'INSERT INTO `' . $config['return_items'] . '` (`' . $config['return_key'] . '`, `' . $config['item_key']
        . '`, medicine_id, quantity, `' . $config['price_key'] . '`, line_total) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $statement->bind_param(
            'iiiiss',
            $returnId,
            $item['invoice_item_id'],
            $item['medicine_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['line_total']
        );
        $statement->execute();
    }
    $statement->close();
}

function return_handle(string $type, string $action, ?int $id = null): array
{
    global $cc;
    $config = return_config($type);
    $conn = $cc->conn;
    $transactionStarted = false;

    try {
        if ($action === 'list') {
            $headers = [];
            $partyJoin = $type === 'purchase'
                ? 'LEFT JOIN suppliers s ON s.id = i.supplier_id'
                : '';
            $partyColumn = $type === 'purchase' ? 's.supplier_name AS party_name' : 'i.customer_name AS party_name';
            $result = $conn->query(
                'SELECT r.*, i.invoice_no, ' . $partyColumn . ' FROM `' . $config['returns'] . '` r '
                . 'JOIN `' . $config['invoice'] . '` i ON i.id = r.`' . $config['invoice_key'] . '` '
                . $partyJoin . ' ORDER BY r.id DESC'
            );
            while ($row = $result->fetch_assoc()) {
                $row['items'] = [];
                $headers[(int) $row['id']] = $row;
            }
            $items = $conn->query(
                'SELECT ri.`' . $config['return_key'] . '` AS return_id, ri.medicine_id, m.medicine_name, '
                . 'ri.quantity, ri.`' . $config['price_key'] . '` AS unit_price, ri.line_total '
                . 'FROM `' . $config['return_items'] . '` ri LEFT JOIN medicines m ON m.id = ri.medicine_id '
                . 'ORDER BY ri.id'
            );
            while ($item = $items->fetch_assoc()) {
                $returnId = (int) $item['return_id'];
                if (isset($headers[$returnId])) {
                    $headers[$returnId]['items'][] = $item;
                }
            }
            return return_response(true, count($headers) ? 'Records found' : 'No returns found', array_values($headers));
        }

        if ($action === 'options') {
            $partyJoin = $type === 'purchase' ? 'LEFT JOIN suppliers s ON s.id = i.supplier_id' : '';
            $partyColumn = $type === 'purchase' ? 's.supplier_name AS party_name' : 'i.customer_name AS party_name';
            $invoices = $conn->query(
                'SELECT i.id, i.invoice_no, i.' . ($type === 'purchase' ? 'purchase_date' : 'sale_date') . ' AS invoice_date, '
                . $partyColumn . ' FROM `' . $config['invoice'] . '` i ' . $partyJoin . ' ORDER BY i.id DESC'
            )->fetch_all(MYSQLI_ASSOC);
            $invoiceId = return_positive_id($_GET['invoice_id'] ?? null);
            $excludeId = return_positive_id($_GET['return_id'] ?? null);
            $lines = [];
            if ($invoiceId !== null) {
                $placeholder = $excludeId ?? 0;
                $sql = 'SELECT ii.id AS invoice_item_id, ii.medicine_id, m.medicine_name, '
                    . 'ii.quantity AS invoice_quantity, ii.unit_price, m.stock_qty, '
                    . 'COALESCE((SELECT SUM(ri.quantity) FROM `' . $config['return_items'] . '` ri '
                    . 'JOIN `' . $config['returns'] . '` r ON r.id = ri.`' . $config['return_key'] . '` '
                    . 'WHERE ri.`' . $config['item_key'] . '` = ii.id AND (? = 0 OR r.id <> ?)), 0) AS returned_quantity '
                    . 'FROM `' . $config['invoice_items'] . '` ii JOIN medicines m ON m.id = ii.medicine_id '
                    . 'WHERE ii.`' . $config['invoice_key'] . '` = ? ORDER BY ii.id';
                $statement = $conn->prepare($sql);
                $statement->bind_param('iii', $placeholder, $placeholder, $invoiceId);
                $statement->execute();
                $lines = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
                $statement->close();
                foreach ($lines as &$line) {
                    $line['available_quantity'] = max(
                        0,
                        (int) $line['invoice_quantity'] - (int) $line['returned_quantity']
                    );
                }
                unset($line);
            }
            return return_response(true, 'Return options loaded', ['invoices' => $invoices, 'items' => $lines]);
        }

        if ($action === 'single') {
            if ($id === null) {
                return return_response(false, 'Invalid return id');
            }
            $statement = $conn->prepare('SELECT * FROM `' . $config['returns'] . '` WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $header = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$header) {
                return return_response(false, 'Return was not found');
            }
            $header['items'] = return_read_items($conn, $config, $id);
            return return_response(true, 'Return found', [$header]);
        }

        if ($action === 'create' || $action === 'update') {
            $payload = return_payload();
            if ($payload === null) {
                return return_response(false, 'Return data could not be read');
            }
            $invoiceId = return_positive_id($payload[$config['invoice_key']] ?? null);
            $reason = $payload['reason'] ?? '';
            $date = $payload['return_date'] ?? date('Y-m-d');
            if ($invoiceId === null || !is_string($reason) || strlen($reason) > 255 || !return_valid_date($date)) {
                return return_response(false, 'Select a valid invoice, return date, and reason (up to 255 characters)');
            }
            if ($action === 'update' && $id === null) {
                return return_response(false, 'Invalid return id');
            }

            $conn->begin_transaction();
            $transactionStarted = true;
            $existing = null;
            $oldItems = [];
            if ($action === 'update') {
                $statement = $conn->prepare('SELECT * FROM `' . $config['returns'] . '` WHERE id = ? FOR UPDATE');
                $statement->bind_param('i', $id);
                $statement->execute();
                $existing = $statement->get_result()->fetch_assoc();
                $statement->close();
                if (!$existing) {
                    throw new DomainException('Return was not found');
                }
                if ((int) $existing[$config['invoice_key']] !== $invoiceId) {
                    throw new DomainException('The original invoice cannot be changed when editing a return');
                }
                $oldItems = return_read_items($conn, $config, $id, true);
            }

            $statement = $conn->prepare('SELECT id FROM `' . $config['invoice'] . '` WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $invoiceId);
            $statement->execute();
            $invoice = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$invoice) {
                throw new DomainException('Invoice was not found');
            }

            $validated = return_validate_items(
                $conn,
                $config,
                $invoiceId,
                $payload['items'] ?? [],
                $action === 'update' ? $id : null
            );
            return_apply_stock($conn, $config, $oldItems, $validated['items']);

            if ($action === 'create') {
                $returnNo = $config['prefix'] . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $statement = $conn->prepare(
                    'INSERT INTO `' . $config['returns'] . '` (`' . $config['invoice_key']
                    . '`, return_no, return_date, total, reason) VALUES (?, ?, ?, ?, ?)'
                );
                $dateTime = $date . ' 00:00:00';
                $statement->bind_param('issss', $invoiceId, $returnNo, $dateTime, $validated['total'], $reason);
                $statement->execute();
                $returnId = (int) $conn->insert_id;
                $statement->close();
            } else {
                $statement = $conn->prepare(
                    'UPDATE `' . $config['returns'] . '` SET return_date = ?, total = ?, reason = ? WHERE id = ?'
                );
                $dateTime = $date . ' 00:00:00';
                $statement->bind_param('sssi', $dateTime, $validated['total'], $reason, $id);
                $statement->execute();
                $statement->close();
                $returnId = $id;
                $statement = $conn->prepare('DELETE FROM `' . $config['return_items'] . '` WHERE `' . $config['return_key'] . '` = ?');
                $statement->bind_param('i', $returnId);
                $statement->execute();
                $statement->close();
            }
            return_insert_items($conn, $config, $returnId, $validated['items']);
            $conn->commit();
            $transactionStarted = false;
            return return_response(true, $action === 'create' ? 'Return created successfully' : 'Return updated successfully', [
                'id' => $returnId,
            ]);
        }

        if ($action === 'delete') {
            if ($id === null) {
                return return_response(false, 'Invalid return id');
            }
            $conn->begin_transaction();
            $transactionStarted = true;
            $statement = $conn->prepare('SELECT * FROM `' . $config['returns'] . '` WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $header = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$header) {
                throw new DomainException('Return was not found');
            }
            $invoiceId = (int) $header[$config['invoice_key']];
            $statement = $conn->prepare('SELECT id FROM `' . $config['invoice'] . '` WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $invoiceId);
            $statement->execute();
            $statement->get_result()->fetch_assoc();
            $statement->close();
            $oldItems = return_read_items($conn, $config, $id, true);
            return_apply_stock($conn, $config, $oldItems, []);
            $statement = $conn->prepare('DELETE FROM `' . $config['return_items'] . '` WHERE `' . $config['return_key'] . '` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->close();
            $statement = $conn->prepare('DELETE FROM `' . $config['returns'] . '` WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->close();
            $conn->commit();
            $transactionStarted = false;
            return return_response(true, 'Return deleted successfully');
        }

        return return_response(false, 'Unsupported return operation');
    } catch (DomainException $error) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        return return_response(false, $error->getMessage());
    } catch (Throwable $error) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('Return API error: ' . $error->getMessage());
        return return_response(false, 'Return could not be processed');
    }
}

function return_endpoint(string $type, string $action): void
{
    $id = return_positive_id($_GET['id'] ?? null);
    $result = return_handle($type, $action, $id);
    echo json_encode($result);
}
