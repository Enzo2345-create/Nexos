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
     * Artigos da equipe (sem link) primeiro; depois os externos mais recentes (com link).
     */
    public static function artigos(int $limiteExternos = 6): array
    {
        $pdo = Database::connect();
        $base = 'SELECT a.id_artigo, a.titulo, a.resumo, a.conteudo, a.imagem, a.imagem_alt, a.icone,
                        a.link, a.fonte, c.slug, c.nome AS categoria
                 FROM artigo a
                 INNER JOIN categoria c ON c.id_categoria = a.id_categoria
                 WHERE a.publicado = 1 ';

        $proprios = $pdo
            ->query($base . 'AND a.link IS NULL ORDER BY a.data_publicacao ASC, a.id_artigo ASC')
            ->fetchAll();

        $stmt = $pdo->prepare(
            $base . 'AND a.link IS NOT NULL ORDER BY a.data_publicacao DESC, a.id_artigo DESC LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limiteExternos, \PDO::PARAM_INT);
        $stmt->execute();

        return array_merge($proprios, $stmt->fetchAll());
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
