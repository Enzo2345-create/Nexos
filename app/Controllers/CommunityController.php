<?php
namespace App\Controllers;

use App\Models\Database;

/** JSON endpoints for forum discussions and article reviews. */
class CommunityController
{
    public function index(): void
    {
        $this->json(array_map(static function ($t) {
            $t['respostas'] = (int)$t['respostas']; $t['autorId']=(int)$t['id_usuario'];
            $t['id'] = (int)$t['id_topico'];
            $t['curtidas'] = 0;
            $t['quando'] = date('d/m/Y', strtotime($t['criado_em']));
            $t['autor'] = $t['nome_pessoa'];
            $t['texto'] = $t['mensagem'];
            return $t;
        }, Database::connect()->query('SELECT t.id_topico,t.id_usuario,t.categoria AS cat,t.titulo,t.mensagem,p.nome_pessoa,t.criado_em,COUNT(r.id_resposta) AS respostas FROM Topico t JOIN Pessoa p ON p.id_pessoa=t.id_usuario AND p.ativo=1 LEFT JOIN Resposta r ON r.id_topico=t.id_topico AND r.publicado=1 WHERE t.publicado=1 GROUP BY t.id_topico ORDER BY t.criado_em DESC')->fetchAll()));
    }

    public function ranking(): void
    {
        $rows=Database::connect()->query('SELECT p.id_pessoa AS id_usuario,p.nome_pessoa,COALESCE(SUM(pt.pontos),0) AS pontos FROM Pontuacao pt JOIN Pessoa p ON p.id_pessoa=pt.id_usuario AND p.ativo=1 WHERE pt.criado_em >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) GROUP BY p.id_pessoa ORDER BY pontos DESC LIMIT 10')->fetchAll();
        $this->json($rows);
    }

    public function quiz(): void
    {
        $db = Database::connect();
        $today = $this->quizDate();
        $this->ensureDailyQuiz($db, $today);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $stmt = $db->prepare(
            'SELECT d.id_quiz,d.pergunta,d.opcao_a,d.opcao_b,d.opcao_c,d.opcao_d,d.fonte,
                    r.escolha AS escolha_anterior,r.correta AS acertou_antes,r.pontos AS pontos_anteriores,
                    d.correta AS resposta_correta
             FROM QuizDiario d
             LEFT JOIN QuizResposta r ON r.data_quiz=d.data_quiz AND r.id_quiz=d.id_quiz AND r.id_usuario=?
             WHERE d.data_quiz=? ORDER BY d.ordem'
        );
        $stmt->execute([$userId ?: null, $today]);
        $rows = $stmt->fetchAll();
        $questions = array_map(static function ($q) {
            $answered = $q['escolha_anterior'] !== null;
            return [
                'id' => (int)$q['id_quiz'],
                'p' => $q['pergunta'],
                'o' => [$q['opcao_a'], $q['opcao_b'], $q['opcao_c'], $q['opcao_d']],
                'answered' => $answered,
                'choice' => $answered ? (int)$q['escolha_anterior'] : null,
                'correct' => $answered ? (bool)$q['acertou_antes'] : null,
                'correctChoice' => $answered ? (int)$q['resposta_correta'] : null,
                'earned' => $answered ? (int)$q['pontos_anteriores'] : 0,
                'source' => $answered ? $q['fonte'] : null,
            ];
        }, $rows);
        $this->json(['date' => $today, 'questions' => $questions, 'correctPoints' => 10, 'incorrectPoints' => 2]);
    }

    public function create(): void
    {
        $this->requirePost(); $u=$this->user(); $this->csrf();
        $title=trim($_POST['title'] ?? ''); $message=trim($_POST['message'] ?? ''); $cat=$_POST['category'] ?? '';
        if (mb_strlen($title)<5 || mb_strlen($title)>160 || mb_strlen($message)<10 || mb_strlen($message)>4000 || !in_array($cat,['historia','indigena','educacao','imigracao'],true)) $this->json(['error'=>'Confira o título, a categoria e a mensagem.'],422);
        $db=Database::connect(); $s=$db->prepare('INSERT INTO Topico(id_usuario,categoria,titulo,mensagem) VALUES(?,?,?,?)'); $s->execute([$u['id'],$cat,$title,$message]);
        $this->award((int)$u['id'],'topico',(string)$db->lastInsertId(),20);
        $this->json(['ok'=>true,'id'=>(int)$db->lastInsertId()],201);
    }

    public function replies(string $id=''): void
    {
        $stmt=Database::connect()->prepare('SELECT r.id_resposta,r.id_usuario,r.mensagem,r.criado_em,p.nome_pessoa FROM Resposta r JOIN Pessoa p ON p.id_pessoa=r.id_usuario AND p.ativo=1 WHERE r.id_topico=? AND r.publicado=1 ORDER BY r.criado_em'); $stmt->execute([(int)$id]); $this->json($stmt->fetchAll());
    }

    public function reply(string $id=''): void
    {
        $this->requirePost(); $u=$this->user(); $this->csrf(); $message=trim($_POST['message']??'');
        if (mb_strlen($message)<2 || mb_strlen($message)>3000) $this->json(['error'=>'A resposta deve ter de 2 a 3000 caracteres.'],422);
        $db=Database::connect(); $s=$db->prepare('INSERT INTO Resposta(id_topico,id_usuario,mensagem) SELECT id_topico,?,? FROM Topico WHERE id_topico=? AND publicado=1'); $s->execute([$u['id'],$message,(int)$id]);
        if (!$s->rowCount()) $this->json(['error'=>'Discussão não encontrada.'],404);
        $this->json(['ok'=>true],201);
    }

    public function removeTopic(string $id=''): void
    {
        $this->requirePost(); $u=$this->user(); $this->csrf(); $db=Database::connect();
        $sql=$u['role']==='administrador'?'DELETE FROM Topico WHERE id_topico=?':'DELETE FROM Topico WHERE id_topico=? AND id_usuario=?';
        $stmt=$db->prepare($sql); $stmt->execute($u['role']==='administrador'?[(int)$id]:[(int)$id,(int)$u['id']]);
        if (!$stmt->rowCount()) $this->json(['error'=>'Discussão não encontrada ou sem permissão.'],404);
        $this->json(['ok'=>true]);
    }

    public function updateTopic(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$title=trim($_POST['title']??'');$message=trim($_POST['message']??'');
        if(mb_strlen($title)<5||mb_strlen($title)>160||mb_strlen($message)<10||mb_strlen($message)>4000)$this->json(['error'=>'Confira o título e a mensagem.'],422);
        $s=Database::connect()->prepare('UPDATE Topico SET titulo=?,mensagem=?,atualizado_em=NOW() WHERE id_topico=? AND id_usuario=? AND publicado=1');$s->execute([$title,$message,(int)$id,(int)$u['id']]);if(!$s->rowCount())$this->json(['error'=>'Discussão não encontrada ou sem permissão.'],404);$this->json(['ok'=>true]);
    }

    public function removeReply(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$sql=$u['role']==='administrador'?'DELETE FROM Resposta WHERE id_resposta=?':'DELETE FROM Resposta WHERE id_resposta=? AND id_usuario=?';$s=Database::connect()->prepare($sql);$s->execute($u['role']==='administrador'?[(int)$id]:[(int)$id,(int)$u['id']]);if(!$s->rowCount())$this->json(['error'=>'Resposta não encontrada ou sem permissão.'],404);$this->json(['ok'=>true]);
    }

    public function updateReply(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$message=trim($_POST['message']??'');if(mb_strlen($message)<2||mb_strlen($message)>3000)$this->json(['error'=>'A resposta deve ter de 2 a 3000 caracteres.'],422);$s=Database::connect()->prepare('UPDATE Resposta SET mensagem=? WHERE id_resposta=? AND id_usuario=? AND publicado=1');$s->execute([$message,(int)$id,(int)$u['id']]);if(!$s->rowCount())$this->json(['error'=>'Resposta não encontrada ou sem permissão.'],404);$this->json(['ok'=>true]);
    }

    public function reviews(string $id=''): void
    {
        $stmt=Database::connect()->prepare('SELECT r.id_resenha,r.id_usuario,r.texto,r.data_post,p.nome_pessoa,COALESCE(AVG(a.nota),0) AS media,COUNT(a.id_usuario) AS votos FROM Resenha r JOIN Pessoa p ON p.id_pessoa=r.id_usuario AND p.ativo=1 LEFT JOIN AvaliacaoResenha a ON a.id_resenha=r.id_resenha WHERE r.id_artigo=? AND r.publicado=1 GROUP BY r.id_resenha ORDER BY media DESC,r.data_post DESC'); $stmt->execute([(int)$id]); $this->json($stmt->fetchAll());
    }

    public function review(): void
    {
        $this->requirePost(); $u=$this->user(); $this->csrf(); $article=(int)($_POST['article_id']??0); $text=trim($_POST['text']??'');
        if (!$article || mb_strlen($text)<20 || mb_strlen($text)>5000) $this->json(['error'=>'A resenha deve ter entre 20 e 5000 caracteres.'],422);
        $db=Database::connect(); $s=$db->prepare('INSERT INTO Resenha(id_artigo,autor,texto,avaliacao,id_usuario) SELECT id_artigo,?,?,0,? FROM Artigo WHERE id_artigo=? AND publicado=1'); $s->execute([$u['name'],$text,$u['id'],$article]);
        if (!$s->rowCount()) $this->json(['error'=>'Artigo não encontrado.'],404);
        $this->json(['ok'=>true],201);
    }

    public function updateReview(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$text=trim($_POST['text']??'');if(mb_strlen($text)<20||mb_strlen($text)>5000)$this->json(['error'=>'A resenha deve ter entre 20 e 5000 caracteres.'],422);$s=Database::connect()->prepare('UPDATE Resenha SET texto=?,atualizado_em=NOW() WHERE id_resenha=? AND id_usuario=? AND publicado=1');$s->execute([$text,(int)$id,(int)$u['id']]);if(!$s->rowCount())$this->json(['error'=>'Resenha não encontrada ou sem permissão.'],404);$this->json(['ok'=>true]);
    }

    public function removeReview(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$sql=$u['role']==='administrador'?'DELETE FROM Resenha WHERE id_resenha=?':'DELETE FROM Resenha WHERE id_resenha=? AND id_usuario=?';$s=Database::connect()->prepare($sql);$s->execute($u['role']==='administrador'?[(int)$id]:[(int)$id,(int)$u['id']]);if(!$s->rowCount())$this->json(['error'=>'Resenha não encontrada ou sem permissão.'],404);$this->json(['ok'=>true]);
    }

    public function rate(): void
    {
        $this->requirePost(); $u=$this->user(); $this->csrf(); $id=(int)($_POST['review_id']??0); $rating=(int)($_POST['rating']??0);
        if ($rating<1 || $rating>5) $this->json(['error'=>'Escolha uma nota de 1 a 5.'],422);
        $s=Database::connect()->prepare('INSERT INTO AvaliacaoResenha(id_resenha,id_usuario,nota) SELECT id_resenha,?,? FROM Resenha WHERE id_resenha=? AND publicado=1 ON DUPLICATE KEY UPDATE nota=VALUES(nota),criado_em=CURRENT_TIMESTAMP'); $s->execute([$u['id'],$rating,$id]); $this->json(['ok'=>true]);
    }

    public function answer(): void
    {
        $this->requirePost();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->csrf();
        $user = $_SESSION['user'] ?? null;
        $question = (int)($_POST['question'] ?? 0);
        $choice = (int)($_POST['choice'] ?? -1);
        if ($question < 1 || $choice < 0 || $choice > 3) $this->json(['error' => 'Resposta inválida.'], 422);

        $db = Database::connect();
        $today = $this->quizDate();
        $find = $db->prepare('SELECT correta,fonte FROM QuizDiario WHERE data_quiz=? AND id_quiz=?');
        $find->execute([$today, $question]);
        $dailyQuestion = $find->fetch();
        if (!$dailyQuestion) $this->json(['error' => 'Esta pergunta não faz parte do quiz de hoje.'], 404);

        $correct = $choice === (int)$dailyQuestion['correta'];
        $earned = $correct ? 10 : 2;
        $points = 0;
        $alreadyAnswered = false;

        if ($user) {
            $db->beginTransaction();
            try {
                $save = $db->prepare('INSERT INTO QuizResposta(id_usuario,data_quiz,id_quiz,escolha,correta,pontos) VALUES(?,?,?,?,?,?)');
                $save->execute([(int)$user['id'], $today, $question, $choice, $correct ? 1 : 0, $earned]);
                $reference = 'diario-' . $today . '-' . $question;
                $award = $db->prepare('INSERT IGNORE INTO Pontuacao(id_usuario,atividade,referencia,pontos) VALUES(?,?,?,?)');
                $award->execute([(int)$user['id'], 'quiz_diario', $reference, $earned]);
                $db->commit();
            } catch (\PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                if ($e->getCode() !== '23000') throw $e;
                $alreadyAnswered = true;
                $prior = $db->prepare('SELECT escolha,correta,pontos FROM QuizResposta WHERE id_usuario=? AND data_quiz=? AND id_quiz=?');
                $prior->execute([(int)$user['id'], $today, $question]);
                $previous = $prior->fetch();
                if (!$previous) throw $e;
                $choice = (int)$previous['escolha'];
                $correct = (bool)$previous['correta'];
                $earned = (int)$previous['pontos'];
            }
            $sum = $db->prepare('SELECT COALESCE(SUM(pontos),0) FROM Pontuacao WHERE id_usuario=?');
            $sum->execute([(int)$user['id']]);
            $points = (int)$sum->fetchColumn();
        } else {
            $earned = 0;
        }

        $this->json([
            'correct' => $correct,
            'correctChoice' => (int)$dailyQuestion['correta'],
            'choice' => $choice,
            'earned' => $earned,
            'points' => $points,
            'alreadyAnswered' => $alreadyAnswered,
            'source' => $dailyQuestion['fonte'],
        ]);
    }

    public function readArticle(string $id=''): void
    {
        $this->requirePost();$u=$this->user();$this->csrf();$article=(int)$id;$check=Database::connect()->prepare('SELECT 1 FROM Artigo WHERE id_artigo=? AND publicado=1');$check->execute([$article]);if(!$check->fetchColumn())$this->json(['error'=>'Artigo não encontrado.'],404);$this->award((int)$u['id'],'leitura',(string)$article,5);$sum=Database::connect()->prepare('SELECT COALESCE(SUM(pontos),0) FROM Pontuacao WHERE id_usuario=?');$sum->execute([(int)$u['id']]);$this->json(['points'=>(int)$sum->fetchColumn()]);
    }

    private function award(int $user,string $activity,string $ref,int $points): bool { $s=Database::connect()->prepare('INSERT IGNORE INTO Pontuacao(id_usuario,atividade,referencia,pontos) VALUES(?,?,?,?)'); $s->execute([$user,$activity,$ref,$points]); return $s->rowCount()===1; }

    private function quizDate(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('America/Bahia')))->format('Y-m-d');
    }

    private function ensureDailyQuiz(\PDO $db, string $today): void
    {
        $db->beginTransaction();
        try {
            // Um registro fixo serializa a criação do quiz do dia, inclusive se várias pessoas
            // abrirem a página ao mesmo tempo.
            $db->query('SELECT id FROM QuizRotacao WHERE id=1 FOR UPDATE')->fetchColumn();
            $check = $db->prepare('SELECT COUNT(*) FROM QuizDiario WHERE data_quiz=?');
            $check->execute([$today]);
            $existing = (int)$check->fetchColumn();
            if ($existing >= 10) {
=======
            if ((int)$check->fetchColumn() > 0) {
>>>>>>> 3fd4d2494a0669337856496ae2dabcc86dca41f0
                $db->commit();
                return;
            }

<<<<<<< HEAD
            $missing = 10 - $existing;
            $selectAvailable = static function () use ($db, $today): array {
                $stmt = $db->prepare(
                    'SELECT q.id_quiz,q.pergunta,q.opcao_a,q.opcao_b,q.opcao_c,q.opcao_d,q.correta,q.fonte
                     FROM Quiz q
                     LEFT JOIN QuizUso u ON u.id_quiz=q.id_quiz
                     WHERE q.publicado=1 AND q.fonte IS NOT NULL AND q.fonte<>\'\'
                       AND u.id_quiz IS NULL
                       AND NOT EXISTS (SELECT 1 FROM QuizDiario d WHERE d.data_quiz=? AND d.id_quiz=q.id_quiz)
                     ORDER BY RAND()'
                );
                $stmt->execute([$today]);
                return $stmt->fetchAll();
            };
            $available = $selectAvailable();
            if (count($available) < $missing) {
                // Reinicia o ciclo sem liberar perguntas já usadas no quiz de hoje.
                $reset = $db->prepare(
                    'DELETE FROM QuizUso WHERE id_quiz NOT IN
                     (SELECT id_quiz FROM QuizDiario WHERE data_quiz=?)'
                );
                $reset->execute([$today]);
                $available = $selectAvailable();
=======
            $available = $db->query(
                'SELECT q.id_quiz,q.pergunta,q.opcao_a,q.opcao_b,q.opcao_c,q.opcao_d,q.correta,q.fonte
                 FROM Quiz q LEFT JOIN QuizUso u ON u.id_quiz=q.id_quiz
                 WHERE q.publicado=1 AND q.fonte IS NOT NULL AND q.fonte<>\'\' AND u.id_quiz IS NULL ORDER BY RAND()'
            )->fetchAll();
            if (!$available) {
                // O banco todo já apareceu. Começa um novo ciclo, evitando repetição até aqui.
                $db->exec('DELETE FROM QuizUso');
                $available = $db->query(
                    'SELECT id_quiz,pergunta,opcao_a,opcao_b,opcao_c,opcao_d,correta,fonte
                     FROM Quiz WHERE publicado=1 AND fonte IS NOT NULL AND fonte<>\'\' ORDER BY RAND()'
                )->fetchAll();
>>>>>>> 3fd4d2494a0669337856496ae2dabcc86dca41f0
            }

            $insertDaily = $db->prepare(
                'INSERT INTO QuizDiario(data_quiz,id_quiz,ordem,pergunta,opcao_a,opcao_b,opcao_c,opcao_d,correta,fonte)
                 VALUES(?,?,?,?,?,?,?,?,?,?)'
            );
<<<<<<< HEAD
            $markUsed = $db->prepare('INSERT INTO QuizUso(id_quiz,data_uso) VALUES(?,?) ON DUPLICATE KEY UPDATE data_uso=VALUES(data_uso)');
            foreach (array_slice($available, 0, $missing) as $offset => $q) {
                $order = $existing + $offset + 1;
                $insertDaily->execute([$today,(int)$q['id_quiz'],$order,$q['pergunta'],$q['opcao_a'],$q['opcao_b'],$q['opcao_c'],$q['opcao_d'],(int)$q['correta'],$q['fonte']]);
=======
            $markUsed = $db->prepare('INSERT INTO QuizUso(id_quiz,data_uso) VALUES(?,?)');
            foreach (array_slice($available, 0, 5) as $order => $q) {
                $insertDaily->execute([$today,(int)$q['id_quiz'],$order+1,$q['pergunta'],$q['opcao_a'],$q['opcao_b'],$q['opcao_c'],$q['opcao_d'],(int)$q['correta'],$q['fonte']]);
>>>>>>> 3fd4d2494a0669337856496ae2dabcc86dca41f0
                $markUsed->execute([(int)$q['id_quiz'],$today]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function user(): array { if (session_status()===PHP_SESSION_NONE) session_start(); if (empty($_SESSION['user']['id'])) $this->json(['error'=>'Entre na sua conta para continuar.'],401); return $_SESSION['user']; }
    private function csrf(): void { if (session_status()===PHP_SESSION_NONE) session_start(); if (!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) $this->json(['error'=>'Sessão expirada. Atualize a página.'],403); }
    private function requirePost(): void { if ($_SERVER['REQUEST_METHOD']!=='POST') $this->json(['error'=>'Método não permitido.'],405); }
    private function json($data,int $status=200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
}
