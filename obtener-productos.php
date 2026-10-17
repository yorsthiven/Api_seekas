<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';

$negocio_id = isset($_GET['negocio_id']) ? intval($_GET['negocio_id']) : 0;

if ($negocio_id <= 0) {
    echo json_encode(["error" => "ID de negocio no válido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, precio, stock FROM productos WHERE negocio_id = ?");
    $stmt->execute([$negocio_id]);
    $productos = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "negocio_id" => $negocio_id,
        "productos" => $productos
    ], JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Error al obtener los productos"
    ]);
}
?>