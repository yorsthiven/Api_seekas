<?php
namespace Services;
require_once __DIR__ . '/../repositories/ProductoRepository.php';

use Repositories\ProductoRepository;
class ProductoService
{
    private ProductoRepository $repo;

    public function __construct()
    {
        $this->repo = new ProductoRepository();
    }
    public function listarProductos(): array
    {
        $productos = $this->repo->getAll();

        return array_map(function ($n) {
            return [
                'id' => $n['id'],
                'negocio_id' => $n['negocio_id'],
                'nombre' => $n['nombre'],
                'descripcion' => $n['descripcion'],
                'precio' => $n['precio'],
                'stock' => $n['stock'],
                'imagen' => $n['imagen'],
                'creado_en' => $n['creado_en']
            ];
        }, $productos);
    }
}

?>