<main class="container py-5" style="min-height: 75vh">
    <div class="forum-card p-4 p-md-5 mx-auto" style="max-width: 480px">
        <span class="kicker">Comunidade Raízes</span>
        <h1 class="section-title mb-4">Reativar conta</h1>
        <p>Informe o e-mail e a senha da conta desativada para recuperar seu acesso.</p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= url('auth/reactivate') ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold" for="email">E-mail</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="150" autocomplete="email" value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" for="password">Senha da conta</label>
                <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-raizes w-100" type="submit">Reativar minha conta</button>
        </form>
        <a class="d-inline-block mt-3" href="<?= url('auth/login') ?>">Voltar para entrar</a>
    </div>
</main>
