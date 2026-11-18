USE nexos;

CREATE TABLE IF NOT EXISTS Categoria (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(30)  NOT NULL UNIQUE,   
    nome VARCHAR(60)  NOT NULL           
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS Artigo (
    id_artigo INT PRIMARY KEY AUTO_INCREMENT,
    id_categoria INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    resumo VARCHAR(300) NOT NULL,        
    conteudo TEXT NOT NULL,              
    imagem VARCHAR(255) NULL,            
    imagem_alt VARCHAR(255) NULL,
    icone VARCHAR(50) NULL,              
    publicado TINYINT(1) NOT NULL DEFAULT 1,
    data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_admin INT NULL,
    CONSTRAINT fk_artigo_categoria FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria),
    CONSTRAINT fk_artigo_admin FOREIGN KEY (id_admin) REFERENCES Administrador(id_pessoa) ON DELETE SET NULL
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS Noticia (
    id_noticia INT PRIMARY KEY AUTO_INCREMENT,
    rotulo VARCHAR(40) NOT NULL,         
    classe_badge VARCHAR(30) NOT NULL DEFAULT 'badge-historia',  
    titulo VARCHAR(200) NOT NULL,
    resumo VARCHAR(300) NOT NULL,
    imagem VARCHAR(255) NULL,           
    link VARCHAR(255) NULL,              
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    publicado TINYINT(1) NOT NULL DEFAULT 1,
    data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_admin INT NULL,
    CONSTRAINT fk_noticia_admin FOREIGN KEY (id_admin) REFERENCES Administrador(id_pessoa) ON DELETE SET NULL
) CHARACTER SET utf8mb4;


INSERT INTO Categoria (slug, nome)
SELECT * FROM (
    SELECT 'historia' AS slug, 'História' AS nome
    UNION ALL SELECT 'indigena', 'Povos indígenas'
    UNION ALL SELECT 'educacao', 'Educação antirracista'
    UNION ALL SELECT 'imigracao', 'Imigração'
) AS novas
WHERE NOT EXISTS (SELECT 1 FROM Categoria);

INSERT INTO Artigo (id_categoria, titulo, resumo, conteudo, imagem, imagem_alt, icone, data_publicacao)
SELECT (SELECT id_categoria FROM Categoria WHERE slug = t.slug), t.titulo, t.resumo, t.conteudo, t.imagem, t.imagem_alt, t.icone, t.data_publicacao
FROM (
    SELECT 'historia' AS slug,
        'O contexto histórico da formação étnico-racial brasileira' AS titulo,
        'Da colonização à abolição sem reparação: como o passado explica as desigualdades de hoje.' AS resumo,
        '<p>O processo de colonização iniciado pelos portugueses no século XVI foi determinante para a construção das desigualdades e da hierarquização racial no país. A exploração inicial pautou-se na invasão territorial, no espoliamento cultural e na violência contra os povos originários.</p><p>Com a resistência e o genocídio das populações indígenas, a empresa colonial introduziu o tráfico transatlântico de africanos escravizados. Durante mais de três séculos, a população africana e seus descendentes foram desprovidos de direitos básicos, humanidade e identidade (MUNANGA, 2008).</p><p>Com o fim oficial da escravidão em 1888, a abolição ocorreu sem políticas de reparação, integração social ou acesso à terra e à educação. Dados do IBGE (2022) mostram que as disparidades raciais na educação, na renda e no mercado de trabalho são desdobramentos diretos desse processo histórico.</p><p class="fw-semibold mb-0">Para debater: que marcas desse passado você percebe na sua cidade ou escola?</p>' AS conteudo,
        'assets/artigo-historia.jpg' AS imagem,
        'Mãos de diferentes tons de pele tecendo juntas um tecido colorido' AS imagem_alt,
        NULL AS icone,
        '2026-11-01 09:00:00' AS data_publicacao
    UNION ALL SELECT 'indigena',
        'Saberes indígenas e africanos que o Brasil precisa conhecer',
        'Muito além da arte: conhecimentos científicos, ecológicos e filosóficos historicamente invisibilizados.',
        '<p>Segundo a UNESCO, a diversidade étnico-racial abrange diferentes sistemas de saberes, cosmovisões e práticas sociais que moldam as relações dos grupos com o território, a saúde, o ensino e o trabalho.</p><p>As matrizes africanas e indígenas contribuíram não apenas artisticamente, mas com conhecimentos científicos, ecológicos e filosóficos, que permanecem nas comunidades tradicionais e foram historicamente invisibilizados.</p><p>Para o IBGE, no contexto da população indígena, “etnia” é uma comunidade definida por afinidades linguísticas, culturais e sociais, correspondendo a um povo com identidade cultural própria.</p><p class="fw-semibold mb-0">Para debater: quais saberes indígenas ou africanos estão presentes no seu dia a dia sem que você perceba?</p>',
        'assets/artigo-indigena.jpg',
        'Menina indígena com pintura corporal lendo um livro sob uma árvore, à beira de um rio',
        NULL,
        '2026-11-02 09:00:00'
    UNION ALL SELECT 'educacao',
        'Lei 10.639/2003: o que diz e por que importa na sua escola',
        'A lei tornou obrigatório o ensino de história e cultura afro-brasileira. Mas ela já é realidade?',
        '<p>A Lei nº 10.639/2003 torna obrigatório o ensino de história e cultura afro-brasileira nas escolas. Embora prevista na legislação, sua implementação prática ainda enfrenta limitações, como a escassez de materiais acessíveis e de espaços digitais pensados para esse fim.</p><p>Estudos no campo da educação antirracista demonstram que o conhecimento da história e da cultura afro-brasileira e indígena contribui para a redução de práticas discriminatórias (MUNANGA, 2008).</p><p>Iniciativas como a Escola Maria Felipa, o Projeto ANANSI (CEERT) e os cursos da plataforma Escolas Conectadas são avanços importantes, mas se voltam principalmente a professores e adultos.</p><p class="fw-semibold mb-0">Para debater: como a sua escola trabalha esse tema ao longo do ano?</p>',
        'assets/artigo-escola.jpg',
        'Professora negra apontando para o mapa da África e da América do Sul enquanto alunos levantam a mão',
        NULL,
        '2026-11-03 09:00:00'
    UNION ALL SELECT 'historia',
        'O mito da “democracia racial”',
        'Por que a ideia de convivência harmoniosa entre as raças esconde o preconceito no Brasil.',
        '<p>Após a abolição, o Estado brasileiro incentivou teorias de hierarquização racial, como a ideologia do branqueamento, promovendo a imigração europeia para diluir a presença negra na população.</p><p>Depois, difundiu-se a tese da “democracia racial”: a falsa ideia de uma convivência harmoniosa entre as raças, que mascara o preconceito e deslegitima as lutas do movimento negro. É a crença de que a população negra teria as mesmas oportunidades de ascensão que os demais.</p><p>Reconhecer casos de racismo e de injúria racial é o primeiro passo para a conscientização e evita o apagamento da violência sofrida pelos povos indígenas e africanos.</p><p class="fw-semibold mb-0">Para debater: você já ouviu frases como “no Brasil não existe racismo”? Como responder a elas?</p>',
        NULL, NULL, 'bi-people',
        '2026-11-04 09:00:00'
    UNION ALL SELECT 'imigracao',
        'Xenofobia: o debate também é sobre imigrantes',
        'Venezuelanos, haitianos, bolivianos, árabes, chineses e povos ciganos também enfrentam preconceito.',
        '<p>O debate étnico-racial não se restringe aos povos indígenas e aos descendentes de africanos. Ele também envolve etnias tratadas com indiferença no Brasil, como venezuelanos, haitianos, bolivianos, árabes e chineses — muitas vezes atacados nas redes sociais.</p><p>Ainda persistem preconceitos contra os povos ciganos, injustamente associados à vadiagem. Fora do país, a islamofobia na França e atitudes desrespeitosas contra brasileiros, como em jogos de futebol, mostram que o problema é global.</p><p>Mais do que apontar atitudes erradas, é preciso compreender o que leva as pessoas a agirem assim, para que o resultado do debate seja a reflexão e a mudança, e não o ódio.</p><p class="fw-semibold mb-0">Para debater: como acolher colegas imigrantes na sua escola?</p>',
        NULL, NULL, 'bi-globe-americas',
        '2026-11-05 09:00:00'
    UNION ALL SELECT 'educacao',
        'Pensamento crítico contra estereótipos',
        'Como identificar discriminações no dia a dia e transformar o debate em mudança de atitude.',
        '<p>Estereótipo é uma ideia generalizada e simplificada atribuída a determinado grupo de pessoas. Ele aparece em piadas, apelidos, filmes, propagandas e até em materiais didáticos.</p><p>O pensamento crítico — a capacidade de analisar informações, questionar ideias e formar opiniões de maneira reflexiva — é a principal ferramenta para identificar discriminações no cotidiano, em casa, na rua e na escola.</p><p>A desinformação e os conteúdos preconceituosos nas redes ajudam a manter estereótipos. Checar fontes e ouvir quem vive a experiência faz toda a diferença.</p><p class="fw-semibold mb-0">Para debater: qual estereótipo você já percebeu na TV ou nas redes sociais esta semana?</p>',
        NULL, NULL, 'bi-lightbulb',
        '2026-11-06 09:00:00'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM Artigo);

INSERT INTO Noticia (rotulo, classe_badge, titulo, resumo, imagem, destaque, data_publicacao)
SELECT * FROM (
    SELECT 'Destaque' AS rotulo, 'badge-destaque' AS classe_badge,
        'Plataforma Raízes é apresentada a estudantes do IF Baiano – Campus Guanambi' AS titulo,
        'Projeto Integrador propõe fórum, artigos e gamificação para levar o debate étnico-racial ao dia a dia da escola.' AS resumo,
        'assets/artigo-historia.jpg' AS imagem, 1 AS destaque, '2026-11-12 10:00:00' AS data_publicacao
    UNION ALL SELECT 'Brasil', 'badge-historia',
        'Novembro Negro: escolas promovem semana de debates sobre consciência negra',
        'Atividades valorizam a história e a cultura afro-brasileira em diversas redes de ensino.',
        NULL, 0, '2026-11-10 10:00:00'
    UNION ALL SELECT 'Educação', 'badge-educacao',
        'Escola Maria Felipa é referência em educação afro-brasileira',
        'Idealizada pela professora Bárbara Carine, é a primeira escola afro-brasileira registrada pelo MEC.',
        NULL, 0, '2026-11-04 10:00:00'
    UNION ALL SELECT 'Dados', 'badge-indigena',
        'IBGE: desigualdades raciais persistem em educação, renda e trabalho',
        'Indicadores reforçam a necessidade de ações de conscientização e inclusão social.',
        NULL, 0, '2026-10-28 10:00:00'
    UNION ALL SELECT 'Mundo', 'badge-imigracao',
        'Imigrantes venezuelanos relatam ataques xenófobos nas redes',
        'Especialistas defendem educação digital para combater o discurso de ódio.',
        NULL, 0, '2026-10-15 10:00:00'
) AS novas
WHERE NOT EXISTS (SELECT 1 FROM Noticia);
