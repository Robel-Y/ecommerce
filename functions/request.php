<?php
/* ============================================
   REQUEST HELPERS - Procedural
   Centralized request getters + sanitize + validate
============================================ */

require_once __DIR__ . '/validation.php';

function request_method_is(string $method): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === strtoupper($method);
}

function request_post_string(string $key, string $type = 'string', string $default = ''): string
{
    $raw = $_POST[$key] ?? $default;
    if (!is_string($raw) && !is_numeric($raw)) {
        return $default;
    }
    return (string) sanitize_input((string) $raw, $type);
}

function request_post_int(string $key, int $default = 0): int
{
    $raw = $_POST[$key] ?? $default;
    return (int) sanitize_input($raw, 'int');
}

function request_post_float(string $key, float $default = 0.0): float
{
    $raw = $_POST[$key] ?? $default;
    return (float) sanitize_input($raw, 'float');
}

function request_post_bool(string $key): bool
{
    return isset($_POST[$key]);
}

/**
 * Sanitizes a subset of inputs before validate_form_data.
 * $schema: ['email' => 'email', 'name' => 'string', 'price' => 'float']
 */
function request_sanitize(array $input, array $schema): array
{
    $out = $input;
    foreach ($schema as $field => $type) {
        if (array_key_exists($field, $out)) {
            $out[$field] = sanitize_input($out[$field], (string) hint_sanitize_type($type));
        }
    }
    return $out;
}

function hint_sanitize_type(string $type): string
{
    $type = strtolower(trim($type));
    if ($type === 'int' || $type === 'integer') return 'int';
    if ($type === 'float' || $type === 'number') return 'float';
    if ($type === 'email') return 'email';
    if ($type === 'url') return 'url';
    if ($type === 'html') return 'html';
    return 'string';
}

/**
 * Convenience: sanitize then validate.
 */
function request_validate(array $input, array $rules, array $sanitize_schema = []): array
{
    $sanitized = $sanitize_schema ? request_sanitize($input, $sanitize_schema) : $input;
    return validate_form_data($sanitized, $rules);
}
