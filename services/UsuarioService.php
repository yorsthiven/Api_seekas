<?php
namespace Services;

require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Repositories\UsuarioRepository;

class UsuarioService
{
    private UsuarioRepository $repo;
    private const ALLOWED_FIELDS = ['negocio_id', 'email', 'password'];

    public function __construct()
    {
        $this->repo = new UsuarioRepository();
    }

    public function listarUsuarios(int $negocioId): array
    {
        if ($negocioId <= 0) {
            throw new \InvalidArgumentException('El ID del negocio debe ser un entero positivo.');
        }

        return $this->repo->getAll($negocioId);
    }

    public function obtenerUsuario(int $id, int $negocioId): array
    {
        $this->validarId($id);
        $user = $this->repo->getById($id, $negocioId);
        if ($user === null) {
            throw new \OutOfBoundsException('No se encontró el usuario solicitado.');
        }

        return $user;
    }

    public function crearUsuario(array $data, ?int $negocioId = null): array
    {
        if ($negocioId !== null && isset($data['negocio_id'])
            && $this->parsePositiveInteger($data['negocio_id']) !== $negocioId) {
            throw new AuthorizationException('No puede crear usuarios para otro negocio.');
        }
        if ($negocioId !== null) {
            $data['negocio_id'] = $negocioId;
        }
        $user = $this->validarDatos($data, false);
        $businessId = $user['negocio_id'];
        $this->validarNegocio($businessId);
        $this->validarEmailUnico($user['email'], $businessId);
        $user['password'] = $this->hashPassword($user['password']);

        return $this->obtenerUsuario($this->repo->create($user), $businessId);
    }

    public function actualizarUsuario(int $id, array $data, int $negocioId): array
    {
        $this->validarId($id);
        $current = $this->repo->getById($id, $negocioId);
        if ($current === null) {
            throw new \OutOfBoundsException('No se encontró el usuario que se desea actualizar.');
        }

        $user = $this->validarDatos($data, true);
        if (isset($user['negocio_id']) && $user['negocio_id'] !== $negocioId) {
            throw new AuthorizationException('No puede mover el usuario a otro negocio.');
        }
        $email = $user['email'] ?? $current['email'];
        if (isset($user['negocio_id'])) {
            $this->validarNegocio($negocioId);
        }
        if (isset($user['negocio_id']) || isset($user['email'])) {
            $this->validarEmailUnico($email, $negocioId, $id);
        }
        if (isset($user['password'])) {
            $user['password'] = $this->hashPassword($user['password']);
        }

        $this->repo->update($id, $negocioId, $user);
        return $this->obtenerUsuario($id, $negocioId);
    }

    public function eliminarUsuario(int $id, int $negocioId): void
    {
        $this->validarId($id);
        if (!$this->repo->delete($id, $negocioId)) {
            throw new \OutOfBoundsException('No se encontró el usuario que se desea eliminar.');
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
            $validated['negocio_id'] = $this->parsePositiveInteger($data['negocio_id']);
        }
        if (array_key_exists('email', $data)) {
            if (!is_string($data['email'])) {
                throw new \InvalidArgumentException('El correo debe ser texto.');
            }
            $email = strtolower(trim($data['email']));
            if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException('El correo electrónico no es válido.');
            }
            $validated['email'] = $email;
        }
        if (array_key_exists('password', $data)) {
            if (!is_string($data['password'])) {
                throw new \InvalidArgumentException('La contraseña debe ser texto.');
            }
            $passwordLength = strlen($data['password']);
            if ($passwordLength < 8 || $passwordLength > 72) {
                throw new \InvalidArgumentException(
                    'La contraseña debe tener entre 8 y 72 bytes.'
                );
            }
            $validated['password'] = $data['password'];
        }

        return $validated;
    }

    private function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new \RuntimeException('No fue posible proteger la contraseña.');
        }

        return $hash;
    }

    private function validarNegocio(int $negocioId): void
    {
        if (!$this->repo->negocioExists($negocioId)) {
            throw new \InvalidArgumentException('El negocio indicado no existe.');
        }
    }

    private function validarEmailUnico(string $email, int $negocioId, ?int $exceptId = null): void
    {
        if ($this->repo->existsByEmail($email, $negocioId, $exceptId)) {
            throw new \DomainException('Ya existe un usuario con ese correo para el negocio indicado.');
        }
    }

    private function validarId(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('El ID del usuario debe ser un entero positivo.');
        }
    }

    private function parsePositiveInteger($value): int
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
            throw new \InvalidArgumentException('El negocio_id debe ser un entero positivo.');
        }

        return $parsed;
    }
}
