<?php
namespace Services;

require_once __DIR__ . '/../repositories/PedidoRepository.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Repositories\PedidoRepository;

class PedidoService
{
    private PedidoRepository $repo;
    private const ALLOWED_FIELDS = [
        'negocio_id',
        'cliente_nombre',
        'cliente_telefono',
        'total',
        'estado',
    ];

    public function __construct()
    {
        $this->repo = new PedidoRepository();
    }

    public function listarPedidos(int $negocioId, ?int $filtroNegocioId = null): array
    {
        if ($negocioId <= 0) {
            throw new \InvalidArgumentException('El ID del negocio debe ser un entero positivo.');
        }
        if ($filtroNegocioId !== null && $filtroNegocioId !== $negocioId) {
            throw new AuthorizationException('No tiene permiso para consultar pedidos de otro negocio.');
        }

        return $this->repo->getAll($negocioId);
    }

    public function obtenerPedido(int $id, int $negocioId): array
    {
        $this->validarId($id);
        $order = $this->repo->getById($id, $negocioId);
        if ($order === null) {
            throw new \OutOfBoundsException('No se encontró el pedido solicitado.');
        }

        return $order;
    }

    public function crearPedido(array $data, int $negocioId): array
    {
        if (isset($data['negocio_id'])
            && $this->parsePositiveInteger($data['negocio_id'], 'negocio_id') !== $negocioId) {
            throw new AuthorizationException('No puede crear pedidos para otro negocio.');
        }
        $data['negocio_id'] = $negocioId;
        $order = $this->validarDatos($data, false);
        $this->validarNegocio($order['negocio_id']);

        return $this->obtenerPedido($this->repo->create($order), $negocioId);
    }

    public function actualizarPedido(int $id, array $data, int $negocioId): array
    {
        $this->validarId($id);
        $current = $this->repo->getById($id, $negocioId);
        if ($current === null) {
            throw new \OutOfBoundsException('No se encontró el pedido que se desea actualizar.');
        }

        $order = $this->validarDatos($data, true);
        if (isset($order['negocio_id']) && $order['negocio_id'] !== $negocioId) {
            throw new AuthorizationException('No puede mover el pedido a otro negocio.');
        }
        if (isset($order['negocio_id'])) {
            $this->validarNegocio($order['negocio_id']);
        }
        $this->repo->update($id, $negocioId, $order);
        return $this->obtenerPedido($id, $negocioId);
    }

    public function eliminarPedido(int $id, int $negocioId): void
    {
        $this->validarId($id);
        $order = $this->repo->getById($id, $negocioId);
        if ($order === null) {
            throw new \OutOfBoundsException('No se encontró el pedido que se desea eliminar.');
        }
        if (strtolower(trim((string) $order['estado'])) !== 'pendiente') {
            throw new \DomainException('Solo se pueden eliminar pedidos pendientes.');
        }
        if (!$this->repo->deletePending($id, $negocioId)) {
            $current = $this->repo->getById($id, $negocioId);
            if ($current === null) {
                throw new \OutOfBoundsException('No se encontró el pedido que se desea eliminar.');
            }
            throw new \DomainException('El pedido dejó de estar pendiente y no se puede eliminar.');
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
        foreach ($parcial ? [] : self::ALLOWED_FIELDS as $requiredField) {
            if (!array_key_exists($requiredField, $data)) {
                throw new \InvalidArgumentException("El campo {$requiredField} es obligatorio.");
            }
        }
        if ($parcial && $data === []) {
            throw new \InvalidArgumentException('Debe enviar al menos un campo para actualizar.');
        }

        $validated = [];
        if (array_key_exists('negocio_id', $data)) {
            $validated['negocio_id'] = $this->parsePositiveInteger($data['negocio_id'], 'negocio_id');
        }
        foreach (['cliente_nombre', 'cliente_telefono', 'estado'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if (!is_string($data[$field]) || trim($data[$field]) === '') {
                throw new \InvalidArgumentException("El campo {$field} es obligatorio y debe ser texto.");
            }
            $value = trim($data[$field]);
            $maxLength = $field === 'cliente_nombre' ? 255 : 50;
            if (strlen($value) > $maxLength) {
                throw new \InvalidArgumentException(
                    "El campo {$field} no puede superar {$maxLength} caracteres."
                );
            }
            $validated[$field] = $value;
        }
        if (array_key_exists('total', $data)) {
            if ((!is_int($data['total']) && !is_float($data['total']) && !is_string($data['total']))
                || !is_numeric($data['total'])
                || !is_finite((float) $data['total'])) {
                throw new \InvalidArgumentException('El total debe ser un número válido.');
            }
            if ((float) $data['total'] < 0) {
                throw new \InvalidArgumentException('El total no puede ser negativo.');
            }
            $validated['total'] = $data['total'];
        }

        return $validated;
    }

    private function validarNegocio(int $negocioId): void
    {
        if (!$this->repo->negocioExists($negocioId)) {
            throw new \InvalidArgumentException('El negocio indicado no existe.');
        }
    }

    private function validarId(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('El ID del pedido debe ser un entero positivo.');
        }
    }

    private function parsePositiveInteger($value, string $field): int
    {
        if (is_int($value)) {
            $parsed = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            if ($parsed === false) {
                $parsed = null;
            }
        } else {
            $parsed = null;
        }

        if ($parsed === null || $parsed <= 0) {
            throw new \InvalidArgumentException("El campo {$field} debe ser un entero positivo.");
        }

        return $parsed;
    }
}
