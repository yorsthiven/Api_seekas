<?php
namespace Services;

require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AuthenticationException.php';

use Core\Auth;
use Core\AuthenticationException;
use Repositories\UsuarioRepository;

class AuthService
{
    private UsuarioRepository $users;

    public function __construct()
    {
        $this->users = new UsuarioRepository();
    }

    public function iniciarSesion(array $data): array
    {
        $businessId = $this->parsePositiveInteger($data['negocio_id'] ?? null);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        if (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false
            || strlen(trim($email)) > 254 || !is_string($password) || $password === '') {
            throw new \InvalidArgumentException('Debe enviar negocio_id, un correo válido y una contraseña.');
        }

        $user = $this->users->getForLogin(strtolower(trim($email)), $businessId);
        if ($user === null) {
            throw new AuthenticationException('Credenciales incorrectas.');
        }

        $storedPassword = (string) $user['password'];
        $passwordInfo = password_get_info($storedPassword);
        $isHash = ($passwordInfo['algo'] ?? null) !== null;
        if ($isHash) {
            $passwordIsValid = password_verify($password, $storedPassword);
        } else {
            $passwordIsValid = hash_equals($storedPassword, $password);
            if ($passwordIsValid) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                if ($hash === false) {
                    throw new \RuntimeException('No fue posible proteger la contraseña.');
                }
                $this->users->updatePassword((int) $user['id'], $hash);
            }
        }

        if (!$passwordIsValid) {
            throw new AuthenticationException('Credenciales incorrectas.');
        }

        return [
            'token' => Auth::issueToken(
                (int) $user['id'],
                (int) $user['negocio_id'],
                (string) $user['email']
            ),
            'user' => [
                'id' => (int) $user['id'],
                'negocio_id' => (int) $user['negocio_id'],
                'email' => $user['email'],
            ],
        ];
    }

    private function parsePositiveInteger($value): int
    {
        if (is_int($value)) {
            $id = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false) {
                $id = null;
            }
        } else {
            $id = null;
        }

        if ($id === null || $id <= 0) {
            throw new \InvalidArgumentException('El negocio_id debe ser un entero positivo.');
        }

        return $id;
    }
}
