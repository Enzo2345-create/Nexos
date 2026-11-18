USE nexos;

ALTER TABLE Artigo
    ADD COLUMN link  VARCHAR(255) NULL,   -- preenchido = artigo externo (abre o site original)
    ADD COLUMN fonte VARCHAR(60)  NULL;   -- nome do site de origem
