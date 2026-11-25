NEXOS — instalação local com XAMPP

1. Copie a pasta nexos para C:\xampp\htdocs\nexos.
2. Inicie Apache e MySQL no XAMPP.
3. No phpMyAdmin, importe nesta ordem:
   - database/database.sql
   - database/conteudo.sql
   - database/externos.sql (opcional, para importar artigos e notícias externas)
   - database/requisitos.sql
   - database/inativar_contas.sql (uma vez, depois de requisitos.sql)
   - database/quiz_diario.sql (uma vez, depois de inativar_contas.sql)
   - database/quiz_banco_avancado.sql (uma vez, depois de quiz_diario.sql)
4. Confira config/database.php. O padrão usa MySQL local, usuário root e senha vazia.
5. Abra http://localhost/nexos/.
6. Crie uma conta de professor e execute database/promover_administrador.sql após trocar o e-mail de exemplo. Acesse novamente para ver “Gestão de conteúdo”.

Recursos da conta comum: cadastro e login, foto e perfil editável, desativação e
reativação de conta, discussões com respostas, resenhas e notas, quiz diário com pontos
registrados no servidor.
O ranking exibe os pontos registrados nos últimos sete dias. Membros podem editar e
remover as próprias publicações. Professores administradores podem publicar, editar,
excluir artigos, notícias e perguntas do quiz, além de remover publicações inadequadas.

Os arquivos database/requisitos.sql, database/inativar_contas.sql,
database/quiz_diario.sql e database/quiz_banco_avancado.sql são migrações e devem ser executados uma vez, nessa ordem,
depois das tabelas-base. Em uma instalação já existente, faça uma cópia do banco
antes de aplicá-las. A primeira acrescenta o nome de usuário e as tabelas usadas pelo
fórum, resenhas, avaliações e gamificação. A segunda habilita desativação e reativação
de contas. A terceira prepara o quiz diário, guarda as fontes e registra as respostas.
O quiz escolhe dez perguntas por dia, não repete uma questão até percorrer as
publicadas e reinicia o ciclo depois disso. A quarta arquiva as perguntas introdutórias
originais do pacote para novos sorteios e adiciona 36 questões autorais com alternativas
mais próximas entre si e fontes consultáveis. Perguntas que já foram copiadas para dias
anteriores continuam no histórico. Respostas certas valem 10 pontos e
tentativas erradas valem 2; cada conta pontua uma vez por pergunta/dia. Administradores
podem ampliar o banco de perguntas e precisam informar a fonte para publicar uma nova
questão.

Para usar o servidor PHP embutido, a partir desta pasta:
php -S localhost:8000 -t public public/router.php
e acesse http://localhost:8000/.

