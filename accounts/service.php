<?php
if (!defined('ACCOUNTS_API_ENTRY')) {
    http_response_code(404);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../connection.php';

function accounts_response(bool $status, string $message, $data = [], int $httpStatus = 0): void
{
    http_response_code($httpStatus ?: ($status ? 200 : 422));
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

function accounts_user_id(mysqli $conn): int
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!is_string($authorization) || !preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
        http_response_code(401);
        accounts_response(false, 'Authentication required', [], 401);
    }

    $token = $matches[1];
    $statement = $conn->prepare(
        'SELECT id FROM users WHERE remember_token = ? AND status = 1 AND deleted_at IS NULL LIMIT 1'
    );
    $statement->bind_param('s', $token);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$user) {
        http_response_code(401);
        accounts_response(false, 'Your login session is invalid or expired', [], 401);
    }
    return (int) $user['id'];
}

function accounts_body(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function accounts_positive_id($value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 ? $id : null;
}

function accounts_amount_cents($value): ?int
{
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        return null;
    }
    $amount = trim((string) $value);
    if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $amount)) {
        return null;
    }
    [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
    $cents = (int) ($whole . str_pad($fraction, 2, '0'));
    return $cents;
}

function accounts_decimal(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function accounts_next_account_code(mysqli $conn): string
{
    $result = $conn->query(
        "SELECT account_code FROM account_heads WHERE account_code REGEXP '^[0-9]+$'"
    );
    $usedCodes = [];
    while ($row = $result->fetch_assoc()) {
        $numericCode = (int) $row['account_code'];
        if ($numericCode >= 101) {
            $usedCodes[$numericCode] = true;
        }
    }

    $nextCode = 101;
    while (isset($usedCodes[$nextCode])) {
        $nextCode++;
    }
    return (string) $nextCode;
}

function accounts_date_valid($value): bool
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

function accounts_voucher_tables(string $type): array
{
    $tables = [
        'journal' => ['journal_vouchers', 'journal_voucher_details', null],
        'receive' => ['receive_vouchers', 'receive_voucher_details', 'received_from'],
        'payment' => ['payment_vouchers', 'payment_voucher_details', 'pay_to'],
    ];
    if (!isset($tables[$type])) {
        throw new DomainException('Select a valid voucher type');
    }
    return $tables[$type];
}

function accounts_require_active_leaf(mysqli $conn, int $accountId): void
{
    $statement = $conn->prepare(
        "SELECT a.id FROM account_heads a
         WHERE a.id = ? AND a.status = 'Active' AND a.deleted_at IS NULL
           AND NOT EXISTS (
               SELECT 1 FROM account_heads child
               WHERE child.parent_id = a.id AND child.status = 'Active' AND child.deleted_at IS NULL
           )
         LIMIT 1"
    );
    $statement->bind_param('i', $accountId);
    $statement->execute();
    $found = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$found) {
        throw new DomainException('Choose an active detail account without child accounts');
    }
}

function accounts_get_vouchers(mysqli $conn): array
{
    $sql = "
        SELECT id, voucher_no, 'journal' AS voucher_type, voucher_date, narration,
               dr, cr, status, source_type, source_id, created_at
        FROM journal_vouchers WHERE deleted_at IS NULL
        UNION ALL
        SELECT id, voucher_no, 'receive' AS voucher_type, voucher_date, narration,
               dr, cr, status, source_type, source_id, created_at
        FROM receive_vouchers WHERE deleted_at IS NULL
        UNION ALL
        SELECT id, voucher_no, 'payment' AS voucher_type, voucher_date, narration,
               dr, cr, status, source_type, source_id, created_at
        FROM payment_vouchers WHERE deleted_at IS NULL
        ORDER BY voucher_date DESC, id DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function accounts_ledger_query(): string
{
    return "
        SELECT 'journal' AS voucher_type, j.id AS voucher_id, j.voucher_no, j.voucher_date,
               d.id AS detail_id, d.account_head_id, d.dr, d.cr, d.remarks
        FROM journal_vouchers j
        JOIN journal_voucher_details d ON d.journal_voucher_id = j.id
        WHERE j.status = 'posted' AND j.deleted_at IS NULL
        UNION ALL
        SELECT 'receive' AS voucher_type, r.id AS voucher_id, r.voucher_no, r.voucher_date,
               d.id AS detail_id, d.account_head_id, d.dr, d.cr, d.remarks
        FROM receive_vouchers r
        JOIN receive_voucher_details d ON d.receive_voucher_id = r.id
        WHERE r.status = 'posted' AND r.deleted_at IS NULL
        UNION ALL
        SELECT 'payment' AS voucher_type, p.id AS voucher_id, p.voucher_no, p.voucher_date,
               d.id AS detail_id, d.account_head_id, d.dr, d.cr, d.remarks
        FROM payment_vouchers p
        JOIN payment_voucher_details d ON d.payment_voucher_id = p.id
        WHERE p.status = 'posted' AND p.deleted_at IS NULL";
}

function accounts_source_voucher_config(string $sourceType): array
{
    $configs = [
        'sale' => [
            'source_type' => 'sales_invoice',
            'voucher_type' => 'receive',
            'header_table' => 'receive_vouchers',
            'detail_table' => 'receive_voucher_details',
            'foreign_key' => 'receive_voucher_id',
            'party_column' => 'received_from',
        ],
        'purchase' => [
            'source_type' => 'purchase_invoice',
            'voucher_type' => 'payment',
            'header_table' => 'payment_vouchers',
            'detail_table' => 'payment_voucher_details',
            'foreign_key' => 'payment_voucher_id',
            'party_column' => 'pay_to',
        ],
    ];
    if (!isset($configs[$sourceType])) {
        throw new DomainException('Choose a valid sale or purchase invoice');
    }
    return $configs[$sourceType];
}

function accounts_load_source_voucher(mysqli $conn, array $config, int $sourceId): ?array
{
    $statement = $conn->prepare(
        'SELECT id, voucher_no, ' . $config['party_column'] . ' AS party_name, narration,
                voucher_date, dr, cr, status
         FROM `' . $config['header_table'] . '`
         WHERE source_type = ? AND source_id = ? AND deleted_at IS NULL
         LIMIT 1'
    );
    $statement->bind_param('si', $config['source_type'], $sourceId);
    $statement->execute();
    $voucher = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$voucher) {
        return null;
    }

    $details = $conn->prepare(
        'SELECT d.account_head_id, a.account_code, a.account_name, d.dr, d.cr, d.remarks
         FROM `' . $config['detail_table'] . '` d
         JOIN account_heads a ON a.id = d.account_head_id
         WHERE d.`' . $config['foreign_key'] . '` = ?
         ORDER BY d.id'
    );
    $voucherId = (int) $voucher['id'];
    $details->bind_param('i', $voucherId);
    $details->execute();
    $voucher['details'] = $details->get_result()->fetch_all(MYSQLI_ASSOC);
    $details->close();
    $voucher['voucher_type'] = $config['voucher_type'];
    return $voucher;
}

function accounts_active_leaf_accounts(mysqli $conn, string $type): array
{
    $statement = $conn->prepare(
        "SELECT a.id, a.account_name, a.account_subtype
         FROM account_heads a
         WHERE a.account_type = ? AND a.status = 'Active' AND a.deleted_at IS NULL
           AND NOT EXISTS (
               SELECT 1 FROM account_heads child
               WHERE child.parent_id = a.id AND child.status = 'Active' AND child.deleted_at IS NULL
           )
         ORDER BY a.account_code"
    );
    $statement->bind_param('s', $type);
    $statement->execute();
    $accounts = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $accounts;
}

function accounts_create_default_invoice_category_account(
    mysqli $conn,
    string $sourceType,
    int $userId
): int {
    $isSale = $sourceType === 'sale';
    $code = $isSale ? 'SALES-INCOME' : 'PURCHASE-EXPENSE';
    $name = $isSale ? 'Sales Income' : 'Purchase Expense';
    $type = $isSale ? 'Income' : 'Expense';
    $subtype = 'Invoice voucher';
    $description = 'Automatically created for invoice-linked vouchers.';

    $existing = $conn->prepare('SELECT id, account_type, status, deleted_at FROM account_heads WHERE account_code = ? LIMIT 1');
    $existing->bind_param('s', $code);
    $existing->execute();
    $account = $existing->get_result()->fetch_assoc();
    $existing->close();
    if ($account) {
        if ($account['account_type'] !== $type || $account['status'] !== 'Active' || $account['deleted_at'] !== null) {
            throw new DomainException('The default invoice account code already exists but is not active');
        }
        return (int) $account['id'];
    }

    $statement = $conn->prepare(
        "INSERT INTO account_heads
            (account_code, account_name, account_type, account_subtype, parent_id,
             opening_balance, status, description, created_by)
         VALUES (?, ?, ?, ?, NULL, 0.00, 'Active', ?, ?)"
    );
    $statement->bind_param('sssssi', $code, $name, $type, $subtype, $description, $userId);
    $statement->execute();
    $id = (int) $conn->insert_id;
    $statement->close();
    return $id;
}

try {
    $conn = $cc->conn;
    $userId = accounts_user_id($conn);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? '';

    if ($method === 'GET' && $action === 'single') {
        $type = (string) ($_GET['type'] ?? '');
        $id = accounts_positive_id($_GET['id'] ?? null);
        if ($id === null) {
            throw new DomainException('Invalid record id');
        }
        if ($type === 'account') {
            $statement = $conn->prepare(
                'SELECT id, account_code, account_name, account_type, account_subtype,
                        parent_id, opening_balance, status, description
                 FROM account_heads WHERE id = ? AND deleted_at IS NULL LIMIT 1'
            );
            $statement->bind_param('i', $id);
            $statement->execute();
            $record = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$record) {
                throw new DomainException('Account head was not found');
            }
            accounts_response(true, 'Account head found', [$record]);
        }

        [$headerTable, $detailTable] = accounts_voucher_tables($type);
        $foreignKey = $type === 'journal' ? 'journal_voucher_id' : $type . '_voucher_id';
        $header = $conn->prepare(
            "SELECT * FROM `$headerTable` WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $header->bind_param('i', $id);
        $header->execute();
        $record = $header->get_result()->fetch_assoc();
        $header->close();
        if (!$record) {
            throw new DomainException('Voucher was not found');
        }
        $details = $conn->prepare(
            "SELECT d.id, d.`$foreignKey` AS voucher_id, d.account_head_id,
                    a.account_code, a.account_name, d.dr, d.cr, d.remarks
             FROM `$detailTable` d
             JOIN account_heads a ON a.id = d.account_head_id
             WHERE d.`$foreignKey` = ? ORDER BY d.id"
        );
        $details->bind_param('i', $id);
        $details->execute();
        $record['details'] = $details->get_result()->fetch_all(MYSQLI_ASSOC);
        $details->close();
        accounts_response(true, 'Voucher found', [$record]);
    }

    if ($method === 'GET' && $action === 'source_voucher') {
        $sourceType = (string) ($_GET['type'] ?? '');
        $sourceId = accounts_positive_id($_GET['id'] ?? null);
        if ($sourceId === null) {
            throw new DomainException('Invalid invoice id');
        }
        $config = accounts_source_voucher_config($sourceType);
        $voucher = accounts_load_source_voucher($conn, $config, $sourceId);
        if (!$voucher) {
            throw new DomainException('No voucher has been created for this invoice');
        }
        accounts_response(true, 'Invoice voucher loaded', $voucher);
    }

    if ($method === 'GET' && $action === 'accounts') {
        $result = $conn->query(
            "SELECT id, account_code, account_name, account_type, account_subtype,
                    parent_id, opening_balance, status, description
             FROM account_heads WHERE deleted_at IS NULL
             ORDER BY CASE WHEN account_code REGEXP '^[0-9]+$' THEN 0 ELSE 1 END,
                      CASE WHEN account_code REGEXP '^[0-9]+$' THEN CAST(account_code AS UNSIGNED) END,
                      account_code"
        );
        accounts_response(true, 'Account heads loaded', $result->fetch_all(MYSQLI_ASSOC));
    }

    if ($method === 'GET' && $action === 'next_account_code') {
        accounts_response(true, 'Next account code loaded', [
            'account_code' => accounts_next_account_code($conn),
        ]);
    }

    if ($method === 'GET' && $action === 'vouchers') {
        accounts_response(true, 'Vouchers loaded', accounts_get_vouchers($conn));
    }

    if ($method === 'GET' && $action === 'ledger') {
        $accountId = isset($_GET['account_id']) && $_GET['account_id'] !== ''
            ? accounts_positive_id($_GET['account_id'])
            : null;
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        if ($accountId === null || ($from !== '' || $to !== '')
            && (!accounts_date_valid($from) || !accounts_date_valid($to) || $from > $to)) {
            throw new DomainException('Choose a valid account');
        }

        $sql = 'SELECT l.voucher_type, l.voucher_id, l.voucher_no, l.voucher_date,
                       l.detail_id, l.account_head_id, a.account_code, a.account_name,
                       l.dr, l.cr, l.remarks
                FROM (' . accounts_ledger_query() . ') l
                JOIN account_heads a ON a.id = l.account_head_id
                WHERE l.account_head_id = ?';
        if ($from !== '' && $to !== '') {
            $sql .= ' AND l.voucher_date BETWEEN ? AND ?';
        }
        $sql .= ' ORDER BY l.voucher_date, l.voucher_type, l.voucher_id, l.detail_id';
        $statement = $conn->prepare($sql);
        if ($from !== '' && $to !== '') {
            $statement->bind_param('iss', $accountId, $from, $to);
        } else {
            $statement->bind_param('i', $accountId);
        }
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $opening = 0;
        $running = 0;
        $accountQuery = $conn->prepare(
            'SELECT account_type, opening_balance FROM account_heads WHERE id = ? AND deleted_at IS NULL'
        );
        $accountQuery->bind_param('i', $accountId);
        $accountQuery->execute();
        $account = $accountQuery->get_result()->fetch_assoc();
        $accountQuery->close();
        if (!$account) {
            throw new DomainException('Account was not found');
        }
        $opening = (float) $account['opening_balance'];
        $isDebitNormal = in_array($account['account_type'], ['Asset', 'Expense'], true);
        if ($from !== '') {
            $balance = $conn->prepare(
                'SELECT COALESCE(SUM(dr), 0) AS debit, COALESCE(SUM(cr), 0) AS credit
                 FROM (' . accounts_ledger_query() . ') ledger_rows
                 WHERE account_head_id = ? AND voucher_date < ?'
            );
            $balance->bind_param('is', $accountId, $from);
            $balance->execute();
            $prior = $balance->get_result()->fetch_assoc();
            $balance->close();
            $opening += $isDebitNormal
                ? (float) $prior['debit'] - (float) $prior['credit']
                : (float) $prior['credit'] - (float) $prior['debit'];
        }
        $running = $opening;
        foreach ($rows as &$row) {
            $delta = $isDebitNormal
                ? (float) $row['dr'] - (float) $row['cr']
                : (float) $row['cr'] - (float) $row['dr'];
            $running += $delta;
            $row['running_balance'] = number_format($running, 2, '.', '');
        }
        unset($row);
        accounts_response(true, 'Ledger loaded', [
            'opening_balance' => number_format($opening, 2, '.', ''),
            'closing_balance' => number_format($running, 2, '.', ''),
            'rows' => $rows,
        ]);
    }

    if ($method === 'GET' && $action === 'profit_loss') {
        $statement = $conn->prepare(
            "SELECT a.id, a.account_code, a.account_name, a.account_type,
                    COALESCE(SUM(l.dr), 0) AS total_debit,
                    COALESCE(SUM(l.cr), 0) AS total_credit
             FROM (" . accounts_ledger_query() . ") l
             JOIN account_heads a ON a.id = l.account_head_id
             WHERE a.account_type IN ('Income', 'Expense')
               AND NOT (
                   BINARY l.voucher_type = BINARY 'receive'
                   AND EXISTS (
                       SELECT 1 FROM receive_vouchers linked_receive
                       WHERE linked_receive.id = l.voucher_id
                         AND BINARY linked_receive.source_type = BINARY 'sales_invoice'
                         AND linked_receive.source_id IS NOT NULL
                   )
               )
               AND NOT (
                   BINARY l.voucher_type = BINARY 'payment'
                   AND EXISTS (
                       SELECT 1 FROM payment_vouchers linked_payment
                       WHERE linked_payment.id = l.voucher_id
                         AND BINARY linked_payment.source_type = BINARY 'purchase_invoice'
                         AND linked_payment.source_id IS NOT NULL
                   )
               )
             GROUP BY a.id, a.account_code, a.account_name, a.account_type
             ORDER BY a.account_type, a.account_code"
        );
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $salesStatement = $conn->prepare(
            'SELECT GREATEST(
                COALESCE((SELECT SUM(total_amount) FROM sales), 0)
                - COALESCE((SELECT SUM(total) FROM sales_returns), 0),
                0
             ) AS total'
        );
        $salesStatement->execute();
        $salesTotal = (float) $salesStatement->get_result()->fetch_assoc()['total'];
        $salesStatement->close();

        $costOfGoodsStatement = $conn->prepare(
            'SELECT COALESCE(SUM(
                GREATEST(si.quantity - COALESCE(sri.returned_quantity, 0), 0)
                * COALESCE(purchase_cost.average_unit_cost, m.purchase_price)
             ), 0) AS total
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN medicines m ON m.id = si.medicine_id
             LEFT JOIN (
                 SELECT sale_item_id, SUM(quantity) AS returned_quantity
                 FROM sales_return_items
                 GROUP BY sale_item_id
             ) sri ON sri.sale_item_id = si.id
             LEFT JOIN (
                 SELECT pi.medicine_id,
                        SUM(pi.line_total - COALESCE(pri.return_amount, 0))
                        / NULLIF(SUM(pi.quantity - COALESCE(pri.returned_quantity, 0)), 0) AS average_unit_cost
                 FROM purchase_items pi
                 LEFT JOIN (
                     SELECT purchase_item_id, SUM(quantity) AS returned_quantity,
                            SUM(line_total) AS return_amount
                     FROM purchase_return_items
                     GROUP BY purchase_item_id
                 ) pri ON pri.purchase_item_id = pi.id
                 GROUP BY pi.medicine_id
             ) purchase_cost ON purchase_cost.medicine_id = si.medicine_id'
        );
        $costOfGoodsStatement->execute();
        $costOfGoods = (float) $costOfGoodsStatement->get_result()->fetch_assoc()['total'];
        $costOfGoodsStatement->close();

        $income = 0.0;
        $expense = 0.0;
        foreach ($rows as &$row) {
            $net = $row['account_type'] === 'Income'
                ? (float) $row['total_credit'] - (float) $row['total_debit']
                : (float) $row['total_debit'] - (float) $row['total_credit'];
            $row['net_amount'] = number_format($net, 2, '.', '');
            if ($row['account_type'] === 'Income') {
                $income += $net;
            } else {
                $expense += $net;
            }
        }
        unset($row);
        $income += $salesTotal;
        $rows[] = [
            'id' => 'sales-total',
            'account_code' => 'SALES',
            'account_name' => 'Net sales',
            'account_type' => 'Income',
            'net_amount' => number_format($salesTotal, 2, '.', ''),
        ];
        $rows[] = [
            'id' => 'cost-of-goods-sold',
            'account_code' => 'COGS',
            'account_name' => 'Cost of goods sold',
            'account_type' => 'Expense',
            'net_amount' => number_format($costOfGoods, 2, '.', ''),
        ];
        $expense += $costOfGoods;
        accounts_response(true, 'Profit and loss loaded', [
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'net_profit' => number_format($income - $expense, 2, '.', ''),
            'rows' => $rows,
        ]);
    }

    if ($method === 'POST') {
        $input = accounts_body();

        if ($action === 'create_invoice_voucher') {
            $sourceType = (string) ($input['source_type'] ?? '');
            $sourceId = accounts_positive_id($input['source_id'] ?? null);
            if ($sourceId === null) {
                throw new DomainException('Invalid invoice id');
            }
            $config = accounts_source_voucher_config($sourceType);
            $conn->begin_transaction();
            try {
                if ($sourceType === 'sale') {
                    $invoiceStatement = $conn->prepare(
                        'SELECT id, invoice_no, customer_name AS party_name, sale_date AS invoice_date,
                                total_amount
                         FROM sales WHERE id = ? FOR UPDATE'
                    );
                } else {
                    $invoiceStatement = $conn->prepare(
                        'SELECT p.id, p.invoice_no, s.supplier_name AS party_name,
                                p.purchase_date AS invoice_date, p.total_amount
                         FROM purchases p
                         LEFT JOIN suppliers s ON s.id = p.supplier_id
                         WHERE p.id = ? FOR UPDATE'
                    );
                }
                $invoiceStatement->bind_param('i', $sourceId);
                $invoiceStatement->execute();
                $invoice = $invoiceStatement->get_result()->fetch_assoc();
                $invoiceStatement->close();
                if (!$invoice) {
                    throw new DomainException('Invoice was not found');
                }

                $existingVoucher = accounts_load_source_voucher($conn, $config, $sourceId);
                if ($existingVoucher) {
                    $conn->commit();
                    accounts_response(true, 'Voucher already exists for this invoice', $existingVoucher);
                }

                $amountCents = accounts_amount_cents((string) $invoice['total_amount']);
                if ($amountCents === null || $amountCents === 0) {
                    throw new DomainException('A voucher cannot be created for a zero-value invoice');
                }
                $cashAccountId = accounts_positive_id($input['cash_account_id'] ?? null);
                $categoryAccountId = accounts_positive_id($input['category_account_id'] ?? null);
                if ($cashAccountId === null) {
                    $assets = accounts_active_leaf_accounts($conn, 'Asset');
                    $cashAccounts = array_values(array_filter($assets, static function ($account) {
                        return preg_match('/cash|bank/i', $account['account_name'] . ' ' . $account['account_subtype']);
                    }));
                    if (count($cashAccounts) !== 1) {
                        throw new DomainException('Select one active Cash/Bank account');
                    }
                    $cashAccountId = (int) $cashAccounts[0]['id'];
                }
                $categoryType = $sourceType === 'sale' ? 'Income' : 'Expense';
                if ($categoryAccountId === null) {
                    $categoryAccounts = accounts_active_leaf_accounts($conn, $categoryType);
                    if (count($categoryAccounts) > 1) {
                        throw new DomainException('Select one matching Income/Expense account');
                    }
                    if (count($categoryAccounts) === 1) {
                        $categoryAccountId = (int) $categoryAccounts[0]['id'];
                    } else {
                             throw new DomainException(
                                'Create an active ' . $categoryType . ' account before creating this invoice voucher'
                                );
                            }
                }

                $requiredCategoryType = $sourceType === 'sale' ? 'Income' : 'Expense';
                foreach ([
                    [$cashAccountId, 'Asset'],
                    [$categoryAccountId, $requiredCategoryType],
                ] as [$accountId, $requiredType]) {
                    $accountStatement = $conn->prepare(
                        "SELECT a.id FROM account_heads a
                         WHERE a.id = ? AND a.account_type = ? AND a.status = 'Active'
                           AND a.deleted_at IS NULL
                           AND NOT EXISTS (
                               SELECT 1 FROM account_heads child
                               WHERE child.parent_id = a.id AND child.status = 'Active'
                                 AND child.deleted_at IS NULL
                           )
                         LIMIT 1"
                    );
                    $accountStatement->bind_param('is', $accountId, $requiredType);
                    $accountStatement->execute();
                    $validAccount = $accountStatement->get_result()->fetch_assoc();
                    $accountStatement->close();
                    if (!$validAccount) {
                        throw new DomainException('Choose active detail accounts with the required account types');
                    }
                }

                $amount = accounts_decimal($amountCents);
                $voucherNo = strtoupper(substr($config['voucher_type'], 0, 2))
                    . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $partyName = trim((string) ($invoice['party_name'] ?? ''));
                if ($partyName === '') {
                    $partyName = $sourceType === 'sale' ? 'Sales customer' : 'Supplier';
                }
                $narration = ucfirst($sourceType) . ' invoice ' . $invoice['invoice_no'];
                $headerStatement = $conn->prepare(
                    'INSERT INTO `' . $config['header_table'] . '`
                        (voucher_no, `' . $config['party_column'] . '`, narration, voucher_date,
                         source_type, source_id, dr, cr, created_by, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'posted\')'
                );
                $headerStatement->bind_param(
                    'sssssissi',
                    $voucherNo,
                    $partyName,
                    $narration,
                    $invoice['invoice_date'],
                    $config['source_type'],
                    $sourceId,
                    $amount,
                    $amount,
                    $userId
                );
                $headerStatement->execute();
                $voucherId = (int) $conn->insert_id;
                $headerStatement->close();

                $foreignKey = $config['foreign_key'];
                $detailStatement = $conn->prepare(
                    'INSERT INTO `' . $config['detail_table'] . '`
                        (`' . $foreignKey . '`, account_head_id, dr, cr, remarks, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $voucherLines = $sourceType === 'sale'
                    ? [
                        [$cashAccountId, $amount, '0.00', 'Cash received for invoice ' . $invoice['invoice_no']],
                        [$categoryAccountId, '0.00', $amount, 'Sales income for invoice ' . $invoice['invoice_no']],
                    ]
                    : [
                        [$categoryAccountId, $amount, '0.00', 'Purchase expense for invoice ' . $invoice['invoice_no']],
                        [$cashAccountId, '0.00', $amount, 'Payment for invoice ' . $invoice['invoice_no']],
                    ];
                foreach ($voucherLines as [$accountId, $debit, $credit, $remarks]) {
                    $detailStatement->bind_param(
                        'iisssi',
                        $voucherId,
                        $accountId,
                        $debit,
                        $credit,
                        $remarks,
                        $userId
                    );
                    $detailStatement->execute();
                }
                $detailStatement->close();
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }

            $voucher = accounts_load_source_voucher($conn, $config, $sourceId);
            accounts_response(true, 'Invoice voucher created and posted', $voucher);
        }

        if ($action === 'save_shop_expense') {
            $date = $input['expense_date'] ?? '';
            $name = trim((string) ($input['expense_name'] ?? ''));
            $amountCents = accounts_amount_cents($input['amount'] ?? '');
            $remarks = trim((string) ($input['remarks'] ?? ''));
            if (!accounts_date_valid($date) || $name === '' || strlen($name) > 150
                || $amountCents === null || $amountCents === 0 || strlen($remarks) > 255) {
                throw new DomainException('Enter a valid expense date, name, and positive amount');
            }

            $amount = accounts_decimal($amountCents);
            $statement = $conn->prepare(
                'INSERT INTO shop_expenses (expense_date, expense_name, amount, remarks, created_by)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $statement->bind_param('ssssi', $date, $name, $amount, $remarks, $userId);
            $statement->execute();
            $id = (int) $conn->insert_id;
            $statement->close();
            accounts_response(true, 'Shop expense saved', ['id' => $id]);
        }

        if ($action === 'delete') {
            $type = (string) ($input['type'] ?? '');
            $id = accounts_positive_id($input['id'] ?? null);
            if ($id === null) {
                throw new DomainException('Invalid record id');
            }

            if ($type === 'account') {
               
                    $references = [
                        ['journal_voucher_details', 'journal_vouchers', 'journal_voucher_id'],
                        ['receive_voucher_details', 'receive_vouchers', 'receive_voucher_id'],
                        ['payment_voucher_details', 'payment_vouchers', 'payment_voucher_id'],
                    ];

                    foreach ($references as [$detailTable, $headerTable, $foreignKey]) {
                        $check = $conn->prepare(
                            "SELECT d.id
                            FROM `$detailTable` d
                            JOIN `$headerTable` h ON h.id = d.`$foreignKey`
                            WHERE d.account_head_id = ? AND h.deleted_at IS NULL
                            LIMIT 1"
                        );
                        $check->bind_param('i', $id);
                        $check->execute();
                        $used = $check->get_result()->fetch_assoc();
                        $check->close();

                        if ($used) {
                            throw new DomainException('Account is used by an active voucher and cannot be deleted');
                        }
                    }
                $childCheck = $conn->prepare(
                    'SELECT id FROM account_heads WHERE parent_id = ? AND deleted_at IS NULL LIMIT 1'
                );
                $childCheck->bind_param('i', $id);
                $childCheck->execute();
                $hasChildren = $childCheck->get_result()->fetch_assoc();
                $childCheck->close();
                if ($hasChildren) {
                    throw new DomainException('Account has child accounts and cannot be deleted');
                }
                $delete = $conn->prepare(
                    "UPDATE account_heads SET status = 'Inactive', deleted_at = NOW()
                     WHERE id = ? AND deleted_at IS NULL"
                );
                $delete->bind_param('i', $id);
                $delete->execute();
                $affected = $delete->affected_rows;
                $delete->close();
                if ($affected === 0) {
                    throw new DomainException('Account head was not found');
                }
                accounts_response(true, 'Account head deleted');
            }

            [$headerTable, $detailTable] = accounts_voucher_tables($type);
            $foreignKey = $type === 'journal' ? 'journal_voucher_id' : $type . '_voucher_id';
            $conn->begin_transaction();
            try {
                $header = $conn->prepare(
                    "SELECT id FROM `$headerTable` WHERE id = ? AND deleted_at IS NULL FOR UPDATE"
                );
                $header->bind_param('i', $id);
                $header->execute();
                $voucher = $header->get_result()->fetch_assoc();
                $header->close();
                if (!$voucher) {
                    throw new DomainException('Voucher was not found');
                }
                $removeHeader = $conn->prepare(
                    "UPDATE `$headerTable` SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL"
                );
                $removeHeader->bind_param('i', $id);
                $removeHeader->execute();
                $removeHeader->close();
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }
            accounts_response(true, 'Voucher deleted');
        }

        if ($action === 'save_account') {
            $name = trim((string) ($input['account_name'] ?? ''));
            $type = $input['account_type'] ?? '';
            $subtype = trim((string) ($input['account_subtype'] ?? ''));
            $parentId = ($input['parent_id'] ?? '') === '' ? null : accounts_positive_id($input['parent_id']);
            $openingCents = accounts_amount_cents($input['opening_balance'] ?? '0');
            $description = trim((string) ($input['description'] ?? ''));
            $validTypes = ['Asset', 'Liability', 'Income', 'Expense', 'Equity', 'VAT'];

            if ($name === '' || strlen($name) > 150 || !in_array($type, $validTypes, true) || $openingCents === null
                || ($input['parent_id'] ?? '') !== '' && $parentId === null
                || strlen($subtype) > 100) {
                throw new DomainException('Enter a valid account name, type, and opening balance');
            }
            if ($parentId !== null) {
                $parentStatement = $conn->prepare(
                    "SELECT id FROM account_heads WHERE id = ? AND status = 'Active' AND deleted_at IS NULL"
                );
                $parentStatement->bind_param('i', $parentId);
                $parentStatement->execute();
                $parent = $parentStatement->get_result()->fetch_assoc();
                $parentStatement->close();
                if (!$parent) {
                    throw new DomainException('Selected parent account is not active');
                }
            }

            $opening = accounts_decimal($openingCents);
            $database = $conn->query('SELECT DATABASE()')->fetch_row()[0] ?? '';
            if ($database === '') {
                throw new DomainException('Could not generate an account code. Please try again.');
            }
            $lockName = 'account_heads_code_' . substr(hash('sha256', $database), 0, 32);
            $lockStatement = $conn->prepare('SELECT GET_LOCK(?, 10) AS acquired');
            $lockStatement->bind_param('s', $lockName);
            $lockStatement->execute();
            $lockResult = $lockStatement->get_result()->fetch_assoc();
            $lockStatement->close();
            if ((int) ($lockResult['acquired'] ?? 0) !== 1) {
                throw new DomainException('Could not generate an account code. Please try again.');
            }

            try {
                $code = accounts_next_account_code($conn);

                $statement = $conn->prepare(
                    "INSERT INTO account_heads
                        (account_code, account_name, account_type, account_subtype, parent_id,
                         opening_balance, status, description, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, 'Active', ?, ?)"
                );
                $statement->bind_param(
                    'ssssissi',
                    $code,
                    $name,
                    $type,
                    $subtype,
                    $parentId,
                    $opening,
                    $description,
                    $userId
                );
                $statement->execute();
                $id = (int) $conn->insert_id;
                $statement->close();
            } finally {
                $releaseStatement = $conn->prepare('SELECT RELEASE_LOCK(?) AS released');
                $releaseStatement->bind_param('s', $lockName);
                $releaseStatement->execute();
                $releaseResult = $releaseStatement->get_result()->fetch_assoc();
                $releaseStatement->close();
                if ((int) ($releaseResult['released'] ?? 0) !== 1) {
                    error_log('Accounts API could not release the account-code generation lock');
                }
            }
            accounts_response(true, 'Account head created', ['id' => $id, 'account_code' => $code]);
        }

        if ($action === 'update_account') {
            $id = accounts_positive_id($input['id'] ?? null);
            $name = trim((string) ($input['account_name'] ?? ''));
            $type = $input['account_type'] ?? '';
            $subtype = trim((string) ($input['account_subtype'] ?? ''));
            $parentId = ($input['parent_id'] ?? '') === '' ? null : accounts_positive_id($input['parent_id']);
            $openingCents = accounts_amount_cents($input['opening_balance'] ?? '0');
            $description = trim((string) ($input['description'] ?? ''));
            $validTypes = ['Asset', 'Liability', 'Income', 'Expense', 'Equity', 'VAT'];

            if ($id === null || $name === '' || strlen($name) > 150
                || !in_array($type, $validTypes, true) || $openingCents === null
                || ($input['parent_id'] ?? '') !== '' && $parentId === null
                || $parentId === $id || strlen($subtype) > 100 || strlen($description) > 2000) {
                throw new DomainException('Enter a valid account name, type, and opening balance');
            }

            $existing = $conn->prepare(
                'SELECT id FROM account_heads WHERE id = ? AND deleted_at IS NULL LIMIT 1'
            );
            $existing->bind_param('i', $id);
            $existing->execute();
            $found = $existing->get_result()->fetch_assoc();
            $existing->close();
            if (!$found) {
                throw new DomainException('Account head was not found');
            }

            if ($parentId !== null) {
                $parentStatement = $conn->prepare(
                    "SELECT id FROM account_heads WHERE id = ? AND status = 'Active' AND deleted_at IS NULL"
                );
                $parentStatement->bind_param('i', $parentId);
                $parentStatement->execute();
                $parent = $parentStatement->get_result()->fetch_assoc();
                $parentStatement->close();
                if (!$parent) {
                    throw new DomainException('Selected parent account is not active');
                }
            }

            $opening = accounts_decimal($openingCents);
            $statement = $conn->prepare(
                'UPDATE account_heads
                 SET account_name = ?, account_type = ?, account_subtype = ?, parent_id = ?,
                     opening_balance = ?, description = ?
                 WHERE id = ? AND deleted_at IS NULL'
            );
            $statement->bind_param('sssissi', $name, $type, $subtype, $parentId, $opening, $description, $id);
            $statement->execute();
            $statement->close();
            accounts_response(true, 'Account head updated', ['id' => $id]);
        }

        if (in_array($action, ['save_voucher', 'edit_voucher'], true)) {
            $type = (string) ($input['voucher_type'] ?? '');
            [$headerTable, $detailTable, $partyColumn] = accounts_voucher_tables($type);
            $editing = $action === 'edit_voucher';
            $voucherId = $editing ? accounts_positive_id($input['id'] ?? null) : null;
            if ($editing && $voucherId === null) {
                throw new DomainException('Invalid voucher id');
            }
            $date = $input['voucher_date'] ?? '';
            $narration = trim((string) ($input['narration'] ?? ''));
            $partyName = trim((string) ($input['party_name'] ?? ''));
            $sourceType = trim((string) ($input['source_type'] ?? ''));
            $sourceId = ($input['source_id'] ?? '') === '' ? null : accounts_positive_id($input['source_id']);
            $lines = $input['lines'] ?? null;
            if (is_string($lines)) {
                $lines = json_decode($lines, true);
            }
            if (!accounts_date_valid($date) || !is_array($lines) || count($lines) < 2 || count($lines) > 100
                || ($partyColumn !== null && ($partyName === '' || strlen($partyName) > 150))
                || ($sourceType !== '' && strlen($sourceType) > 100)
                || (($input['source_id'] ?? '') !== '' && $sourceId === null)) {
                throw new DomainException('Enter a valid date, party (if needed), and at least two voucher lines');
            }

            $normalized = [];
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                if (!is_array($line)) {
                    throw new DomainException('Invalid voucher line');
                }
                $accountId = accounts_positive_id($line['account_head_id'] ?? null);
                $debitValue = $line['dr'] ?? '0';
                $creditValue = $line['cr'] ?? '0';
                if (is_string($debitValue) && trim($debitValue) === '') {
                    $debitValue = '0';
                }
                if (is_string($creditValue) && trim($creditValue) === '') {
                    $creditValue = '0';
                }
                $debit = accounts_amount_cents($debitValue);
                $credit = accounts_amount_cents($creditValue);
                $remarks = trim((string) ($line['remarks'] ?? ''));
                if ($accountId === null || $debit === null || $credit === null
                    || strlen($remarks) > 255 || (($debit > 0) === ($credit > 0))) {
                    throw new DomainException('Each line needs an active account and either a positive debit or credit');
                }
                accounts_require_active_leaf($conn, $accountId);
                $totalDebit += $debit;
                $totalCredit += $credit;
                $normalized[] = [
                    'account_head_id' => $accountId,
                    'dr' => accounts_decimal($debit),
                    'cr' => accounts_decimal($credit),
                    'remarks' => $remarks,
                ];
            }
            if ($totalDebit > 99999999999999 || $totalCredit > 99999999999999
                || $totalDebit === 0 || $totalDebit !== $totalCredit) {
                throw new DomainException('Voucher debit and credit totals must be equal and greater than zero');
            }
            $totalDr = accounts_decimal($totalDebit);
            $totalCr = accounts_decimal($totalCredit);
            $foreignKey = $type === 'journal' ? 'journal_voucher_id' : $type . '_voucher_id';
            $voucherNo = '';
            $conn->begin_transaction();
            try {
                if ($editing) {
                    $existing = $conn->prepare(
                        "SELECT voucher_no FROM `$headerTable`
                         WHERE id = ? AND deleted_at IS NULL FOR UPDATE"
                    );
                    $existing->bind_param('i', $voucherId);
                    $existing->execute();
                    $record = $existing->get_result()->fetch_assoc();
                    $existing->close();
                    if (!$record) {
                        throw new DomainException('Voucher was not found');
                    }
                    $voucherNo = $record['voucher_no'];
                    $partySql = $partyColumn === null ? '' : ", `$partyColumn` = ?";
                    $sql = "UPDATE `$headerTable`
                            SET narration = ?, voucher_date = ?, dr = ?, cr = ?, status = 'posted'$partySql
                            WHERE id = ?";
                    $statement = $conn->prepare($sql);
                    if ($partyColumn === null) {
                        $statement->bind_param('ssssi', $narration, $date, $totalDr, $totalCr, $voucherId);
                    } else {
                        $statement->bind_param('sssssi', $narration, $date, $totalDr, $totalCr, $partyName, $voucherId);
                    }
                    $statement->execute();
                    $statement->close();
                    $removeDetails = $conn->prepare("DELETE FROM `$detailTable` WHERE `$foreignKey` = ?");
                    $removeDetails->bind_param('i', $voucherId);
                    $removeDetails->execute();
                    $removeDetails->close();
                } else {
                    $voucherNo = strtoupper(substr($type, 0, 2)) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                    $extraColumn = $partyColumn === null ? '' : ', `' . $partyColumn . '`';
                    $extraPlaceholder = $partyColumn === null ? '' : ', ?';
                    $sql = 'INSERT INTO `' . $headerTable . '`
                            (voucher_no' . $extraColumn . ', narration, voucher_date, source_type, source_id,
                             dr, cr, created_by, status)
                            VALUES (?'. $extraPlaceholder .', ?, ?, ?, ?, ?, ?, ?, \'posted\')';
                    $statement = $conn->prepare($sql);
                    if ($partyColumn === null) {
                        $statement->bind_param(
                            'ssssissi',
                            $voucherNo,
                            $narration,
                            $date,
                            $sourceType,
                            $sourceId,
                            $totalDr,
                            $totalCr,
                            $userId
                        );
                    } else {
                        $statement->bind_param(
                            'sssssissi',
                            $voucherNo,
                            $partyName,
                            $narration,
                            $date,
                            $sourceType,
                            $sourceId,
                            $totalDr,
                            $totalCr,
                            $userId
                        );
                    }
                    $statement->execute();
                    $voucherId = (int) $conn->insert_id;
                    $statement->close();
                }

                $detail = $conn->prepare(
                    'INSERT INTO `' . $detailTable . '`
                        (`' . $foreignKey . '`, account_head_id, dr, cr, remarks, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                foreach ($normalized as $line) {
                    $accountId = $line['account_head_id'];
                    $debit = $line['dr'];
                    $credit = $line['cr'];
                    $remarks = $line['remarks'];
                    $detail->bind_param('iisssi', $voucherId, $accountId, $debit, $credit, $remarks, $userId);
                    $detail->execute();
                }
                $detail->close();
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }
            accounts_response(
                true,
                $editing ? 'Voucher updated' : 'Voucher saved',
                ['id' => $voucherId, 'voucher_no' => $voucherNo]
            );
        }

        if ($action === 'post_voucher') {
            $type = (string) ($input['voucher_type'] ?? '');
            $id = accounts_positive_id($input['id'] ?? null);
            if ($id === null) {
                throw new DomainException('Invalid voucher id');
            }
            [$headerTable, $detailTable] = accounts_voucher_tables($type);
            $foreignKey = $type === 'journal' ? 'journal_voucher_id' : $type . '_voucher_id';
            $conn->begin_transaction();
            try {
                $header = $conn->prepare(
                    "SELECT id, status FROM `$headerTable` WHERE id = ? AND deleted_at IS NULL FOR UPDATE"
                );
                $header->bind_param('i', $id);
                $header->execute();
                $voucher = $header->get_result()->fetch_assoc();
                $header->close();
                if (!$voucher || $voucher['status'] !== 'draft') {
                    throw new DomainException('Only an existing draft voucher can be posted');
                }

                $totals = $conn->prepare(
                    "SELECT COUNT(*) AS line_count, COALESCE(SUM(dr), 0) AS debit,
                            COALESCE(SUM(cr), 0) AS credit
                     FROM `$detailTable` WHERE `$foreignKey` = ?"
                );
                $totals->bind_param('i', $id);
                $totals->execute();
                $sum = $totals->get_result()->fetch_assoc();
                $totals->close();
                if ((int) $sum['line_count'] < 2 || (float) $sum['debit'] <= 0
                    || accounts_amount_cents((string) $sum['debit'])
                        !== accounts_amount_cents((string) $sum['credit'])) {
                    throw new DomainException('Voucher is not balanced and cannot be posted');
                }
                $accountLines = $conn->prepare(
                    "SELECT account_head_id FROM `$detailTable` WHERE `$foreignKey` = ?"
                );
                $accountLines->bind_param('i', $id);
                $accountLines->execute();
                $accountResult = $accountLines->get_result();
                while ($accountLine = $accountResult->fetch_assoc()) {
                    accounts_require_active_leaf($conn, (int) $accountLine['account_head_id']);
                }
                $accountLines->close();
                $post = $conn->prepare("UPDATE `$headerTable` SET status = 'posted' WHERE id = ?");
                $post->bind_param('i', $id);
                $post->execute();
                $post->close();
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }
            accounts_response(true, 'Voucher posted successfully', ['id' => $id]);
        }
    }

    accounts_response(false, 'Unsupported Accounts API action');
} catch (DomainException $exception) {
    accounts_response(false, $exception->getMessage());
} catch (mysqli_sql_exception $exception) {
    error_log('Accounts API database error: ' . $exception->getMessage());
    accounts_response(false, 'Database operation failed. Check the Accounts tables and unique account/voucher codes.');
} catch (Throwable $exception) {
    error_log('Accounts API error: ' . $exception->getMessage());
    accounts_response(false, 'Accounts operation failed', [], 500);
}
