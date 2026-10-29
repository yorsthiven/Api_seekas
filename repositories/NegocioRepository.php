<?php
namespace Repositories;
require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;
class NegocioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Obtener todos los negocios
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, tipo, creado_en FROM negocios"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNegocioById(int $negocioId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id,nombre,tipo,creado_en
                FROM negocios
                WHERE id= :negocio_id"
        );

        $stmt->execute(['negocio_id ' => $negocioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateConfiguration(int $id, array $data): bool
    {
        $sql = "UPDATE negocios SET
                nombre = :nombre,
                tipo = :tipo, 
                descripcion = :descripcion, 
                logo_url = :logo_url, 
                banner_url = :banner_url, 
                telefono_whatsapp = :telefono_whatsapp
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $resp = $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'] ?? null,
            'tipo' => $data['tipo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'banner_url' => $data['banner_url'] ?? null,
            'telefono_whatsapp' => $data['telefono_whatsapp'] ?? null
        ]);

        return $resp;
    }
}
?>