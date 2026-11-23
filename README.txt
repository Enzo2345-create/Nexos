NEXOS — instalação local com XAMPP

1. Copie a pasta nexos para C:\xampp\htdocs\nexos.
2. Inicie Apache e MySQL no XAMPP.
3. No phpMyAdmin, importe nesta ordem:
   - database/database.sql
   - database/conteudo.sql
   - database/externos.sql (opcional, para importar artigos e notícias externas)
   - database/requisitos.sql
4. Confira config/database.php. O padrão usa MySQL local, usuário root e senha vazia.
5. Abra http://localhost/nexos/.
6. Crie uma conta de professor e execute database/promover_administrador.sql após trocar o e-mail de exemplo. Acesse novamente para ver “Gestão de conteúdo”.

Recursos da conta comum: cadastro e login, foto e perfil editável, exclusão de conta,
discussões com respostas, resenhas e notas, quiz com pontos registrados no servidor.
O ranking exibe os pontos registrados nos últimos sete dias. Membros podem editar e
remover as próprias publicações. Professores administradores podem publicar, editar,
excluir artigos, notícias e perguntas do quiz, além de remover publicações inadequadas.

O arquivo database/requisitos.sql é uma migração e deve ser executado uma vez, depois
das tabelas-base. Em uma instalação já existente, faça uma cópia do banco antes de
aplicá-la. A migração acrescenta o nome de usuário e as tabelas usadas pelo fórum,
resenhas, avaliações e gamificação.

Para usar o servidor PHP embutido, a partir desta pasta:
php -S localhost:8000 -t public public/router.php
e acesse http://localhost:8000/.
