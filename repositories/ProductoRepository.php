<?php
namespace Repositories;
require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;
class ProductoRepository
{
    private PDO $db;
    private const PRODUCT_COLUMNS = 'id, negocio_id, nombre, descripcion, precio, stock, imagen, creado_en';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    public function getAll(?int $negocioId = null): array
    {
        $sql = 'SELECT ' . self::PRODUCT_COLUMNS . ' FROM productos';
        if ($negocioId !== null) {
            $sql .= ' WHERE negocio_id = :negocio_id';
        }

        $stmt = $this->db->prepare($sql);
        if ($negocioId !== null) {
            $stmt->bindValue(':negocio_id', $negocioId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::PRODUCT_COLUMNS . ' FROM productos WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        return $product === false ? null : $product;
    }

    public function negocioExists(int $negocioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM negocios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $negocioId]);

        return $stmt->fetchColumn() !== false;
    }

    public function productoExists(int $negocioId, string $nombre, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM productos
                WHERE negocio_id = :negocio_id
                  AND LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))';
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':negocio_id', $negocioId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        if ($exceptId !== null) {
            $stmt->bindValue(':except_id', $exceptId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function crearProducto(array $data): int
    {
        $sql = 'INSERT INTO productos (negocio_id, nombre, descripcion, precio, stock, imagen)
                VALUES (:negocio_id, :nombre, :descripcion, :precio, :stock, :imagen)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function actualizarProducto(int $id, array $data): void
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = $field . ' = :' . $field;
        }

        $data['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE productos SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function eliminarProducto(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM productos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
?>