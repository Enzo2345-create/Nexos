<?php
declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $file = __DIR__ . '/../app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$resultado = App\Services\FeedImporter::importar();

echo "Notícias novas gravadas: {$resultado['novas']}\n";
foreach ($resultado['erros'] as $erro) {
    echo "Aviso: $erro\n";
}
