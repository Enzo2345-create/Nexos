<?php
namespace App\Models;

class Auth
{
    public static function emailOrCpfExists(string $email, string $cpf): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT 1 FROM pessoa WHERE (email = ? OR cpf = ?) AND ativo = 1 LIMIT 1'
        );
        $stmt->execute([$email, $cpf]);
        return (bool) $stmt->fetchColumn();
    }

    public static function createUser(array $data): int
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'INSERT INTO pessoa (nome_pessoa, nome_usuario, email, senha, cpf, dt_nasc)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['name'],
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['cpf'],
                $data['birth_date'],
            ]);
            $id = (int) $db->lastInsertId();

            $stmt = $db->prepare('INSERT INTO usuario (id_pessoa) VALUES (?)');
            $stmt->execute([$id]);
            $db->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT p.id_pessoa, p.nome_pessoa, p.nome_usuario, p.email, p.senha, u.foto_de_perfil,
                    CASE WHEN a.id_pessoa IS NOT NULL THEN "administrador" ELSE "usuario" END AS role
             FROM pessoa p
             LEFT JOIN usuario u ON u.id_pessoa = p.id_pessoa
             LEFT JOIN administrador a ON a.id_pessoa = p.id_pessoa
             WHERE p.email = ? AND p.ativo = 1 AND (u.id_pessoa IS NOT NULL OR a.id_pessoa IS NOT NULL)
             LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findInactiveByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id_pessoa, nome_pessoa, nome_usuario, email, senha
             FROM pessoa WHERE email = ? AND ativo = 0 LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function reactivateAccount(int $id): void
    {
        $db = Database::connect();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('UPDATE pessoa SET ativo = 1 WHERE id_pessoa = ? AND ativo = 0');
            $stmt->execute([$id]);
            // Recupera a visibilidade das publicações ocultadas pela versão anterior.
            foreach (['Topico', 'Resposta', 'Resenha'] as $table) {
                $restore = $db->prepare("UPDATE {$table} SET publicado = 1 WHERE id_usuario = ?");
                $restore->execute([$id]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function updateProfilePhoto(int $userId, string $photoPath): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE usuario SET foto_de_perfil = ? WHERE id_pessoa = ?'
        );
        $stmt->execute([$photoPath, $userId]);
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = Database::connect()->prepare('UPDATE Pessoa SET nome_pessoa=?, nome_usuario=?, email=?, cpf=?, dt_nasc=? WHERE id_pessoa=?');
        $stmt->execute([$data['name'], $data['username'], $data['email'], $data['cpf'], $data['birth_date'], $id]);
        if (!empty($data['password'])) {
            $stmt = Database::connect()->prepare('UPDATE Pessoa SET senha=? WHERE id_pessoa=?');
            $stmt->execute([password_hash($data['password'], PASSWORD_DEFAULT), $id]);
        }
    }

    public static function profileExists(int $id, string $email, string $username): bool
    {
        $stmt = Database::connect()->prepare('SELECT 1 FROM Pessoa WHERE (email=? OR nome_usuario=?) AND id_pessoa<>?');
        $stmt->execute([$email, $username, $id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function cpfExists(int $id,string $cpf): bool
    {
        $stmt=Database::connect()->prepare('SELECT 1 FROM Pessoa WHERE cpf=? AND id_pessoa<>?');$stmt->execute([$cpf,$id]);return (bool)$stmt->fetchColumn();
    }

    public static function publicProfile(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT p.id_pessoa,p.nome_pessoa,p.nome_usuario,p.email,p.cpf,p.dt_nasc,u.foto_de_perfil FROM Pessoa p JOIN Usuario u USING(id_pessoa) WHERE p.id_pessoa=? AND p.ativo=1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function deleteAccount(int $id): void
    {
        $db = Database::connect();
        $db->beginTransaction();
        try {
            // Desativa somente a conta. As publicações e seus estados permanecem intactos.
            $stmt = $db->prepare('UPDATE Pessoa SET ativo=0 WHERE id_pessoa=?');
            $stmt->execute([$id]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function activityHistory(int $id): array
    {
        $stmt=Database::connect()->prepare('SELECT atividade,pontos,criado_em FROM Pontuacao WHERE id_usuario=? ORDER BY criado_em DESC LIMIT 30');$stmt->execute([$id]);return $stmt->fetchAll();
    }

    public static function totalPoints(int $id): int
    {
        $stmt=Database::connect()->prepare('SELECT COALESCE(SUM(pontos),0) FROM Pontuacao WHERE id_usuario=?');$stmt->execute([$id]);return (int)$stmt->fetchColumn();
    }

    public static function activityDates(int $id): array
    {
        $stmt=Database::connect()->prepare('SELECT DISTINCT DATE(criado_em) AS dia FROM Pontuacao WHERE id_usuario=?');$stmt->execute([$id]);return array_column($stmt->fetchAll(),'dia');
    }
}
