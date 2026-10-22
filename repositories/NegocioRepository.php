<?php
require_once __DIR__ . '/../config/Database.php';

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
}
?>