USE nexos;

ALTER TABLE Quiz ADD COLUMN fonte VARCHAR(600) NULL AFTER correta;

CREATE TABLE IF NOT EXISTS QuizRotacao (
  id TINYINT PRIMARY KEY,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO QuizRotacao(id) VALUES (1);

CREATE TABLE IF NOT EXISTS QuizUso (
  id_quiz INT PRIMARY KEY,
  data_uso DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS QuizDiario (
  data_quiz DATE NOT NULL,
  id_quiz INT NOT NULL,
  ordem TINYINT NOT NULL,
  pergunta VARCHAR(500) NOT NULL,
  opcao_a VARCHAR(300) NOT NULL,
  opcao_b VARCHAR(300) NOT NULL,
  opcao_c VARCHAR(300) NOT NULL,
  opcao_d VARCHAR(300) NOT NULL,
  correta TINYINT NOT NULL,
  fonte VARCHAR(600) NULL,
  PRIMARY KEY (data_quiz, id_quiz),
  UNIQUE KEY uq_quiz_diario_ordem (data_quiz, ordem),
  CHECK (correta BETWEEN 0 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS QuizResposta (
  id_usuario INT NOT NULL,
  data_quiz DATE NOT NULL,
  id_quiz INT NOT NULL,
  escolha TINYINT NOT NULL,
  correta TINYINT(1) NOT NULL,
  pontos INT NOT NULL,
  respondida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, data_quiz, id_quiz),
  CONSTRAINT fk_quiz_resposta_usuario FOREIGN KEY (id_usuario)
    REFERENCES Usuario(id_pessoa) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_resposta_diario FOREIGN KEY (data_quiz, id_quiz)
    REFERENCES QuizDiario(data_quiz, id_quiz) ON DELETE CASCADE,
  CHECK (escolha BETWEEN 0 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Perguntas próprias, com referências oficiais para conferência.
-- Podem ser acrescentadas/atualizadas pela área administrativa do Nexos.
INSERT INTO Quiz(pergunta,opcao_a,opcao_b,opcao_c,opcao_d,correta,fonte)
SELECT novas.pergunta,novas.opcao_a,novas.opcao_b,novas.opcao_c,novas.opcao_d,novas.correta,novas.fonte
FROM (
  SELECT 'Qual lei tornou obrigatório o ensino de História e Cultura Afro-Brasileira nas escolas?' pergunta,'Lei nº 8.069/1990' opcao_a,'Lei nº 10.639/2003' opcao_b,'Lei nº 12.711/2012' opcao_c,'Lei nº 9.784/1999' opcao_d,1 correta,'https://www.planalto.gov.br/ccivil_03/leis/2003/l10.639.htm' fonte
  UNION ALL SELECT 'Além da história da África, que outros temas a Lei nº 10.639/2003 inclui no currículo?','Apenas a história da Europa','Somente biografias de governantes','A luta dos negros no Brasil, a cultura negra brasileira e sua contribuição para a sociedade','Apenas conteúdos de geografia',2,'https://www.planalto.gov.br/ccivil_03/leis/2003/l10.639.htm'
  UNION ALL SELECT 'Qual lei ampliou a obrigação curricular para incluir também a história e a cultura dos povos indígenas?','Lei nº 11.645/2008','Lei nº 8.078/1990','Lei nº 13.005/2014','Lei nº 9.795/1999',0,'https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2008/lei/l11645.htm'
  UNION ALL SELECT 'Segundo o IBGE, quais categorias aparecem na pergunta de cor ou raça do Censo?','Negra, mestiça e estrangeira','Branca, preta, parda, amarela e indígena','Europeia, africana e asiática','Clara, média e escura',1,'https://educa.ibge.gov.br/jovens/materias-especiais/18319-cor-ou-raca.html'
  UNION ALL SELECT 'Como o IBGE coleta a informação de cor ou raça no Censo?','Por análise de fotografia','Por indicação da escola','Por autodeclaração da pessoa entrevistada','Por exame de laboratório',2,'https://educa.ibge.gov.br/jovens/materias-especiais/18319-cor-ou-raca.html'
  UNION ALL SELECT 'Em que ano foi sancionada a Lei nº 10.639, sobre História e Cultura Afro-Brasileira?','1988','2003','2008','2012',1,'https://www.planalto.gov.br/ccivil_03/leis/2003/l10.639.htm'
  UNION ALL SELECT 'Em que ano a Lei nº 11.645 incluiu a temática indígena na legislação curricular?','1996','2003','2008','2011',2,'https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2008/lei/l11645.htm'
  UNION ALL SELECT 'Qual artigo da Constituição de 1988 reconhece a organização social, os costumes, as línguas, as crenças e as tradições indígenas?','Artigo 5º','Artigo 37','Artigo 150','Artigo 231',3,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
  UNION ALL SELECT 'O que a Constituição reconhece aos povos indígenas no artigo 231?','Direitos originários sobre as terras que tradicionalmente ocupam','Propriedade automática de qualquer área urbana','A obrigação de abandonar suas línguas','Apenas o direito de participar de eleições',0,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
  UNION ALL SELECT 'O que estabelece o artigo 68 do Ato das Disposições Constitucionais Transitórias?','A criação de uma data para o Censo','O direito à propriedade definitiva das terras ocupadas por comunidades remanescentes de quilombos','A transferência de terras indígenas para municípios','A proibição de associações comunitárias',1,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
  UNION ALL SELECT 'Qual data a Lei nº 12.519/2011 instituiu como Dia Nacional de Zumbi e da Consciência Negra?','13 de maio','7 de setembro','20 de novembro','19 de abril',2,'https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12519.htm'
  UNION ALL SELECT 'Qual é o objetivo principal da Lei nº 12.288/2010, conhecida como Estatuto da Igualdade Racial?','Garantir à população negra igualdade de oportunidades e combater a discriminação e a intolerância étnica','Criar uma única identidade cultural nacional','Substituir o Censo Demográfico','Regular apenas a educação universitária',0,'https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2010/lei/l12288.htm'
  UNION ALL SELECT 'A Lei de Cotas (Lei nº 12.711/2012) trata principalmente de reserva de vagas em quais instituições?','Escolas privadas de educação infantil','Instituições federais de ensino','Museus municipais','Empresas de comunicação',1,'https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2012/lei/l12711.htm'
  UNION ALL SELECT 'A Constituição brasileira classifica o racismo como:','Uma infração administrativa leve','Um tema sem previsão constitucional','Crime inafiançável e imprescritível, sujeito à pena de reclusão','Uma questão exclusiva das escolas',2,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
  UNION ALL SELECT 'Qual lei define crimes resultantes de preconceito de raça ou de cor?','Lei nº 7.716/1989','Lei nº 10.639/2003','Lei nº 12.519/2011','Lei nº 11.645/2008',0,'https://www.planalto.gov.br/ccivil_03/leis/l7716.htm'
  UNION ALL SELECT 'Segundo o IBGE, a categoria “indígena” no Censo se limita a pessoas que vivem em terras indígenas?','Sim, somente quem vive em aldeia pode declarar essa categoria','Não; a identificação não depende exclusivamente de morar em terra indígena','Sim, desde que a pessoa fale uma língua indígena','Não, mas só pode ser declarada por crianças',1,'https://agenciadenoticias.ibge.gov.br/agencia-noticias/2012-agencia-de-noticias/noticias/34725-na-floresta-no-campo-na-cidade-hora-de-retratar-a-populacao-indigena'
  UNION ALL SELECT 'Além da cor ou raça, que informações o Censo pode investigar para pessoas que se declaram indígenas?','Somente a renda familiar','A etnia, o povo ou grupo e a língua falada no domicílio','A filiação partidária','A religião obrigatória',1,'https://agenciadenoticias.ibge.gov.br/agencia-noticias/2012-agencia-de-noticias/noticias/34590-ibge-organiza-mobilizacao-nacional-para-recensear-comunidades-e-povos-indigenas'
  UNION ALL SELECT 'Qual alternativa descreve melhor o papel da Lei nº 10.639/2003 na educação?','Tornar facultativo o ensino de história africana','Incluir a temática afro-brasileira no currículo oficial da rede de ensino','Criar uma disciplina universitária única','Retirar conteúdos de história do Brasil',1,'https://www.planalto.gov.br/ccivil_03/leis/2003/l10.639.htm'
  UNION ALL SELECT 'A Lei nº 11.645/2008 aborda quais histórias e culturas no currículo escolar?','Apenas a história europeia','Apenas a história dos povos indígenas','História e cultura afro-brasileira e indígena','Somente a história contemporânea',2,'https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2008/lei/l11645.htm'
  UNION ALL SELECT 'Qual alternativa explica por que a autodeclaração é importante nos dados do IBGE?','A pessoa informa como se identifica segundo as categorias pesquisadas','O recenseador escolhe a categoria pela aparência','A escola determina a resposta','A resposta é definida pelo local de nascimento',0,'https://educa.ibge.gov.br/jovens/materias-especiais/18319-cor-ou-raca.html'
  UNION ALL SELECT 'O que a Constituição determina sobre o ensino da História do Brasil no artigo 242, §1º?','Deve considerar as contribuições das diferentes culturas e etnias para a formação do povo brasileiro','Deve tratar somente da história política','Deve excluir a história dos povos originários','Deve ser igual em todos os países',0,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
  UNION ALL SELECT 'Qual órgão federal tem atribuições relacionadas à identificação, ao reconhecimento, à delimitação, à demarcação e à titulação das terras quilombolas?','IBGE','INCRA','INEP','IPHAN',1,'https://www.gov.br/incra/pt-br/assuntos/governanca-fundiaria/quilombolas/'
  UNION ALL SELECT 'O que significa dizer que uma comunidade quilombola tem direito à titulação coletiva de seu território?','Que a terra pode ser reconhecida em nome da comunidade, conforme os procedimentos legais','Que cada morador recebe automaticamente um lote urbano','Que o território deixa de ter proteção legal','Que a comunidade precisa se mudar para uma cidade',0,'https://www.gov.br/incra/pt-br/assuntos/governanca-fundiaria/quilombolas/'
  UNION ALL SELECT 'Qual afirmação sobre diversidade étnico-racial está de acordo com a proposta das Leis nº 10.639/2003 e nº 11.645/2008?','A história brasileira deve considerar diferentes povos e suas contribuições','Somente uma cultura deve ser ensinada','A temática deve aparecer apenas em datas comemorativas','Culturas indígenas e afro-brasileiras não fazem parte da história do Brasil',0,'https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2008/lei/l11645.htm'
  UNION ALL SELECT 'No Censo Demográfico 2022, qual categoria de cor ou raça passou a representar a maior parcela da população brasileira, segundo o IBGE?','Branca','Preta','Parda','Amarela',2,'https://www.ibge.gov.br/estatisticas/sociais/populacao/22827-censo-demografico-2022.html?edicao=38698&t=destaques'
  UNION ALL SELECT 'Qual alternativa corresponde a uma das categorias usadas pelo IBGE no quesito cor ou raça?','Latina','Parda','Brasileira','Mestiça urbana',1,'https://educa.ibge.gov.br/jovens/materias-especiais/18319-cor-ou-raca.html'
  UNION ALL SELECT 'A proteção constitucional aos povos indígenas inclui o respeito a quais aspectos de sua vida coletiva?','Somente ao idioma oficial do país','À organização social, aos costumes, às línguas, às crenças e às tradições','Apenas à propriedade individual','Somente às atividades econômicas',1,'https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm'
) AS novas
LEFT JOIN Quiz existente ON existente.pergunta = novas.pergunta
WHERE existente.id_quiz IS NULL;

UPDATE Quiz SET fonte='https://www.planalto.gov.br/ccivil_03/leis/2003/l10.639.htm'
WHERE fonte IS NULL AND pergunta='Qual lei tornou obrigatório o ensino de história e cultura afro-brasileira nas escolas?';
UPDATE Quiz SET fonte='https://educa.ibge.gov.br/jovens/materias-especiais/18319-cor-ou-raca.html'
WHERE fonte IS NULL AND pergunta='Quais são as categorias de cor ou raça usadas pelo IBGE?';
UPDATE Quiz SET fonte='https://agenciadenoticias.ibge.gov.br/agencia-noticias/2012-agencia-de-noticias/noticias/21206-ibge-mostra-as-cores-da-desigualdade'
WHERE fonte IS NULL AND pergunta='O que é o mito da democracia racial?';
UPDATE Quiz SET fonte='https://www.planalto.gov.br/ccivil_03/leis/lim/lim3353.htm'
WHERE fonte IS NULL AND pergunta='Em que ano ocorreu o fim oficial da escravidão no Brasil?';
