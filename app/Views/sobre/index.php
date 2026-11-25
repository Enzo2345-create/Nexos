<?php
$iniciais = static function (string $nome): string {
    $partes = preg_split('/\s+/', trim($nome));
    $a = mb_substr($partes[0] ?? '', 0, 1);
    $b = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($a . $b);
};
$cores = ['av-1', 'av-2', 'av-3', 'av-4'];
?>
  <div class="topbar">
    <div class="container d-flex justify-content-between gap-3">
      <span>Diversidade étnico-racial em debate</span>
      <span class="d-none d-md-inline"><a href="<?= url('#artigos') ?>">Artigos</a> · <a href="<?= url('#noticias') ?>">Notícias</a> · <a href="<?= url('#comunidade') ?>">Comunidade</a> · <a href="<?= url('#desafio') ?>">Quizes</a></span>
    </div>
  </div>

  <nav class="navbar navbar-expand-lg navbar-raizes sticky-top py-3">
    <div class="container">
      <a class="brand-raizes" href="<?= url('') ?>" aria-label="Nexos, voltar ao início">NEXOS<span class="dot" aria-hidden="true"></span></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item"><a class="nav-link" href="<?= url('') ?>">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('#artigos') ?>">Artigos</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('#noticias') ?>">Notícias</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('#comunidade') ?>">Comunidade</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('#glossario') ?>">Glossário</a></li>
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= url('sobre') ?>">Sobre nós</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <main>
    <section class="hero pb-4" id="inicio">
      <div class="container">
        <div class="edition-line">
          <span class="kicker">Quem faz o Nexos</span>
          <span class="kicker text-maroon">Nº 01 · 2026</span>
        </div>
        <div class="row">
          <div class="col-lg-9">
            <h1>Sobre <em>nós</em></h1>
            <p class="lead">
              O Nexos nasceu como Projeto Integrador I do Curso Técnico em Informática para Internet do
              IF Baiano, Campus Guanambi. Somos quatro estudantes que quiseram usar a tecnologia para abrir um
              espaço de conversa, leitura e aprendizado sobre a diversidade étnico-racial do Brasil, com respeito e escuta.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="section pt-4">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title">Integrantes</h2>
          <span class="kicker">Equipe de desenvolvimento</span>
        </div>
        <div class="row g-4">
          <?php foreach ($integrantes as $i => $p): ?>
            <div class="col-sm-6 col-lg-3">
              <article class="card-raizes team-card h-100">
                <div class="team-photo <?= e($cores[$i % 4]) ?>">
                  <?php if (!empty($p['foto'])): ?>
                    <img src="<?= asset($p['foto']) ?>" alt="Foto de <?= e($p['nome']) ?>">
                  <?php else: ?>
                    <span aria-hidden="true"><?= e($iniciais($p['nome'])) ?></span>
                  <?php endif; ?>
                </div>
                <div class="card-body-rz">
                  <span class="kicker"><?= e($p['funcao']) ?></span>
                  <h3><?= e($p['nome']) ?></h3>
                  <?php if (!empty($p['bio'])): ?><p class="mb-0"><?= e($p['bio']) ?></p><?php endif; ?>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section bg-sand">
      <div class="container">
        <div class="row g-4 g-lg-5">
          <div class="col-lg-6">
            <span class="kicker">Orientação</span>
            <h2 class="section-title mb-4" style="font-size:clamp(1.8rem,3.6vw,2.6rem)">Quem nos guiou</h2>
            <ul class="list-unstyled d-grid gap-3 mb-0">
              <?php foreach ($orientadores as $o): ?>
                <li class="d-flex align-items-center gap-3"><i class="bi bi-mortarboard-fill text-rust fs-4"></i><span class="fw-semibold"><?= e($o) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="col-lg-6">
            <span class="kicker">Nossa proposta</span>
            <h2 class="section-title mb-4" style="font-size:clamp(1.8rem,3.6vw,2.6rem)">Por que o Nexos?</h2>
            <p class="text-soft">
              Fórum, artigos, notícias e desafios num só lugar, para apoiar a aplicação da Lei 10.639/2003 na escola
              e levar o debate sobre diversidade para dentro e fora da sala de aula.
            </p>
            <a class="btn btn-raizes" href="<?= url('') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar ao site</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <div class="stripe-band"></div>
  <footer class="footer">
    <div class="container">
      <p class="small mb-0 text-center">© 2026 Nexos · Projeto Integrador I · IF Baiano, Campus Guanambi – BA</p>
    </div>
  </footer>
