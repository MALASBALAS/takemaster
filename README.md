# TAKEMASTER - GRUPO 5 ALVARO BALAS Y MIGUEL ANGEL PRIETO 

Aplicación web en PHP para gestión y visualización de información, con frontend HTML/CSS/JS y backend PHP (mysqli) sobre MariaDB.

## Estado actual del proyecto

**ESTADO: FASE DE TESTING CON USUARIOS REALES**

- **Desarrollo completado:** Todas las funcionalidades principales implementadas y validadas
- **Documentación finalizada:** Entrega RA1 y RA4 documentadas completamente
- **Testing en curso:** Recopilación de feedback de usuarios reales
- **Mejoras pendientes:** Análisis de feedback para optimizaciones

**Próximos pasos:**
1. Pruebas extensivas con usuarios reales (actores, direcrtores)
2. Recopilación de feedback sobre usabilidad y funcionalidades
3. Implementación de mejoras basadas en feedback
4. Optimizaciones de rendimiento si es necesario
5. Seguridad y escalabilidad final


## Características principales
- Autenticación con contraseñas hasheadas (password_hash/password_verify)
- Sesiones seguras (cookies HttpOnly, SameSite, regeneración de ID)
- Protección CSRF en formularios y peticiones JSON
- Consultas preparadas (mysqli) y UTF-8 (utf8mb4)
- Panel de control (dashboard) y páginas públicas en `pags/`
- Estructura modular con funciones en `funciones/` y utilidades en `src/nav/`
- **Responsive Design:** interfaz optimizada para móvil, tablet y escritorio
  - Meta viewport en todas las páginas para escalado correcto
  - Breakpoints: 480px (móvil), 768px (tablet), 1024px+ (desktop)
  - Menú hamburguesa en móviles con JavaScript robusto
  - Tamaños táctiles mínimos (44x44px) para accesibilidad

## Requisitos
- PHP 8.1+ (recomendado 8.3) con extensiones: mysqli, openssl, mbstring, json
- MariaDB/MySQL
- Servidor web (Nginx o Apache)

## Estructura del proyecto (resumen)
- `index.php` – página de entrada (con diálogo de cookies persistente)
- `auth/` – login, registro y logout (responsive)
- `dashboard/` – vistas autenticadas, plantillas y endpoints de guardado (responsive, container 95% centrado)
- `funciones/` – endpoints utilitarios (datos, tarifas, etc.)
- `pags/` – páginas públicas (about, terms, privacy, contact, etc.) (responsive)
- `plantillas/` – sistema de plantillas (WIP, responsive)
- `src/` – assets (css/js/img) y utilidades (`src/nav/*`)
  - `css/styles.css` – estilos globales con media queries y escalado responsivo
  - `css/style-table.css` – estilos específicos para tablas
  - `css/style-form.css` – estilos para formularios
  - `nav/topnav.php` – barra de navegación fija con menú hamburguesa móvil
  - `nav/footer.php` – pie de página reutilizable

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

## Documentación de Entrega (Asignatura: Diseño de Interfaces Gráficas)

Para información completa sobre la entrega del proyecto, consultar:

- **[RESUMEN_EJECUTIVO.md](./RESUMEN_EJECUTIVO.md)** – Resumen en 30 segundos del proyecto y estado de entrega
- **[ENTREGA_PROYECTO.md](./ENTREGA_PROYECTO.md)** – Objetivo, público destino, valor aportado, estructura, criterios RA1
- **[AUTOEVALUACION_RUBRICA.md](./AUTOEVALUACION_RUBRICA.md)** – Rúbrica auto-evaluada (5/5 EXCELENTE), justificaciones por criterio
- **[HOJA_REPARTO_TRABAJO.md](./HOJA_REPARTO_TRABAJO.md)** – Distribución de tareas, horas, porcentaje de participación
- **[GUIA_VIDEO_PRESENTACION.md](./GUIA_VIDEO_PRESENTACION.md)** – Guión del vídeo, estructura, puntos clave, checklist pre-grabación
- **[CHECKLIST_ENTREGA.md](./CHECKLIST_ENTREGA.md)** – Verificación de ficheros, instrucciones de entrega, último checklist

**Equipo:** Álvaro Balas y Miguel Ángel Prieto  
**Asignatura:** Diseño de Interfaces Gráficas (RA1)  
**Profesor:** Mª Isabel López  
**Fecha de Entrega:** 9 de noviembre de 2025  

## Estado de funcionalidades
- **Responsive Design:** Completado (todos los breakpoints y menú móvil funcional)
- **Diálogo de Cookies:** Completado (con persistencia en localStorage)
- **Navbar:** Funcional (menú hamburguesa en móviles, enlaces centrados, color consistente)
- Plantillas (`plantillas/`): en desarrollo (WIP)
- Resto de páginas: listo para evaluación/uso

## Responsive Design
Todas las páginas incluyen `<meta name="viewport" content="width=device-width, initial-scale=1">` para escalado correcto en móviles.

### Estilos por breakpoint (en `src/css/styles.css`):
- **Escritorio (≥1025px):** `html { font-size: 16px }`
- **Tablet (768-1024px):** `html { font-size: 18px }`
- **Móvil (<768px):** `html { font-size: 18px }`, menú hamburguesa visible
- **Móvil pequeño (<480px):** estilos optimizados para pantallas pequeñas

### Características móviles:
- Botón hamburguesa (☰) con tamaño táctil 44x44px
- Menú desplegable que aparece debajo del navbar
- Tablas con scroll horizontal en móviles
- Botones y inputs con padding aumentado
- Formularios responsivos

### Testing:
Abre las DevTools (F12), activa modo responsive y prueba:
- Ancho 375px (iPhone SE)
- Ancho 768px (iPad)
- Ancho 1920px (desktop)

El menú hamburguesa generará logs en consola (F12 → Console) para debug:
- `"Menu toggle button found and wired!"` → botón detectado e inicializado
- `"Toggle clicked!"` → botón presionado
- `"Menu is now: OPEN"` o `"Menu is now: CLOSED"` → estado del menú


- CSRF: se incluyen tokens en formularios y se validan en endpoints
- Sesiones: cookies seguras y regeneración de ID tras login/logout
- Entrada/Salida: sanitización de inputs y escape de salidas

## Contribución y soporte
- Incidencias: abrir un issue o PR en el repositorio
- Para consultas generales, utiliza el correo del proyecto configurado en variables de entorno o los canales del repositorio

## Licencia
Este proyecto no puede ser copiado ni reutilizado sin permiso explícito del autor.