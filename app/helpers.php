<?php
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}
function asset(string $path): string {
    $file = __DIR__ . '/../public/' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : time();
    return BASE_URL . '/' . ltrim($path, '/') . '?v=' . $version;
}
function format_cpf(?string $cpf): string
{
    $digits = preg_replace('/\D/', '', (string) $cpf);

    if (strlen($digits) !== 11) {
        return (string) $cpf;
    }

    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
}

function dia_mes(string $data): array
{
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $t = strtotime($data);

    return [date('d', $t), $meses[(int) date('n', $t) - 1]];
}
