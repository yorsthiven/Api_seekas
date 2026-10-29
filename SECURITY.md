# Seguridad de la API

## Configuración requerida

Configura estas variables en el servidor; no las guardes en el repositorio ni las envíes al navegador:

- `JWT_SECRET`: secreto aleatorio de al menos 32 caracteres para firmar tokens.
- `API_ADMIN_TOKEN`: token administrativo aleatorio de al menos 32 caracteres.

Puedes generar valores en el servidor con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Usa un valor distinto para cada variable y no compartas los valores generados.

## Inicio de sesión

Envía `POST /auth/login` con `negocio_id`, `email` y `password`. La respuesta contiene un token Bearer válido durante una hora. En las demás solicitudes CRUD envía:

```http
Authorization: Bearer <access_token>
```

Los tokens solo permiten acceder a datos del negocio asociado a la cuenta. Las contraseñas nuevas se guardan como hashes bcrypt. Cuando una cuenta existente inicia sesión correctamente con su contraseña antigua en texto plano, esta se reemplaza por un hash. Las cuentas que todavía no hayan iniciado sesión seguirán teniendo su contraseña antigua en la base de datos hasta entonces.

## Alta administrativa

`POST /negocios` requiere el encabezado `X-Admin-Token: <API_ADMIN_TOKEN>`. Para crear el primer usuario de un negocio nuevo, usa el mismo encabezado en `POST /usuarios` e incluye `negocio_id`, `email` y `password`. Después, ese usuario puede iniciar sesión y operar sobre los datos de su negocio.

Los usuarios autenticados pueden crear, consultar y actualizar cuentas de su propio negocio; esta API todavía no define roles distintos entre esos usuarios. Protege las credenciales administrativas y usa HTTPS en producción. Configura también un límite de intentos en `/auth/login` mediante el proxy o firewall del servidor.

## Migración de la columna de contraseñas

Antes de habilitar los inicios de sesión, ejecuta una vez [la migración SQL](./migrations/20261029_secure_user_passwords.sql) sobre la base de datos. Ajusta la columna `usuarios.password` a `VARCHAR(255)` sin borrar las contraseñas existentes y permite guardar los nuevos hashes.
