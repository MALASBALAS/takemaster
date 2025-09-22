# TAKEMASTER

Aplicación web en PHP para gestión y visualización de información, con frontend HTML/CSS/JS y backend PHP (mysqli) sobre MariaDB.

## Características principales
- Autenticación con contraseñas hasheadas (password_hash/password_verify)
- Sesiones seguras (cookies HttpOnly, SameSite, regeneración de ID)
- Protección CSRF en formularios y peticiones JSON
- Consultas preparadas (mysqli) y UTF-8 (utf8mb4)
- Panel de control (dashboard) y páginas públicas en `pags/`
- Estructura modular con funciones en `funciones/` y utilidades en `src/nav/`

## Requisitos
- PHP 8.1+ (recomendado 8.3) con extensiones: mysqli, openssl, mbstring, json
- MariaDB/MySQL
- Servidor web (Nginx o Apache)

## Estructura del proyecto (resumen)
- `index.php` – página de entrada
- `auth/` – login, registro y logout
- `dashboard/` – vistas autenticadas y endpoints de guardado
- `funciones/` – endpoints utilitarios (datos, tarifas, etc.)
- `pags/` – páginas públicas (about, terms, privacy, etc.)
- `plantillas/` – sistema de plantillas (WIP)
- `src/` – assets (css/js/img) y utilidades (`src/nav/*`)

## Configuración
Hay soporte para variables de entorno. Si no se definen, se usan valores por defecto.
- BASE_URL
- DB_HOST, DB_NAME, DB_USER, DB_PASS

Importante:
- Por seguridad, este repositorio NO incluye `src/nav/config.php` (está en `.gitignore`). La aplicación no funcionará sin un `config.php` válido o sin variables de entorno configuradas.
- Se ha dejado una copia/plantilla sin credenciales en `src/nav/config copy.php`. Cópiala a `src/nav/config.php` y completa tus valores, o usa variables de entorno (consulta `.env.example`).

Archivos clave:
- `src/nav/config.php` – requerido en ejecución; no viene en el repo, créalo desde la copia o usa variables de entorno
- `src/nav/bootstrap.php` – arranque de sesión segura, helpers CSRF y cabeceras
- `src/nav/db_connection.php` – conexión centralizada a la base de datos
- `.env.example` – ejemplo de variables de entorno; copia a `.env` para desarrollo local

## Despliegue rápido (Nginx + PHP-FPM)
Ejemplo de bloque PHP (ajusta la versión del socket según tu sistema):

```
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
}
```

Asegura también:
- `root` apuntando al directorio del proyecto
- `index index.php index.html index.htm;`
- En producción, activa HTTPS y cabeceras de seguridad

## Base de datos
Crea la base de datos y usuario con permisos mínimos. La app espera tablas para usuarios y los módulos usados en dashboard/funciones. Asegúrate de configurar `DB_NAME`, `DB_USER` y `DB_PASS` correctos.

## Flujo básico
1. Registro en `auth/register.php`
2. Inicio de sesión en `auth/login.php`
3. Acceso al panel en `dashboard/dashboard.php`

## Estado de funcionalidades
- Plantillas (`plantillas/`): en desarrollo (WIP)
- Resto de páginas: listo para evaluación/uso

## Seguridad
- CSRF: se incluyen tokens en formularios y se validan en endpoints
- Sesiones: cookies seguras y regeneración de ID tras login/logout
- Entrada/Salida: sanitización de inputs y escape de salidas

## Contribución y soporte
- Incidencias: abrir un issue o PR en el repositorio
- Para consultas generales, utiliza el correo del proyecto configurado en variables de entorno o los canales del repositorio

## Licencia
Este proyecto no puede ser copiado ni reutilizado sin permiso explícito del autor.