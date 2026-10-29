<?php
namespace Repositories;

require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;

class UsuarioRepository
{
    private PDO $db;
    private const COLUMNS = 'id, negocio_id, email, creado_en';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(int $negocioId): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM usuarios
                WHERE negocio_id = :negocio_id ORDER BY id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':negocio_id', $negocioId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, ?int $negocioId = null): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM usuarios WHERE id = :id';
        $params = ['id' => $id];
        if ($negocioId !== null) {
            $sql .= ' AND negocio_id = :negocio_id';
            $params['negocio_id'] = $negocioId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    public function getForLogin(string $email, int $negocioId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, negocio_id, email, password
             FROM usuarios
             WHERE negocio_id = :negocio_id AND LOWER(email) = LOWER(:email)
             LIMIT 1'
        );
        $stmt->execute([
            'negocio_id' => $negocioId,
            'email' => $email,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET password = :password WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'password' => $passwordHash,
        ]);
    }

    public function negocioExists(int $negocioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM negocios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $negocioId]);

        return $stmt->fetchColumn() !== false;
    }

    public function existsByEmail(string $email, int $negocioId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM usuarios
                WHERE negocio_id = :negocio_id AND LOWER(email) = LOWER(:email)';
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':negocio_id', $negocioId, PDO::PARAM_INT);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        if ($exceptId !== null) {
            $stmt->bindValue(':except_id', $exceptId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (negocio_id, email, password)
             VALUES (:negocio_id, :email, :password)'
        );
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $negocioId, array $data): void
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = $field . ' = :' . $field;
        }

        $data['id'] = $id;
        $data['scope_negocio_id'] = $negocioId;
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET ' . implode(', ', $fields)
            . ' WHERE id = :id AND negocio_id = :scope_negocio_id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id, int $negocioId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM usuarios WHERE id = :id AND negocio_id = :negocio_id'
        );
        $stmt->execute(['id' => $id, 'negocio_id' => $negocioId]);

        return $stmt->rowCount() > 0;
    }
}
