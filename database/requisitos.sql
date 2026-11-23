USE nexos;

ALTER TABLE Pessoa ADD COLUMN nome_usuario VARCHAR(40) NULL AFTER nome_pessoa;
UPDATE Pessoa SET nome_usuario = CONCAT('nexos', id_pessoa) WHERE nome_usuario IS NULL;
ALTER TABLE Pessoa MODIFY nome_usuario VARCHAR(40) NOT NULL, ADD UNIQUE KEY uq_pessoa_nome_usuario (nome_usuario);

CREATE TABLE IF NOT EXISTS Topico (
  id_topico INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  categoria VARCHAR(30) NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  mensagem TEXT NOT NULL,
  publicado TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL,
  FOREIGN KEY (id_usuario) REFERENCES Usuario(id_pessoa) ON DELETE CASCADE,
  INDEX ix_topico_publicado_data (publicado, criado_em)
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS Resposta (
  id_resposta INT AUTO_INCREMENT PRIMARY KEY,
  id_topico INT NOT NULL,
  id_usuario INT NOT NULL,
  mensagem TEXT NOT NULL,
  publicado TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_topico) REFERENCES Topico(id_topico) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES Usuario(id_pessoa) ON DELETE CASCADE,
  INDEX ix_resposta_topico (id_topico, criado_em)
) CHARACTER SET utf8mb4;

ALTER TABLE Resenha
  ADD COLUMN id_artigo INT NULL AFTER id_resenha,
  ADD COLUMN texto TEXT NULL AFTER autor,
  ADD COLUMN publicado TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN atualizado_em DATETIME NULL,
  ADD CONSTRAINT fk_resenha_artigo FOREIGN KEY (id_artigo) REFERENCES Artigo(id_artigo) ON DELETE CASCADE,
  ADD INDEX ix_resenha_artigo (id_artigo, publicado, data_post);
UPDATE Resenha SET texto = CONCAT('Resenha publicada por ', autor, '.') WHERE texto IS NULL;

CREATE TABLE IF NOT EXISTS AvaliacaoResenha (
  id_resenha INT NOT NULL,
  id_usuario INT NOT NULL,
  nota TINYINT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_resenha, id_usuario),
  FOREIGN KEY (id_resenha) REFERENCES Resenha(id_resenha) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES Usuario(id_pessoa) ON DELETE CASCADE,
  CHECK (nota BETWEEN 1 AND 5)
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS Pontuacao (
  id_pontuacao BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  atividade VARCHAR(40) NOT NULL,
  referencia VARCHAR(80) NOT NULL,
  pontos INT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_atividade_usuario (id_usuario, atividade, referencia),
  FOREIGN KEY (id_usuario) REFERENCES Usuario(id_pessoa) ON DELETE CASCADE
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS Quiz (
  id_quiz INT AUTO_INCREMENT PRIMARY KEY,
  pergunta VARCHAR(500) NOT NULL,
  opcao_a VARCHAR(300) NOT NULL,
  opcao_b VARCHAR(300) NOT NULL,
  opcao_c VARCHAR(300) NOT NULL,
  opcao_d VARCHAR(300) NOT NULL,
  correta TINYINT NOT NULL,
  publicado TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (correta BETWEEN 0 AND 3)
) CHARACTER SET utf8mb4;

INSERT INTO Quiz(pergunta,opcao_a,opcao_b,opcao_c,opcao_d,correta)
SELECT * FROM (
  SELECT 'Qual lei tornou obrigatório o ensino de história e cultura afro-brasileira nas escolas?' pergunta,'Lei nº 8.069/1990' opcao_a,'Lei nº 10.639/2003' opcao_b,'Lei nº 12.711/2012' opcao_c,'Lei nº 9.394/1996' opcao_d,1 correta
  UNION ALL SELECT 'Em que ano ocorreu o fim oficial da escravidão no Brasil?','1822','1889','1888','1850',2
  UNION ALL SELECT 'Quais são as categorias de cor ou raça usadas pelo IBGE?','Branca, negra e indígena','Branca, preta, parda, amarela e indígena','Clara, morena e escura','Europeia, africana e asiática',1
  UNION ALL SELECT 'O que é o mito da democracia racial?','A ideia falsa de que no Brasil todas as raças convivem em harmonia e com as mesmas oportunidades','Uma lei que garante igualdade nas eleições','Um movimento de valorização da cultura indígena','Uma teoria que comprova o fim do racismo',0
) AS s WHERE NOT EXISTS (SELECT 1 FROM Quiz);
