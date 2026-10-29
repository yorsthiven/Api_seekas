<?php
namespace Services;
require_once __DIR__ . '/../repositories/ProductoRepository.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Repositories\ProductoRepository;
class ProductoService
{
    private ProductoRepository $repo;
    private const ALLOWED_FIELDS = [
        'negocio_id',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'imagen',
    ];

    public function __construct()
    {
        $this->repo = new ProductoRepository();
    }
    public function listarProductos(int $negocioId, ?int $filtroNegocioId = null): array
    {
        if ($negocioId <= 0) {
            throw new \InvalidArgumentException('El ID del negocio debe ser un entero positivo.');
        }
        if ($filtroNegocioId !== null && $filtroNegocioId !== $negocioId) {
            throw new AuthorizationException('No tiene permiso para consultar productos de otro negocio.');
        }

        return $this->repo->getAll($negocioId);
    }

    public function obtenerProducto(int $id, int $negocioId): array
    {
        $this->validarId($id);
        $producto = $this->repo->getById($id, $negocioId);
        if ($producto === null) {
            throw new \OutOfBoundsException('No se encontró el producto solicitado.');
        }

        return $producto;
    }

    public function crearProducto(array $data, int $negocioId): array
    {
        if (isset($data['negocio_id'])
            && $this->validarEnteroPositivo($data['negocio_id'], 'negocio_id') !== $negocioId) {
            throw new AuthorizationException('No puede crear productos para otro negocio.');
        }
        $data['negocio_id'] = $negocioId;
        $producto = $this->validarDatos($data, false);
        $this->validarNegocio($producto['negocio_id']);
        $this->validarDuplicado($producto['negocio_id'], $producto['nombre']);

        $id = $this->repo->crearProducto($producto);
        return $this->obtenerProducto($id, $negocioId);
    }

    public function actualizarProducto(int $id, array $data, int $negocioId): array
    {
        $this->validarId($id);
        $actual = $this->repo->getById($id, $negocioId);
        if ($actual === null) {
            throw new \OutOfBoundsException('No se encontró el producto que se desea actualizar.');
        }

        $producto = $this->validarDatos($data, true);
        $targetBusinessId = $producto['negocio_id'] ?? (int) $actual['negocio_id'];
        if ($targetBusinessId !== $negocioId) {
            throw new AuthorizationException('No puede mover el producto a otro negocio.');
        }
        $nombre = $producto['nombre'] ?? $actual['nombre'];
        if (isset($producto['negocio_id'])) {
            $this->validarNegocio($targetBusinessId);
        }

        if (isset($producto['negocio_id']) || isset($producto['nombre'])) {
            $this->validarDuplicado($targetBusinessId, $nombre, $id);
        }

        $this->repo->actualizarProducto($id, $negocioId, $producto);
        return $this->obtenerProducto($id, $negocioId);
    }

    public function eliminarProducto(int $id, int $negocioId): void
    {
        $this->validarId($id);
        if (!$this->repo->eliminarProducto($id, $negocioId)) {
            throw new \OutOfBoundsException('No se encontró el producto que se desea eliminar.');
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

        $requiredFields = $parcial
            ? []
            : ['negocio_id', 'nombre', 'precio', 'stock'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("El campo {$field} es obligatorio.");
            }
        }
        if ($parcial && $data === []) {
            throw new \InvalidArgumentException('Debe enviar al menos un campo para actualizar.');
        }

        $validated = [];
        if (array_key_exists('negocio_id', $data)) {
            $validated['negocio_id'] = $this->validarEnteroPositivo($data['negocio_id'], 'negocio_id');
        }
        if (array_key_exists('nombre', $data)) {
            if (!is_string($data['nombre'])) {
                throw new \InvalidArgumentException('El nombre debe ser texto.');
            }
            $nombre = trim($data['nombre']);
            if ($nombre === '') {
                throw new \InvalidArgumentException('El nombre del producto es obligatorio.');
            }
            if (strlen($nombre) > 255) {
                throw new \InvalidArgumentException('El nombre no puede superar 255 caracteres.');
            }
            $validated['nombre'] = $nombre;
        }
        if (array_key_exists('precio', $data)) {
            if (!is_int($data['precio']) && !is_float($data['precio']) && !is_string($data['precio'])) {
                throw new \InvalidArgumentException('El precio debe ser un número válido.');
            }
            if (!is_numeric($data['precio']) || !is_finite((float) $data['precio'])) {
                throw new \InvalidArgumentException('El precio debe ser un número válido.');
            }
            if ((float) $data['precio'] < 0) {
                throw new \InvalidArgumentException('El precio no puede ser negativo.');
            }
            $validated['precio'] = $data['precio'];
        }
        if (array_key_exists('stock', $data)) {
            $validated['stock'] = $this->validarEnteroNoNegativo($data['stock'], 'stock');
        }
        foreach (['descripcion', 'imagen'] as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] !== null && !is_string($data[$field])) {
                    throw new \InvalidArgumentException("El campo {$field} debe ser texto o null.");
                }
                $value = $data[$field] === null ? null : trim($data[$field]);
                $maxLength = $field === 'imagen' ? 255 : 5000;
                if ($value !== null && strlen($value) > $maxLength) {
                    throw new \InvalidArgumentException(
                        "El campo {$field} no puede superar {$maxLength} caracteres."
                    );
                }
                $validated[$field] = $value === '' ? null : $value;
            } elseif (!$parcial) {
                $validated[$field] = null;
            }
        }

        return $validated;
    }

    private function validarNegocio(int $negocioId): void
    {
        if (!$this->repo->negocioExists($negocioId)) {
            throw new \InvalidArgumentException('El negocio indicado no existe.');
        }
    }

    private function validarDuplicado(int $negocioId, string $nombre, ?int $exceptId = null): void
    {
        if ($this->repo->productoExists($negocioId, $nombre, $exceptId)) {
            throw new \DomainException(
                'Ya existe un producto con ese nombre para el negocio indicado.'
            );
        }
    }

    private function validarId(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('El ID del producto debe ser un entero positivo.');
        }
    }

    private function validarEnteroPositivo($value, string $field): int
    {
        $integer = $this->parseInteger($value);
        if ($integer === null || $integer <= 0) {
            throw new \InvalidArgumentException("El campo {$field} debe ser un entero positivo.");
        }

        return $integer;
    }

    private function validarEnteroNoNegativo($value, string $field): int
    {
        $integer = $this->parseInteger($value);
        if ($integer === null || $integer < 0) {
            throw new \InvalidArgumentException("El campo {$field} debe ser un entero mayor o igual a cero.");
        }

        return $integer;
    }

    private function parseInteger($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            return $parsed === false ? null : $parsed;
        }
        if (is_float($value) && is_finite($value) && floor($value) === $value
            && $value <= PHP_INT_MAX && $value >= PHP_INT_MIN) {
            return (int) $value;
        }

        return null;
    }
}

?>