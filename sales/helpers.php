<?php

function sales_respond(bool $status, string $message, $data = []): void
{
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
}

function sales_post_string(string $field): ?string
{
    if (!isset($_POST[$field]) || !is_scalar($_POST[$field])) {
        return null;
    }

    return trim((string) $_POST[$field]);
}

function sales_positive_int($value): ?int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    return $number !== false && $number > 0 ? $number : null;
}

function sales_decimal($value): ?string
{
    if (!is_string($value) || !preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
        return null;
    }

    $parts = explode('.', $value, 2);
    $whole = ltrim($parts[0], '0');
    $whole = $whole === '' ? '0' : $whole;
    if (strlen($whole) > 8) {
        return null;
    }

    $fraction = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';
    return $whole . '.' . $fraction;
}

function sales_subtotal(int $quantity, string $price): ?string
{
    [$whole, $fraction] = explode('.', $price, 2);
    $cents = ((int) $whole * 100) + (int) $fraction;
    $maximumCents = 999999999999;

    if ($cents > 0 && $quantity > intdiv($maximumCents, $cents)) {
        return null;
    }

    $subtotalCents = $cents * $quantity;
    return intdiv($subtotalCents, 100) . '.' . str_pad((string) ($subtotalCents % 100), 2, '0', STR_PAD_LEFT);
}

function sales_valid_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    $errors = DateTime::getLastErrors();

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function sales_string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
