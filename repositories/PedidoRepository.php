<?php
namespace Repositories;

require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;

class PedidoRepository
{
    private PDO $db;
    private const COLUMNS = 'id, negocio_id, cliente_nombre, cliente_telefono, total, estado, creado_en';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(int $negocioId): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM pedidos
                WHERE negocio_id = :negocio_id ORDER BY id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':negocio_id', $negocioId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, int $negocioId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM pedidos
             WHERE id = :id AND negocio_id = :negocio_id'
        );
        $stmt->execute(['id' => $id, 'negocio_id' => $negocioId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return $order === false ? null : $order;
    }

    public function negocioExists(int $negocioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM negocios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $negocioId]);

        return $stmt->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pedidos (negocio_id, cliente_nombre, cliente_telefono, total, estado)
             VALUES (:negocio_id, :cliente_nombre, :cliente_telefono, :total, :estado)'
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
            'UPDATE pedidos SET ' . implode(', ', $fields)
            . ' WHERE id = :id AND negocio_id = :scope_negocio_id'
        );
        $stmt->execute($data);
    }

    public function deletePending(int $id, int $negocioId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM pedidos
             WHERE id = :id AND negocio_id = :negocio_id
               AND LOWER(TRIM(estado)) = 'pendiente'"
        );
        $stmt->execute(['id' => $id, 'negocio_id' => $negocioId]);

        return $stmt->rowCount() > 0;
    }
}
