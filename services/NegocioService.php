<?php
namespace Services;

require_once __DIR__ . '/../repositories/NegocioRepository.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Repositories\NegocioRepository;

class NegocioService
{
    private NegocioRepository $repo;
    private const ALLOWED_FIELDS = [
        'nombre',
        'tipo',
        'descripcion',
        'logo_url',
        'banner_url',
        'telefono_whatsapp',
    ];

    public function __construct()
    {
        $this->repo = new NegocioRepository();
    }

    public function listarNegocios(int $negocioId): array
    {
        return $this->repo->getAll($negocioId);
    }

    public function obtenerNegocioPorId(int $id, int $negocioAutenticado): array
    {
        $this->validarId($id);
        if ($id !== $negocioAutenticado) {
            throw new AuthorizationException('No tiene permiso para consultar otro negocio.');
        }
        $business = $this->repo->getById($id);
        if ($business === null) {
            throw new \OutOfBoundsException('No se encontró el negocio solicitado.');
        }

        return $business;
    }

    public function crearNegocio(array $data): array
    {
        $business = $this->validarDatos($data, false);
        $this->validarNombreUnico($business['nombre']);

        $id = $this->repo->create($business);
        $created = $this->repo->getById($id);
        if ($created === null) {
            throw new \RuntimeException('El negocio se creó, pero no fue posible consultarlo.');
        }

        return $created;
    }

    public function actualizarNegocio(int $id, array $data, int $negocioAutenticado): array
    {
        $this->validarId($id);
        if ($id !== $negocioAutenticado) {
            throw new AuthorizationException('No tiene permiso para actualizar otro negocio.');
        }
        $current = $this->repo->getById($id);
        if ($current === null) {
            throw new \OutOfBoundsException('No se encontró el negocio que se desea actualizar.');
        }

        $business = $this->validarDatos($data, true);
        if (isset($business['nombre'])) {
            $this->validarNombreUnico($business['nombre'], $id);
        }
        $this->repo->update($id, $business);

        return $this->obtenerNegocioPorId($id, $negocioAutenticado);
    }

    public function eliminarNegocio(int $id, int $negocioAutenticado): void
    {
        $this->validarId($id);
        if ($id !== $negocioAutenticado) {
            throw new AuthorizationException('No tiene permiso para eliminar otro negocio.');
        }
        if (!$this->repo->delete($id)) {
            throw new \OutOfBoundsException('No se encontró el negocio que se desea eliminar.');
        }
    }

    private function validarDatos(array $data, bool $parcial): array
    {
        $unknownFields = array_diff(array_keys($data), self::ALLOWED_FIELDS);
        if ($unknownFields !== []) {
            throw new \InvalidArgumentException(
                'Campos no permitidos: ' . implode(', ', $unknownFields) . '.'
            );
        }

        foreach ($parcial ? [] : ['nombre', 'tipo'] as $requiredField) {
            if (!array_key_exists($requiredField, $data)) {
                throw new \InvalidArgumentException("El campo {$requiredField} es obligatorio.");
            }
        }
        if ($parcial && $data === []) {
            throw new \InvalidArgumentException('Debe enviar al menos un campo para actualizar.');
        }

        $validated = [];
        foreach (self::ALLOWED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                if (!$parcial && $field !== 'nombre' && $field !== 'tipo') {
                    $validated[$field] = null;
                }
                continue;
            }
            if ($field === 'nombre' || $field === 'tipo') {
                if (!is_string($data[$field]) || trim($data[$field]) === '') {
                    throw new \InvalidArgumentException("El campo {$field} es obligatorio y debe ser texto.");
                }
                $value = trim($data[$field]);
                if (strlen($value) > 255) {
                    throw new \InvalidArgumentException("El campo {$field} no puede superar 255 caracteres.");
                }
                $validated[$field] = $value;
                continue;
            }

            if ($data[$field] !== null && !is_string($data[$field])) {
                throw new \InvalidArgumentException("El campo {$field} debe ser texto o null.");
            }
            $value = $data[$field] === null ? null : trim($data[$field]);
            $maxLength = $field === 'descripcion'
                ? 5000
                : ($field === 'telefono_whatsapp' ? 50 : 255);
            if ($value !== null && strlen($value) > $maxLength) {
                throw new \InvalidArgumentException(
                    "El campo {$field} no puede superar {$maxLength} caracteres."
                );
            }
            $validated[$field] = $value === '' ? null : $value;
        }

        return $validated;
    }

    private function validarNombreUnico(string $nombre, ?int $exceptId = null): void
    {
        if ($this->repo->existsByName($nombre, $exceptId)) {
            throw new \DomainException('Ya existe un negocio con ese nombre.');
        }
    }

    private function validarId(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('El ID del negocio debe ser un entero positivo.');
        }
    }
}
