<?php
namespace App\Controllers;

use App\Models\Conteudo;
use App\Services\FeedImporter;

class HomeController
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $csrfToken = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $userPoints = 0;
        if (!empty($_SESSION['user']['id'])) {
            try {
                $stmt = \App\Models\Database::connect()->prepare('SELECT COALESCE(SUM(pontos),0) FROM Pontuacao WHERE id_usuario=?');
                $stmt->execute([(int)$_SESSION['user']['id']]);
                $userPoints = (int)$stmt->fetchColumn();
            } catch (\PDOException $e) { /* migrations may not have been applied yet */ }
        }
        $dados = [
            'categorias' => [],
            'artigos' => [],
            'noticiaDestaque' => null,
            'noticias' => [],
            'conteudoErro' => '',
        ];

        // Busca notícias novas na internet (no máximo a cada 6 horas) antes de ler do banco.
        FeedImporter::seNecessario(6);

        // Se o banco estiver fora do ar ou sem as tabelas, a página abre mesmo assim.
        try {
            $dados['categorias'] = Conteudo::categorias();
            $dados['artigos'] = Conteudo::artigos();
            $dados['noticiaDestaque'] = Conteudo::noticiaDestaque();
            $dados['noticias'] = Conteudo::noticias(4);
        } catch (\PDOException $e) {
            error_log('[nexos] conteúdo: ' . $e->getMessage());
            $dados['conteudoErro'] = 'Não foi possível carregar o conteúdo do banco. '
                . 'Confira se o MySQL está ligado e se os arquivos database/conteudo.sql e database/externos.sql foram executados.';
        }

        $this->render('home/index', $dados + [
            'csrfToken' => $csrfToken,
            'userPoints' => $userPoints,
            'title' => 'Raízes | Fórum de Discussão sobre Diversidade Étnico-Racial',
            'pageScript' => 'js/script.js',
            'homePage' => true,
        ]);
    }

    private function render(string $view, array $data = []): void
    {
        $base = __DIR__ . '/../Views/';
        $renderFile = static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        };
        $content = $renderFile($base . $view . '.php', $data);
        $vars = $data + ['content' => $content];
        echo $renderFile($base . 'layouts/main.php', $vars);
    }
    private function csrfToken(): string {
        session_status() === PHP_SESSION_NONE && session_start();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }
    private function checkCsrf(): void {
        session_status() === PHP_SESSION_NONE && session_start();
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(403);
            exit('Token inválido');
        }
    }
}
