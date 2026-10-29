# Configuración segura del API en cPanel

## 1. Crear la configuración privada

En cPanel abre **Administrador de archivos** y crea `api-secrets.php` en la carpeta principal de tu cuenta, al mismo nivel que `public_html` y `api.proweb.com.co`, no dentro de ninguna carpeta pública. El código lo busca dos niveles arriba de `config/Database.php`.

Pega esta estructura y reemplaza cada valor entre comillas por su valor real:

```php
<?php
return [
    'db_host' => 'HOST_DE_MYSQL',
    'db_name' => 'NOMBRE_DE_LA_BASE',
    'db_user' => 'USUARIO_DE_MYSQL',
    'db_password' => 'CONTRASENA_DE_MYSQL',
    'jwt_secret' => 'SECRETO_ALEATORIO_1',
    'api_admin_token' => 'SECRETO_ALEATORIO_2',
];
```

Consulta el host, nombre de base de datos y usuario en la sección **Bases de datos MySQL** de cPanel. No asumas que el host es `localhost`: usa el valor que indique el proveedor. En el administrador de archivos, configura los permisos del archivo para que solo tu usuario y el servidor puedan leerlo (por ejemplo, `600` o `640`, según lo permita el hosting).

Genera dos secretos aleatorios distintos de al menos 32 caracteres desde el **Terminal de cPanel**, si está disponible:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Ejecuta el comando dos veces y pega cada resultado en un campo diferente. No los envíes por chat, no los pongas en la landing page y no los guardes en Git.

Si tu plan no permite crear archivos fuera de `public_html`, consulta al soporte de Latinoamérica Hosting cómo definir variables privadas de PHP (`JWT_SECRET`, `API_ADMIN_TOKEN`) y cómo almacenar las credenciales de MySQL fuera del directorio público. No publiques `api-secrets.php` en `public_html`.

## 2. Rotar la contraseña de MySQL

La configuración anterior del proyecto contenía credenciales de la base de datos en un archivo versionado. Por precaución, cambia la contraseña de ese usuario desde cPanel y actualiza `db_password` en `api-secrets.php`. La contraseña anterior pudo permanecer en el historial de Git, por lo que eliminar el archivo de la versión actual no sustituye la rotación.

Para desarrollo local, configura [la plantilla local](./config/database.local.php.example) para que use tu base de XAMPP. El ejemplo apunta a `prowebco_prueba1` en `127.0.0.1:3307`, según la instancia local mostrada en phpMyAdmin; ajusta host, puerto, usuario y contraseña si tu instalación usa otros valores. La base mostrada ya contiene datos, por lo que las operaciones CRUD locales sí podrán modificarlos o eliminarlos. No ejecutes [el esquema de base vacía](./migrations/local_development_schema.sql) sobre esa base existente. Configura también dos secretos aleatorios locales distintos. Puedes generarlos en **XAMPP Shell** con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`; ejecuta el comando dos veces y usa un resultado distinto en cada campo. Este archivo está excluido de Git.

Cuando la petición llegue por `localhost` o `127.0.0.1`, la aplicación usa exclusivamente `config/database.local.php` y no busca el archivo privado del hosting. Si falta la configuración local, la API falla con un mensaje claro en vez de conectarse a producción. En el hosting, conserva `api-secrets.php` fuera de las carpetas públicas; el despliegue de cPanel publica el código en `/home/prowebco/api.proweb.com.co`, que debe coincidir con el directorio configurado para el subdominio.

Pasos en XAMPP:

1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Confirma en phpMyAdmin que la base local se llama `prowebco_prueba1` y el servidor usa el puerto `3307`.
3. En `config/database.local.php`, configura `db_host` como `127.0.0.1`, `db_port` como `3307`, `db_name` como `prowebco_prueba1` y el usuario y contraseña que usas para MySQL local. Completa también los dos secretos.
4. Si el proyecto está dentro de `htdocs/Api_seekas`, la URL local será `http://localhost/Api_seekas`. La aplicación también acepta rutas desde esa subcarpeta, por ejemplo `http://localhost/Api_seekas/auth/login`.
5. Crea un usuario de prueba enviando `POST http://localhost/Api_seekas/usuarios` con el encabezado `X-Admin-Token` igual al `api_admin_token` local, y el JSON `{"negocio_id":1,"email":"prueba@example.com","password":"ClaveLocal123"}`. Luego inicia sesión en `POST http://localhost/Api_seekas/auth/login` con esos mismos datos. Usa una contraseña ficticia solo para desarrollo.

## 3. Preparar la contraseña de usuarios

Ejecuta una sola vez [la migración de contraseña](./migrations/20261029_secure_user_passwords.sql) si todavía no lo hiciste. Mantiene los valores actuales y permite guardar hashes de hasta 255 caracteres.

Las contraseñas nuevas se guardan como hashes bcrypt. Cuando una cuenta existente inicia sesión con su contraseña antigua, esta se reemplaza por un hash. Hasta que cada usuario inicie sesión, su contraseña antigua seguirá almacenada como estaba. Ningún endpoint devuelve la contraseña o el hash.

## 4. Iniciar sesión y usar las rutas

Envía `POST /auth/login` con este JSON:

```json
{
  "negocio_id": 1,
  "email": "correo-del-usuario",
  "password": "contrasena-del-usuario"
}
```

Usa `token.access_token` de la respuesta en las solicitudes CRUD:

```http
Authorization: Bearer <access_token>
```

El token dura una hora y solo permite acceder a los datos del negocio asociado a la cuenta. Las páginas pueden llamar al login desde el navegador, pero deben usar HTTPS y no guardar contraseñas; conserva el token en memoria cuando sea posible. No pongas el token administrativo en el frontend.

## 5. Crear negocios y primeros usuarios

Las operaciones normales de usuarios requieren el token Bearer. Para crear un negocio o el primer usuario de un negocio nuevo, usa Postman u otra herramienta privada y envía el encabezado:

```http
X-Admin-Token: <api_admin_token>
```

El administrador crea el negocio con `POST /negocios`, luego el usuario con `POST /usuarios`, enviando `negocio_id`, `email` y `password`. Después esa persona inicia sesión y usa su token. No agregues `api_admin_token` al código JavaScript ni a las peticiones del navegador.

Antes de publicar, activa HTTPS y pregunta al proveedor si puede limitar los intentos de `POST /auth/login`; esta API no incorpora todavía un limitador de intentos.
