<?php
namespace App\Controllers;

class SobreController
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Para incluir foto: salve em public/assets/equipe/ e preencha 'foto' (ex.: 'assets/equipe/artur.jpg').
        // Para incluir um texto pessoal: preencha 'bio'.
        $integrantes = [
            ['nome' => 'Artur Rebouças Lima Costa',  'funcao' => 'Integrante da equipe', 'bio' => '', 'foto' => 'assets/equipe/artur.jpg'],
            ['nome' => 'Enzo Vinícius da Cruz Chaves', 'funcao' => 'Integrante da equipe', 'bio' => '', 'foto' => 'assets/equipe/enzo.jpg'],
            ['nome' => 'Gustavo Ribeiro Neves',       'funcao' => 'Integrante da equipe', 'bio' => '', 'foto' => 'assets/equipe/gustavo.jpg'],
            ['nome' => 'Jan Pedro Souza Caires',      'funcao' => 'Integrante da equipe', 'bio' => '', 'foto' => 'assets/equipe/jan-pedro.jpg'],
        ];
        $orientadores = [
            'Prof. Cleiton de Miranda Brito',
            'Prof. Welison de Brito dos Santos Batista',
            'Prof. Jamile da Silva Ramos',
        ];

        $title = 'Sobre nós | Nexos';
        $base = __DIR__ . '/../Views/';
        $renderFile = static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        };
        $content = $renderFile($base . 'sobre/index.php', compact('integrantes', 'orientadores'));
        echo $renderFile($base . 'layouts/main.php', compact('title', 'content'));
    }
}
