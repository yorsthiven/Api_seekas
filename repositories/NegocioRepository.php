<?php
namespace Repositories;

require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;
use PDOException;

class NegocioRepository
{
    private PDO $db;
    private const COLUMNS = 'id, nombre, tipo, descripcion, logo_url, banner_url, telefono_whatsapp, creado_en';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(int $negocioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM negocios WHERE id = :id'
        );
        $stmt->execute(['id' => $negocioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM negocios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        return $business === false ? null : $business;
    }

    public function existsByName(string $nombre, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM negocios WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))';
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        if ($exceptId !== null) {
            $stmt->bindValue(':except_id', $exceptId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO negocios
                    (nombre, tipo, descripcion, logo_url, banner_url, telefono_whatsapp)
                VALUES
                    (:nombre, :tipo, :descripcion, :logo_url, :banner_url, :telefono_whatsapp)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = $field . ' = :' . $field;
        }

        $data['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE negocios SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $relatedStmt = $this->db->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM productos WHERE negocio_id = :producto_id) AS tiene_productos,
                EXISTS(SELECT 1 FROM pedidos WHERE negocio_id = :pedido_id) AS tiene_pedidos,
                EXISTS(SELECT 1 FROM usuarios WHERE negocio_id = :usuario_id) AS tiene_usuarios'
        );
        $relatedStmt->execute([
            'producto_id' => $id,
            'pedido_id' => $id,
            'usuario_id' => $id,
        ]);
        $related = $relatedStmt->fetch(PDO::FETCH_ASSOC);
        if ($related['tiene_productos'] || $related['tiene_pedidos'] || $related['tiene_usuarios']) {
            throw new \DomainException(
                'No se puede eliminar el negocio porque tiene productos, pedidos o usuarios asociados.'
            );
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM negocios WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \DomainException(
                    'No se puede eliminar el negocio porque tiene registros relacionados.',
                    0,
                    $e
                );
            }
            throw $e;
        }
    }
}
