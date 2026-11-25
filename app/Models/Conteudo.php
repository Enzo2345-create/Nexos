<?php
namespace App\Models;

/**
 * Busca no banco os artigos e as notícias exibidos na página inicial.
 */
class Conteudo
{
    public static function categorias(): array
    {
        return Database::connect()
            ->query('SELECT slug, nome FROM categoria ORDER BY id_categoria')
            ->fetchAll();
    }

    /**
     * Artigos externos mais recentes (com link) e, como reserva, os artigos fixos da equipe (sem link).
     * Quando já há externos suficientes (config 'ocultar_fixos_com_externos'), os fixos não aparecem.
     */
    public static function artigos(int $limiteExternos = 6): array
    {
        $config = require __DIR__ . '/../../config/feeds.php';
        $minimo = (int) ($config['ocultar_fixos_com_externos'] ?? 0);

        $pdo = Database::connect();
        $base = 'SELECT a.id_artigo, a.titulo, a.resumo, a.conteudo, a.imagem, a.imagem_alt, a.icone,
                        a.link, a.fonte, c.slug, c.nome AS categoria,
                        COALESCE(p.nome_pessoa, a.fonte, \'Equipe Nexos\') AS autor
                 FROM artigo a
                 INNER JOIN categoria c ON c.id_categoria = a.id_categoria
                 LEFT JOIN Administrador ad ON ad.id_pessoa=a.id_admin
                 LEFT JOIN Pessoa p ON p.id_pessoa=ad.id_pessoa
                 WHERE a.publicado = 1 ';

        $stmt = $pdo->prepare(
            $base . 'AND a.link IS NOT NULL ORDER BY a.data_publicacao DESC, a.id_artigo DESC LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limiteExternos, \PDO::PARAM_INT);
        $stmt->execute();
        $externos = $stmt->fetchAll();

        // Artigos externos que já receberam resenhas continuam na lista, mesmo quando saem dos mais recentes.
        $ids = array_column($externos, 'id_artigo');
        $comResenha = $pdo->query(
            $base . 'AND a.link IS NOT NULL
                     AND EXISTS (SELECT 1 FROM Resenha r WHERE r.id_artigo = a.id_artigo AND r.publicado = 1)
                     ORDER BY a.data_publicacao DESC, a.id_artigo DESC'
        )->fetchAll();
        foreach ($comResenha as $r) {
            if (!in_array($r['id_artigo'], $ids)) {
                $externos[] = $r;
            }
        }

        if ($minimo > 0 && count($externos) >= $minimo) {
            return $externos;
        }

        $proprios = $pdo
            ->query($base . 'AND a.link IS NULL ORDER BY a.data_publicacao ASC, a.id_artigo ASC')
            ->fetchAll();

        return array_merge($proprios, $externos);
    }

    public static function noticiaDestaque(): ?array
    {
        $row = Database::connect()
            ->query(
                'SELECT id_noticia, rotulo, classe_badge, titulo, resumo, imagem, link, data_publicacao
                 FROM noticia
                 WHERE publicado = 1 AND destaque = 1
                 ORDER BY data_publicacao DESC
                 LIMIT 1'
            )
            ->fetch();

        return $row ?: null;
    }

    public static function noticias(int $limite = 4): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id_noticia, rotulo, classe_badge, titulo, resumo, link, data_publicacao
             FROM noticia
             WHERE publicado = 1 AND destaque = 0
             ORDER BY data_publicacao DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
