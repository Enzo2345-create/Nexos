USE nexos;

-- Execute uma única vez para habilitar a inativação de contas.
ALTER TABLE Pessoa ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1;

-- A aplicação passa a marcar a conta como inativa e ocultar suas publicações.
