<?php
// Importação manual das notícias.
//   Importar:  C:\\xampp\\php\\php.exe C:\\xampp\\htdocs\\nexos\\bin\\importar_noticias.php
//   Só simular (não grava, mostra o que entraria e a pontuação):  ... importar_noticias.php --simular
declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $file = __DIR__ . '/../app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$simular = in_array('--simular', $argv, true);
$resultado = App\Services\FeedImporter::importar(!$simular);

echo ($simular ? "SIMULAÇÃO (nada foi gravado)\n" : '') . "\n";
foreach ($resultado['feeds'] as $f) {
    echo sprintf("%-4d lidas | %-3d aceitas | %s\n", $f['lidos'], $f['aceitos'], $f['url']);
}
echo "\n";
foreach ($resultado['aceitas'] as $linha) {
    echo "  + $linha\n";
}
echo "\n" . ($simular ? 'Entrariam' : 'Gravados') . ": {$resultado['noticias']} notícias e {$resultado['artigos']} artigos\n";
foreach ($resultado['erros'] as $erro) {
    echo "Aviso: $erro\n";
}
