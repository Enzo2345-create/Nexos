USE nexos;

-- Crie primeiro uma conta normalmente e substitua pelo e-mail dessa conta.
-- A conta também precisa permanecer na tabela Usuario para usar o perfil comum.
INSERT INTO Administrador (id_pessoa, cargo, salario)
SELECT id_pessoa, 'Administrador da plataforma', 0.00
FROM Pessoa
WHERE email = 'troque-pelo-email-do-professor@escola.edu.br'
  AND NOT EXISTS (
      SELECT 1 FROM Administrador a WHERE a.id_pessoa = Pessoa.id_pessoa
  );
