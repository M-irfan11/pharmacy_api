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
               dr, cr, status, created_at
        FROM journal_vouchers WHERE deleted_at IS NULL
        UNION ALL
        SELECT id, voucher_no, 'receive' AS voucher_type, voucher_date, narration,
               dr, cr, status, created_at
        FROM receive_vouchers WHERE deleted_at IS NULL
        UNION ALL
        SELECT id, voucher_no, 'payment' AS voucher_type, voucher_date, narration,
               dr, cr, status, created_at
        FROM payment_vouchers WHERE deleted_at IS NULL
        ORDER BY voucher_date DESC, id DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
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

    if ($method === 'GET' && $action === 'accounts') {
        $result = $conn->query(
            "SELECT id, account_code, account_name, account_type, account_subtype,
                    parent_id, opening_balance, status, description
             FROM account_heads WHERE deleted_at IS NULL
             ORDER BY account_code"
        );
        accounts_response(true, 'Account heads loaded', $result->fetch_all(MYSQLI_ASSOC));
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
        if (($accountId === null && isset($_GET['account_id']) && $_GET['account_id'] !== '')
            || !accounts_date_valid($from) || !accounts_date_valid($to) || $from > $to) {
            throw new DomainException('Choose a valid account and date range');
        }

        $sql = 'SELECT l.voucher_type, l.voucher_id, l.voucher_no, l.voucher_date,
                       l.detail_id, l.account_head_id, a.account_code, a.account_name,
                       l.dr, l.cr, l.remarks
                FROM ledger l
                JOIN account_heads a ON a.id = l.account_head_id
                WHERE l.voucher_date BETWEEN ? AND ?';
        if ($accountId !== null) {
            $sql .= ' AND l.account_head_id = ?';
        }
        $sql .= ' ORDER BY l.voucher_date, l.voucher_type, l.voucher_id, l.detail_id';
        $statement = $conn->prepare($sql);
        if ($accountId !== null) {
            $statement->bind_param('ssi', $from, $to, $accountId);
        } else {
            $statement->bind_param('ss', $from, $to);
        }
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $opening = 0;
        $running = 0;
        if ($accountId !== null) {
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
            $balance = $conn->prepare(
                'SELECT COALESCE(SUM(dr), 0) AS debit, COALESCE(SUM(cr), 0) AS credit
                 FROM ledger WHERE account_head_id = ? AND voucher_date < ?'
            );
            $balance->bind_param('is', $accountId, $from);
            $balance->execute();
            $prior = $balance->get_result()->fetch_assoc();
            $balance->close();
            $isDebitNormal = in_array($account['account_type'], ['Asset', 'Expense'], true);
            $opening += $isDebitNormal
                ? (float) $prior['debit'] - (float) $prior['credit']
                : (float) $prior['credit'] - (float) $prior['debit'];
            $running = $opening;
            foreach ($rows as &$row) {
                $delta = $isDebitNormal
                    ? (float) $row['dr'] - (float) $row['cr']
                    : (float) $row['cr'] - (float) $row['dr'];
                $running += $delta;
                $row['running_balance'] = number_format($running, 2, '.', '');
            }
            unset($row);
        }
        accounts_response(true, 'Ledger loaded', [
            'opening_balance' => number_format($opening, 2, '.', ''),
            'closing_balance' => number_format($running, 2, '.', ''),
            'rows' => $rows,
        ]);
    }

    if ($method === 'GET' && $action === 'profit_loss') {
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        if (!accounts_date_valid($from) || !accounts_date_valid($to) || $from > $to) {
            throw new DomainException('Choose a valid date range');
        }

        $statement = $conn->prepare(
            "SELECT a.id, a.account_code, a.account_name, a.account_type,
                    COALESCE(SUM(l.dr), 0) AS total_debit,
                    COALESCE(SUM(l.cr), 0) AS total_credit
             FROM ledger l
             JOIN account_heads a ON a.id = l.account_head_id
             WHERE l.voucher_date BETWEEN ? AND ?
               AND a.account_type IN ('Income', 'Expense')
             GROUP BY a.id, a.account_code, a.account_name, a.account_type
             ORDER BY a.account_type, a.account_code"
        );
        $statement->bind_param('ss', $from, $to);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

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
        accounts_response(true, 'Profit and loss loaded', [
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'net_profit' => number_format($income - $expense, 2, '.', ''),
            'rows' => $rows,
        ]);
    }

    if ($method === 'POST') {
        $input = accounts_body();

        if ($action === 'delete') {
            $type = (string) ($input['type'] ?? '');
            $id = accounts_positive_id($input['id'] ?? null);
            if ($id === null) {
                throw new DomainException('Invalid record id');
            }

            if ($type === 'account') {
                $references = [
                    'journal_voucher_details',
                    'receive_voucher_details',
                    'payment_voucher_details',
                ];
                foreach ($references as $table) {
                    $check = $conn->prepare("SELECT id FROM `$table` WHERE account_head_id = ? LIMIT 1");
                    $check->bind_param('i', $id);
                    $check->execute();
                    $used = $check->get_result()->fetch_assoc();
                    $check->close();
                    if ($used) {
                        throw new DomainException('Account is used by voucher entries and cannot be deleted');
                    }
                }
                $childCheck = $conn->prepare('SELECT id FROM account_heads WHERE parent_id = ? LIMIT 1');
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
                    "SELECT status FROM `$headerTable` WHERE id = ? AND deleted_at IS NULL FOR UPDATE"
                );
                $header->bind_param('i', $id);
                $header->execute();
                $voucher = $header->get_result()->fetch_assoc();
                $header->close();
                if (!$voucher || $voucher['status'] !== 'draft') {
                    throw new DomainException('Only draft vouchers can be deleted');
                }
                $removeDetails = $conn->prepare("DELETE FROM `$detailTable` WHERE `$foreignKey` = ?");
                $removeDetails->bind_param('i', $id);
                $removeDetails->execute();
                $removeDetails->close();
                $removeHeader = $conn->prepare("DELETE FROM `$headerTable` WHERE id = ?");
                $removeHeader->bind_param('i', $id);
                $removeHeader->execute();
                $removeHeader->close();
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                throw $exception;
            }
            accounts_response(true, 'Draft voucher deleted');
        }

        if ($action === 'save_account') {
            $code = trim((string) ($input['account_code'] ?? ''));
            $name = trim((string) ($input['account_name'] ?? ''));
            $type = $input['account_type'] ?? '';
            $subtype = trim((string) ($input['account_subtype'] ?? ''));
            $parentId = ($input['parent_id'] ?? '') === '' ? null : accounts_positive_id($input['parent_id']);
            $openingCents = accounts_amount_cents($input['opening_balance'] ?? '0');
            $description = trim((string) ($input['description'] ?? ''));
            $validTypes = ['Asset', 'Liability', 'Income', 'Expense', 'Equity', 'VAT'];

            if ($code === '' || strlen($code) > 100 || $name === '' || strlen($name) > 150
                || !in_array($type, $validTypes, true) || $openingCents === null
                || ($input['parent_id'] ?? '') !== '' && $parentId === null
                || strlen($subtype) > 100) {
                throw new DomainException('Enter a valid account code, name, type, and opening balance');
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
            accounts_response(true, 'Account head created', ['id' => $id]);
        }

        if ($action === 'save_voucher') {
            $type = (string) ($input['voucher_type'] ?? '');
            [$headerTable, $detailTable, $partyColumn] = accounts_voucher_tables($type);
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
                $debit = accounts_amount_cents($line['dr'] ?? '0');
                $credit = accounts_amount_cents($line['cr'] ?? '0');
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
            $voucherNo = strtoupper(substr($type, 0, 2)) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $conn->begin_transaction();
            try {
                $extraColumn = $partyColumn === null ? '' : ', `' . $partyColumn . '`';
                $extraPlaceholder = $partyColumn === null ? '' : ', ?';
                $sql = 'INSERT INTO `' . $headerTable . '`
                        (voucher_no' . $extraColumn . ', narration, voucher_date, source_type, source_id,
                         dr, cr, created_by, status)
                        VALUES (?'. $extraPlaceholder .', ?, ?, ?, ?, ?, ?, ?, \'draft\')';
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

                $detail = $conn->prepare(
                    'INSERT INTO `' . $detailTable . '`
                        (' . ($type === 'journal' ? 'journal_voucher_id' : $type . '_voucher_id')
                        . ', account_head_id, dr, cr, remarks, created_by)
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
            accounts_response(true, 'Draft voucher created', ['id' => $voucherId, 'voucher_no' => $voucherNo]);
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
