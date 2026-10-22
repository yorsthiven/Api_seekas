<?php
    require_once __DIR__ . '/../config/Database.php';

    class ProductoRepository{
        private PDO $db;

        public function __construct(){
            $this -> db = Database::getConnection();   
        }

        
    }
?>



<!-- 
        $stmt = $this->db->prepare(
        // Prepara una sentencia SQL en la base de datos. Prevenir inyecciones SQL separando la estructura de los datos.

            "SELECT id, negocio_id, nombre, descripcion, precio, stock, imagen, creado_en 
             FROM productos 
             WHERE negocio_id = :negocio_id"
            // La consulta SQL que busca los productos que pertenecen a un negocio específico usando un comodín (:negocio_id).
        );

        $stmt->execute(['negocio_id' => $negocioId]);
        // Ejecuta la consulta preparada, inyectando de forma segura el valor recibido en el parámetro :negocio_id.

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Recupera todos los registros encontrados y los devuelve en forma de un arreglo asociativo (clave-valor).
    }

    public function getById(int $id, int $negocioId): ?array {
    // Método para buscar un producto único por su ID y asegurar que pertenezca al negocio (multi-tenant). Devuelve un arreglo o nulo (?array).

        $stmt = $this->db->prepare(
            "SELECT id, negocio_id, nombre, descripcion, precio, stock, imagen, creado_en 
             FROM productos 
             WHERE id = :id AND negocio_id = :negocio_id"
        );
        // Consulta SQL con doble condición de seguridad (ID del producto y que pertenezca al negocio correcto).

        $stmt->execute(['id' => $id, 'negocio_id' => $negocioId]);
        // Ejecuta la sentencia pasando ambos parámetros de forma segura.

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Obtiene un único registro como arreglo asociativo.

        return $result ?: null;
        // Si encontró el producto lo retorna, de lo contrario devuelve null explícitamente.
    }
} -->