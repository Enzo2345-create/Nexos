<?php
namespace App\Controllers;

use App\Models\Database;

class AdminController
{
    public function index(): void
    {
        $this->guard(); $db=Database::connect();
        $articles=$db->query('SELECT a.*,c.nome AS categoria FROM Artigo a JOIN Categoria c USING(id_categoria) ORDER BY a.data_publicacao DESC')->fetchAll();
        $news=$db->query('SELECT * FROM Noticia ORDER BY data_publicacao DESC')->fetchAll();
        $quizzes=$db->query('SELECT * FROM Quiz ORDER BY id_quiz')->fetchAll();
        $categories=$db->query('SELECT id_categoria,nome FROM Categoria ORDER BY nome')->fetchAll();
        $csrf=$this->csrfToken();
        $render=static function($file,$data){extract($data);ob_start();require $file;return ob_get_clean();};
        $content=$render(__DIR__.'/../Views/admin/index.php',compact('articles','news','quizzes','categories','csrf'));
        echo $render(__DIR__.'/../Views/layouts/main.php',['content'=>$content,'title'=>'Gestão de conteúdo | Nexos']);
    }

    public function saveArticle(): void
    {
        $this->guard(); $this->post(); $this->csrf();
        $id=(int)($_POST['id']??0); $category=(int)($_POST['category']??0); $title=trim($_POST['title']??''); $summary=trim($_POST['summary']??''); $body=trim($_POST['body']??'');
        if (!$category || mb_strlen($title)<5 || mb_strlen($title)>200 || mb_strlen($summary)<10 || mb_strlen($summary)>300 || mb_strlen($body)<20) { http_response_code(422); exit('Confira os campos do artigo (título, resumo e conteúdo).'); }
        $html='<p>'.implode('</p><p>',array_map(static fn($s)=>htmlspecialchars($s,ENT_QUOTES,'UTF-8'),preg_split('/\R\s*\R/', $body))).'</p>';
        $db=Database::connect();
        if ($id) { $s=$db->prepare('UPDATE Artigo SET id_categoria=?,titulo=?,resumo=?,conteudo=?,publicado=? WHERE id_artigo=?'); $s->execute([$category,$title,$summary,$html,isset($_POST['published'])?1:0,$id]); }
        else { $s=$db->prepare('INSERT INTO Artigo(id_categoria,titulo,resumo,conteudo,publicado,id_admin) VALUES(?,?,?,?,?,?)'); $s->execute([$category,$title,$summary,$html,isset($_POST['published'])?1:0,(int)$_SESSION['user']['id']]); }
        header('Location: '.url('admin/index')); exit;
    }

    public function deleteArticle(string $id=''): void
    {
        $this->guard();$this->post();$this->csrf();$s=Database::connect()->prepare('DELETE FROM Artigo WHERE id_artigo=?');$s->execute([(int)$id]);header('Location: '.url('admin/index'));exit;
    }

    public function saveNews(): void
    {
        $this->guard();$this->post();$this->csrf();$id=(int)($_POST['id']??0);$title=trim($_POST['title']??'');$summary=trim($_POST['summary']??'');$label=trim($_POST['label']??'Notícia');$link=trim($_POST['link']??'');
        if(mb_strlen($title)<5||mb_strlen($title)>200||mb_strlen($summary)<10||mb_strlen($summary)>300||($link!==''&&!filter_var($link,FILTER_VALIDATE_URL))){http_response_code(422);exit('Confira título, resumo e link da notícia.');}
        $db=Database::connect();
        if($id){$s=$db->prepare('UPDATE Noticia SET titulo=?,resumo=?,rotulo=?,link=?,publicado=?,destaque=? WHERE id_noticia=?');$s->execute([$title,$summary,$label,$link?:null,isset($_POST['published'])?1:0,isset($_POST['featured'])?1:0,$id]);}
        else{$s=$db->prepare('INSERT INTO Noticia(titulo,resumo,rotulo,link,publicado,destaque,id_admin) VALUES(?,?,?,?,?,?,?)');$s->execute([$title,$summary,$label,$link?:null,isset($_POST['published'])?1:0,isset($_POST['featured'])?1:0,(int)$_SESSION['user']['id']]);}
        header('Location: '.url('admin/index'));exit;
    }

    public function deleteNews(string $id=''): void
    {
        $this->guard();$this->post();$this->csrf();$s=Database::connect()->prepare('DELETE FROM Noticia WHERE id_noticia=?');$s->execute([(int)$id]);header('Location: '.url('admin/index'));exit;
    }

    public function saveQuiz(): void
    {
        $this->guard();$this->post();$this->csrf();$id=(int)($_POST['id']??0);$question=trim($_POST['question']??'');$options=[trim($_POST['a']??''),trim($_POST['b']??''),trim($_POST['c']??''),trim($_POST['d']??'')];$correct=(int)($_POST['correct']??-1);
        if(mb_strlen($question)<10||mb_strlen($question)>500||min(array_map('mb_strlen',$options))<1||max(array_map('mb_strlen',$options))>300||$correct<0||$correct>3){http_response_code(422);exit('Confira a pergunta, as quatro opções e a resposta correta.');}
        $db=Database::connect();if($id){$s=$db->prepare('UPDATE Quiz SET pergunta=?,opcao_a=?,opcao_b=?,opcao_c=?,opcao_d=?,correta=?,publicado=? WHERE id_quiz=?');$s->execute([$question,...$options,$correct,isset($_POST['published'])?1:0,$id]);}else{$s=$db->prepare('INSERT INTO Quiz(pergunta,opcao_a,opcao_b,opcao_c,opcao_d,correta,publicado) VALUES(?,?,?,?,?,?,?)');$s->execute([$question,...$options,$correct,isset($_POST['published'])?1:0]);}header('Location: '.url('admin/index'));exit;
    }

    public function deleteQuiz(string $id=''): void
    {
        $this->guard();$this->post();$this->csrf();$s=Database::connect()->prepare('DELETE FROM Quiz WHERE id_quiz=?');$s->execute([(int)$id]);header('Location: '.url('admin/index'));exit;
    }

    private function guard(): void { if(session_status()===PHP_SESSION_NONE)session_start(); if(($_SESSION['user']['role']??'')!=='administrador'){http_response_code(403);exit('Área exclusiva para administradores.');} }
    private function post(): void { if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método não permitido.');} }
    private function csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(403);exit('Token de segurança inválido.');} }
    private function csrfToken(): string { return $_SESSION['csrf']??=bin2hex(random_bytes(32)); }
}
