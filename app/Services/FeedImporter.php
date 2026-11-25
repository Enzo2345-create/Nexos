<?php
namespace App\Services;

use App\Models\Database;

/**
 * Lê feeds RSS/Atom e guarda no banco apenas o que tem a ver com diversidade étnico-racial:
 * título, resumo curto e link da matéria original (o texto completo fica no site de origem).
 * Notícias vão para a tabela noticia; artigos para a tabela artigo. Repetidos são ignorados pelo link.
 */
class FeedImporter
{
    /** Roda a importação no máximo uma vez a cada $horas horas. */
    public static function seNecessario(int $horas = 6): void
    {
        $dir = __DIR__ . '/../../storage';
        $arquivo = $dir . '/ultima_importacao.txt';

        if (is_file($arquivo) && time() - (int) @file_get_contents($arquivo) < $horas * 3600) {
            return;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($arquivo, (string) time()); // marca antes, para não rodar em duplicidade

        try {
            self::importar();
        } catch (\PDOException $e) {
            @unlink($arquivo); // banco fora do ar ou sem a migração: tenta de novo na próxima visita
            error_log('[nexos] importação: ' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[nexos] importação: ' . $e->getMessage());
        }
    }

    /**
     * @param bool $gravar false = só simula (não grava nada, mostra o que entraria e a pontuação)
     * @return array{noticias:int, artigos:int, novas:int, feeds:array, aceitas:string[], erros:string[]}
     */
    public static function importar(bool $gravar = true): array
    {
        $config = require __DIR__ . '/../../config/feeds.php';
        $inicio = microtime(true);

        $fortes = array_map([self::class, 'normalizar'], $config['termos_fortes']);
        $fracos = array_map([self::class, 'normalizar'], $config['termos_fracos']);
        $ignorar = array_map([self::class, 'normalizar'], $config['ignorar']);

        $pdo = Database::connect();

        $categorias = [];
        foreach ($pdo->query('SELECT id_categoria, slug FROM categoria') as $c) {
            $categorias[$c['slug']] = (int) $c['id_categoria'];
        }

        $existe = [
            'noticia' => $pdo->prepare('SELECT 1 FROM noticia WHERE link = ? LIMIT 1'),
            'artigo' => $pdo->prepare('SELECT 1 FROM artigo WHERE link = ? LIMIT 1'),
        ];
        $insereNoticia = $pdo->prepare(
            'INSERT INTO noticia (rotulo, classe_badge, titulo, resumo, link, destaque, publicado, data_publicacao)
             VALUES (?, ?, ?, ?, ?, 0, 1, ?)'
        );
        $insereArtigo = $pdo->prepare(
            'INSERT INTO artigo (id_categoria, titulo, resumo, conteudo, imagem, icone, link, fonte, publicado, data_publicacao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $atualizaImagem = $pdo->prepare('UPDATE artigo SET imagem = ? WHERE link = ? AND imagem IS NULL');

        $novos = ['noticia' => 0, 'artigo' => 0];
        $erros = [];
        $feedsInfo = [];
        $aceitas = [];
        $vistos = [];

        foreach ($config['feeds'] as $feed) {
            $tipo = ($feed['tipo'] ?? 'noticia') === 'artigo' ? 'artigo' : 'noticia';
            $limite = (int) $config['por_execucao'][$tipo];

            $feedsInfo[] = ['url' => $feed['url'], 'lidos' => 0, 'aceitos' => 0];
            $idx = count($feedsInfo) - 1;

            if ($novos[$tipo] >= $limite) {
                continue;
            }
            if (microtime(true) - $inicio > (int) $config['tempo_max_segundos']) {
                $erros[] = 'Tempo esgotado antes de ler ' . $feed['url'];
                continue;
            }

            $corpo = self::baixar($feed['url']);
            if ($corpo === null) {
                $erros[] = 'Não foi possível baixar ' . $feed['url'];
                continue;
            }

            $limiteData = time() - ((int) $config['max_dias'][$tipo]) * 86400;

            foreach (self::lerItens($corpo) as $item) {
                if ($novos[$tipo] >= $limite) {
                    break;
                }
                $feedsInfo[$idx]['lidos']++;

                if (!preg_match('#^https?://#i', $item['link'])) {
                    continue; // só aceita links http/https
                }

                $titulo = self::limpar($item['titulo']);
                $resumo = self::limpar($item['resumo']);
                $texto = self::normalizar($titulo . ' ' . $resumo);

                $pontos = self::pontuar($texto, $fortes, $fracos, $ignorar, (int) $config['minimo_termos_fracos']);
                if ($pontos === 0) {
                    continue;
                }

                $data = min(strtotime($item['data']) ?: time(), time());
                if ($data < $limiteData) {
                    continue;
                }

                if (isset($vistos[$item['link']])) {
                    continue;
                }
                $existe[$tipo]->execute([$item['link']]);
                if ($existe[$tipo]->fetchColumn()) {
                    if ($gravar && $tipo === 'artigo' && $item['imagem'] !== null) {
                        $atualizaImagem->execute([$item['imagem'], $item['link']]); // completa artigos antigos sem foto
                    }
                    continue;
                }

                $slug = self::categoria($texto);
                $dataSql = date('Y-m-d H:i:s', $data);

                // O feed não trouxe foto: usa a imagem de capa da própria matéria (og:image)
                if ($gravar && $tipo === 'artigo' && $item['imagem'] === null
                    && microtime(true) - $inicio < (int) $config['tempo_max_segundos']) {
                    $item['imagem'] = self::imagemDaPagina($item['link']);
                }

                if ($gravar) {
                    if ($tipo === 'noticia') {
                        $insereNoticia->execute([
                            mb_substr($feed['nome'], 0, 40),
                            'badge-' . $slug,
                            self::cortar($titulo, 200),
                            self::cortar($resumo, 300),
                            mb_substr($item['link'], 0, 255),
                            $dataSql,
                        ]);
                    } else {
                        $resumoCurto = self::cortar($resumo, 300);
                        $insereArtigo->execute([
                            $categorias[$slug] ?? reset($categorias),
                            self::cortar($titulo, 200),
                            $resumoCurto,
                            '<p>' . htmlspecialchars($resumoCurto, ENT_QUOTES, 'UTF-8') . '</p>',
                            $item['imagem'],
                            'bi-box-arrow-up-right',
                            mb_substr($item['link'], 0, 255),
                            mb_substr($feed['nome'], 0, 60),
                            $dataSql,
                        ]);
                    }
                }

                $vistos[$item['link']] = true;
                $novos[$tipo]++;
                $feedsInfo[$idx]['aceitos']++;
                $aceitas[] = sprintf(
                    '[%s] %d pts%s | %s (%s)',
                    $tipo,
                    $pontos,
                    $item['imagem'] !== null ? ' +foto' : '',
                    mb_substr($titulo, 0, 90),
                    $feed['nome']
                );
            }
        }

        // Completa a foto de artigos antigos que ficaram sem imagem (poucos por rodada)
        if ($gravar) {
            $semFoto = $pdo->query(
                'SELECT id_artigo, link FROM artigo WHERE link IS NOT NULL AND imagem IS NULL ORDER BY RAND() LIMIT 4'
            )->fetchAll();
            $salvaFoto = $pdo->prepare('UPDATE artigo SET imagem = ? WHERE id_artigo = ?');
            foreach ($semFoto as $s) {
                if (microtime(true) - $inicio > (int) $config['tempo_max_segundos'] + 8) {
                    break;
                }
                $img = self::imagemDaPagina($s['link']);
                if ($img !== null) {
                    $salvaFoto->execute([$img, $s['id_artigo']]);
                }
            }
        }

        return [
            'noticias' => $novos['noticia'],
            'artigos' => $novos['artigo'],
            'novas' => $novos['noticia'] + $novos['artigo'],
            'feeds' => $feedsInfo,
            'aceitas' => $aceitas,
            'erros' => $erros,
        ];
    }

    private static function baixar(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => 'NexosBot/1.0 (projeto escolar IF Baiano)',
            ]);
            $corpo = curl_exec($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($corpo === false) {
                error_log('[nexos] feed ' . $url . ': ' . curl_error($ch));
            }
            curl_close($ch);

            return ($corpo !== false && $codigo === 200) ? (string) $corpo : null;
        }

        $contexto = stream_context_create(['http' => [
            'timeout' => 4,
            'header' => "User-Agent: NexosBot/1.0 (projeto escolar IF Baiano)\r\n",
        ]]);
        $corpo = @file_get_contents($url, false, $contexto);

        return $corpo === false ? null : $corpo;
    }

    /** @return array<int, array{titulo:string, link:string, resumo:string, data:string, imagem:?string}> */
    private static function lerItens(string $xmlTexto): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlTexto, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if ($xml === false) {
            return [];
        }

        $itens = [];

        if (isset($xml->channel->item)) { // RSS 2.0
            foreach ($xml->channel->item as $i) {
                $itens[] = [
                    'titulo' => (string) $i->title,
                    'link' => trim((string) $i->link),
                    'resumo' => (string) $i->description,
                    'data' => (string) $i->pubDate,
                    'imagem' => self::imagemDoItem($i),
                ];
            }
        } elseif (isset($xml->entry)) { // Atom
            foreach ($xml->entry as $e) {
                $itens[] = [
                    'titulo' => (string) $e->title,
                    'link' => trim((string) $e->link['href']),
                    'resumo' => (string) ($e->summary ?: $e->content),
                    'data' => (string) ($e->updated ?: $e->published),
                    'imagem' => self::imagemDoItem($e),
                ];
            }
        }

        return $itens;
    }

    /** Procura a foto da matéria: enclosure, Media RSS ou a primeira <img> do conteúdo. */
    private static function imagemDoItem(\SimpleXMLElement $item): ?string
    {
        $candidatas = [];

        // RSS: <enclosure url="..." type="image/jpeg">
        foreach ($item->enclosure as $enc) {
            if (stripos((string) $enc['type'], 'image/') === 0) {
                $candidatas[] = (string) $enc['url'];
            }
        }
        // Atom: <link rel="enclosure" type="image/..." href="...">
        foreach ($item->link as $l) {
            if ((string) $l['rel'] === 'enclosure' && stripos((string) $l['type'], 'image/') === 0) {
                $candidatas[] = (string) $l['href'];
            }
        }

        // Media RSS: <media:content> e <media:thumbnail>
        $media = $item->children('http://search.yahoo.com/mrss/');
        foreach ($media->content as $m) {
            $url = (string) $m['url'];
            if ((string) $m['medium'] === 'image'
                || stripos((string) $m['type'], 'image/') === 0
                || preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url)) {
                $candidatas[] = $url;
            }
        }
        foreach ($media->thumbnail as $t) {
            $candidatas[] = (string) $t['url'];
        }

        // Primeira <img> do conteúdo completo ou do resumo
        $conteudo = $item->children('http://purl.org/rss/1.0/modules/content/');
        $trechos = [(string) $conteudo->encoded, (string) $item->description, (string) $item->content, (string) $item->summary];
        foreach ($trechos as $html) {
            if ($html !== '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
                $candidatas[] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        foreach ($candidatas as $url) {
            $url = trim($url);
            if (self::imagemValida($url)) {
                return $url;
            }
        }

        return null;
    }

    /** Abre a página da matéria e lê a imagem de capa (og:image ou twitter:image). */
    private static function imagemDaPagina(string $url): ?string
    {
        $html = self::baixar($url);
        if ($html === null) {
            return null;
        }
        $html = substr($html, 0, 300000);
        $padroes = [
            '/<meta[^>]+(?:property|name)=["\'](?:og:image(?::secure_url)?|twitter:image(?::src)?)["\'][^>]*content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\'](?:og:image(?::secure_url)?|twitter:image(?::src)?)["\']/i',
        ];
        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $html, $m)) {
                $img = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (strpos($img, '//') === 0) {
                    $img = 'https:' . $img;
                } elseif (isset($img[0]) && $img[0] === '/') {
                    $partes = parse_url($url);
                    $img = ($partes['scheme'] ?? 'https') . '://' . ($partes['host'] ?? '') . $img;
                }
                if (self::imagemValida($img)) {
                    return $img;
                }
            }
        }

        return null;
    }

    /** Aceita só http/https de até 255 caracteres e descarta pixels de rastreio, avatares e GIFs. */
    private static function imagemValida(string $url): bool
    {
        return $url !== ''
            && strlen($url) <= 255
            && preg_match('#^https?://#i', $url) === 1
            && preg_match('/pixel|tracking|feedburner|emoji|gravatar|avatar|spacer|\.gif(\?|$)/i', $url) !== 1;
    }

    private static function limpar(string $texto): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    private static function cortar(string $texto, int $max): string
    {
        return mb_strlen($texto) <= $max ? $texto : rtrim(mb_substr($texto, 0, $max - 1)) . '…';
    }

    private static function normalizar(string $texto): string
    {
        return strtr(mb_strtolower($texto, 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
    }

    /** O termo precisa começar numa palavra ("racial" não casa com "tracial"). */
    private static function contem(string $texto, string $termo): bool
    {
        return $termo !== ''
            && preg_match('/(?<![a-z0-9])' . preg_quote($termo, '/') . '/u', $texto) === 1;
    }

    /**
     * Pontuação do texto: 2 pontos por termo forte diferente, 1 por termo fraco diferente.
     * Retorna 0 (rejeitado) se não houver nenhum termo forte e os fracos forem menos que o mínimo.
     */
    private static function pontuar(string $texto, array $fortes, array $fracos, array $ignorar, int $minimoFracos): int
    {
        $pontos = 0;
        $temForte = false;
        foreach ($fortes as $termo) {
            if (self::contem($texto, $termo)) {
                $temForte = true;
                $pontos += 2;
            }
        }

        $texto = str_replace($ignorar, ' ', $texto);
        $achados = 0;
        foreach ($fracos as $termo) {
            if (self::contem($texto, $termo)) {
                $achados++;
            }
        }

        if (!$temForte && $achados < $minimoFracos) {
            return 0;
        }

        return $pontos + $achados;
    }

    /** Escolhe a categoria (e a cor da etiqueta) pelo assunto. */
    private static function categoria(string $texto): string
    {
        if (preg_match('/indigen|quilomb|funai/', $texto)) {
            return 'indigena';
        }
        if (preg_match('/xenofob|imigrant|refugiad|migrant/', $texto)) {
            return 'imigracao';
        }
        if (preg_match('/escola|educacao|ensino|professor|estudante|10\.639|11\.645/', $texto)) {
            return 'educacao';
        }

        return 'historia';
    }
}
