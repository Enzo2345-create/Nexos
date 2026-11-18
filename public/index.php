<?php
declare(strict_types=1);

$base = dirname(dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim(str_replace('\\', '/', $base), '/'));

spl_autoload_register(function (string $class) {
    $file = __DIR__ . '/../app/' .
        str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/../app/helpers.php';

$url = explode('/', trim(($_GET['url'] ?? '') ?: 'home/index', '/'));

$controller = 'App\\Controllers\\' . ucfirst($url[0]) . 'Controller';
$method = $url[1] ?? 'index';
$params = array_slice($url, 2);

if (
    !class_exists($controller) ||
    !method_exists($controller, $method) ||
    !(new ReflectionMethod($controller, $method))->isPublic()
) {
    http_response_code(404);
    exit('Página não encontrada');
}

try {
    (new $controller())->$method(...$params);
} catch (PDOException $e) {
    error_log('[nexos] ' . $e->getMessage());
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="pt-BR"><meta charset="UTF-8"><title>Banco indisponível</title>'
        . '<body style="font-family:sans-serif;max-width:560px;margin:15vh auto;padding:0 1rem">'
        . '<h1>Não foi possível acessar o banco de dados</h1>'
        . '<p>Verifique se o <strong>MySQL</strong> está iniciado no painel do XAMPP e se os dados em '
        . '<code>config/database.php</code> estão corretos.</p>'
        . '<p><small>Detalhe técnico: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></p>'
        . '</body></html>';
}