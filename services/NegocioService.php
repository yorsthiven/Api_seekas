<?php
namespace Services;
require_once __DIR__ . '/../repositories/negocioRepository.php';

use Repositories\NegocioRepository;
class NegocioService
{
    private NegocioRepository $repo;

    public function __construct()
    {
        $this->repo = new NegocioRepository();
    }

    public function listarNegocios(): array
    {
        $negocios = $this->repo->getAll();

        return array_map(function ($n) {
            return [
                'id' => (int) $n['id'],
                'nombre' => $n['nombre'],
                'tipo' => $n['tipo'],
                'creado_en' => $n['creado_en']
            ];
        }, $negocios);
    }

    public function obtenerNegocioPorId(int $id): array
    {
        //logica para traer por id
        return $this->repo->getNegocioById($id);
    }

    public function actualizarConfiguracion(int $id, array $data): bool {
        // Aquí puedes agregar validaciones de negocio si lo deseas 
        // (ej: validar que el nombre no esté vacío)
        if (empty($data['nombre'])) {
            throw new \Exception("El nombre del negocio es obligatorio.");
        }

        return $this->repo->updateConfiguration($id, $data);
    }
}

?>