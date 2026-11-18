<?php
namespace App\Services;

use App\Models\Database;

/**
 * Lê feeds RSS/Atom, guarda no banco só o que tem a ver com o tema do fórum
 * (título, resumo curto e link da matéria original) e evita repetidas pelo link.
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
            @unlink($arquivo); // banco fora do ar: tenta de novo na próxima visita
            error_log('[nexos] importação: ' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[nexos] importação: ' . $e->getMessage());
        }
    }

    /** @return array{novas:int, erros:string[]} */
    public static function importar(): array
    {
        $config = require __DIR__ . '/../../config/feeds.php';
        $palavras = array_map([self::class, 'normalizar'], $config['palavras_chave']);
        $limiteData = time() - ((int) $config['max_dias']) * 86400;

        $pdo = Database::connect();
        $existe = $pdo->prepare('SELECT 1 FROM noticia WHERE link = ? LIMIT 1');
        $insere = $pdo->prepare(
            'INSERT INTO noticia (rotulo, classe_badge, titulo, resumo, link, destaque, publicado, data_publicacao)
             VALUES (?, ?, ?, ?, ?, 0, 1, ?)'
        );

        $novas = 0;
        $erros = [];

        foreach ($config['feeds'] as $feed) {
            $corpo = self::baixar($feed['url']);
            if ($corpo === null) {
                $erros[] = 'Não foi possível baixar ' . $feed['url'];
                continue;
            }

            foreach (self::lerItens($corpo) as $item) {
                if ($novas >= (int) $config['por_execucao']) {
                    break 2;
                }
                if (!preg_match('#^https?://#i', $item['link'])) {
                    continue; // só aceita links http/https
                }

                $titulo = self::limpar($item['titulo']);
                $resumo = self::limpar($item['resumo']);
                $texto = self::normalizar($titulo . ' ' . $resumo);

                if (!self::temTema($texto, $palavras)) {
                    continue;
                }

                $data = strtotime($item['data']) ?: time();
                $data = min($data, time());
                if ($data < $limiteData) {
                    continue;
                }

                $existe->execute([$item['link']]);
                if ($existe->fetchColumn()) {
                    continue;
                }

                $insere->execute([
                    mb_substr($feed['nome'], 0, 40),
                    self::classeBadge($texto),
                    self::cortar($titulo, 200),
                    self::cortar($resumo, 300),
                    mb_substr($item['link'], 0, 255),
                    date('Y-m-d H:i:s', $data),
                ]);
                $novas++;
            }
        }

        return ['novas' => $novas, 'erros' => $erros];
    }

    private static function baixar(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 3,
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

    /** @return array<int, array{titulo:string, link:string, resumo:string, data:string}> */
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
                ];
            }
        } elseif (isset($xml->entry)) { // Atom
            foreach ($xml->entry as $e) {
                $itens[] = [
                    'titulo' => (string) $e->title,
                    'link' => trim((string) $e->link['href']),
                    'resumo' => (string) ($e->summary ?: $e->content),
                    'data' => (string) ($e->updated ?: $e->published),
                ];
            }
        }

        return $itens;
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

    private static function temTema(string $texto, array $palavras): bool
    {
        foreach ($palavras as $palavra) {
            if ($palavra !== '' && strpos($texto, $palavra) !== false) {
                return true;
            }
        }

        return false;
    }

    /** Escolhe a cor da etiqueta de acordo com o assunto. */
    private static function classeBadge(string $texto): string
    {
        if (preg_match('/indigen|quilombol/', $texto)) {
            return 'badge-indigena';
        }
        if (preg_match('/xenofob|imigrant|refugiad|migrant/', $texto)) {
            return 'badge-imigracao';
        }
        if (preg_match('/escola|educacao|ensino|professor|estudante|10\.639/', $texto)) {
            return 'badge-educacao';
        }

        return 'badge-historia';
    }
}
