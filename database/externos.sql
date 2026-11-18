-- =========================================================
-- Artigos vindos da internet: acrescenta "link" e "fonte" na tabela Artigo.
-- Execute UMA vez no phpMyAdmin (aba SQL, banco "nexos").
-- Se aparecer "Duplicate column name", é porque já foi executado.
-- =========================================================
USE nexos;

ALTER TABLE Artigo
    ADD COLUMN link  VARCHAR(255) NULL,   -- preenchido = artigo externo (abre o site original)
    ADD COLUMN fonte VARCHAR(60)  NULL;   -- nome do site de origem
