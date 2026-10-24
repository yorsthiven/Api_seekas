<?php
namespace Repositories;
require_once __DIR__ . '/../config/Database.php';

use Config\Database;
use PDO;
class ProductoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    //: tipo de dato a retornar un arreglo
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM productos"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>