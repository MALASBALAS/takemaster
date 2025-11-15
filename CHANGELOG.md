# CHANGELOG

## 2025-11-13 (Session 27f - Security & Permissions)
- **Sistema de Compartir con Roles (Share Function) - PRODUCCIÓN:**
  - ✅ Corregido bug persistencia de rol: roles ahora se guardan correctamente en `plantillas_compartidas` (compartir_plantilla.php)
  - ✅ Implementados 4 roles: lector (lectura), editor (editar), admin (editar+compartir), propietario (total)
  - ✅ Validación de roles en obtención de compartidos (obtener_compartidos.php)
  - ✅ Interfaz de selección de roles en frontend (share-modal.js)

- **Seguridad contra Manipulación via DevTools - 4 Capas:**
  - **Capa 1:** HTML `disabled` attributes + CSS opacity + JavaScript DOM disabling
  - **Capa 2:** Hidden role tokens (`user_role_token`, `can_edit_token`) validados antes de AJAX
  - **Capa 3:** Backend `require_plantilla_edit_access()` valida en database (funciones/validate_plantilla_access.php)
  - **Capa 4:** Logging de intentos de seguridad en error_log del servidor
  - ✅ Lector users completamente bloqueados de guardar, incluso removiendo HTML disabled con DevTools

- **Validación Backend de Permisos - CRÍTICO:**
  - ✅ Creada función centralizada `validate_plantilla_access()` (funciones/validate_plantilla_access.php)
  - ✅ Corregido bug HTTP 500 en usuarios ADMIN en plantillas compartidas
  - ✅ Modificada `actualizar_plantilla_segura()` para validar ambos: propiedad AND roles en plantillas_compartidas
  - ✅ ADMIN/EDITOR ahora pueden guardar cambios en plantillas compartidas
  - ✅ Rechazadas modificaciones de LECTOR users por backend (no solo frontend)

- **Dashboard Refactorizado - RECUPERADO:**
  - ✅ Limpieza de archivo dashboard.php (160 líneas limpias, sin corrupción)
  - ✅ Creadas funciones helper en funciones/dashboard_helpers.php
  - ✅ Dashboard muestra plantillas propias y plantillas compartidas conmigo
  - ✅ Indicadores de rol en plantillas compartidas (Lector/Editor/Admin)

- **Campos Dinámicos de Formulario:**
  - ✅ CGs: Requerido (obligatorio)
  - ✅ Takes: Ahora opcional (sin `required` attribute)
  - ✅ Cálculos automáticos manejan valores nulos correctamente

- **Validación de Datos Encriptados:**
  - ✅ Encriptación de contenido de plantillas en BD (AES-256-GCM)
  - ✅ Desencriptación automática en lectura
  - ✅ Claves de encriptación rotadas con ENCRYPTION_KEY de .env

## 2025-11-09
- **Responsive Design Completo:** Implementación integral de diseño responsivo en todas las páginas del proyecto.
  - Añadido meta viewport `content="width=device-width, initial-scale=1"` a todas las páginas (10+ ficheros): `index.php`, `auth/login.php`, `auth/register.php`, `pags/micuenta.php`, `pags/miplantilla.php`, `pags/about.php`, `pags/contact.php`, `pags/privacy.php`, `pags/terms.php`, `dashboard/dashboard.php`, `dashboard/configuracion.php`
  - Consolidación de media queries en `src/css/styles.css`: eliminadas duplicaciones y establecida jerarquía limpia (1024px → 768px → 480px)
  - Escalado responsivo de tipografía: `html { font-size: 16px }` desktop → 18px en tablet/móvil mediante media queries
  - Creada clase `.action-btn` reutilizable para botones de acción con estilos consistentes

- **Hamburger Menu Móvil:** Menú desplegable funcional en dispositivos <768px con toggleado robusto.
  - Implementado menú hamburguesa (☰) con tamaño táctil mínimo 44x44px para accesibilidad
  - Refactorizado JavaScript en `src/nav/topnav.php` usando patrón IIFE con modo estricto
  - Añadidos logs de debug en consola para verificación: "Menu toggle button found and wired!", "Toggle clicked!", "Menu is now: OPEN/CLOSED"
  - Implementado sistema de clase `.active` con toggleado suave (`classList.toggle`)
  - Atributos ARIA (`aria-expanded`) para mejor accesibilidad

- **Navbar Color Consistency:** Corrección de inconsistencia donde navbar aparecía negra en algunas páginas.
  - Cambio de background desde `var(--color-panel)` a hardcoded `#f5f5f5` (gris claro) en `.topnav`, `.dropdown .dropbtn`, `.dropdown-content`, `.menu-links`
  - Aplicado en todas las páginas para garantizar coherencia visual

- **Cookie Consent Dialog:** Persistencia de consentimiento de cookies en localStorage.
  - Refactorizado JavaScript en `index.php` con manejo correcto de evento `DOMContentLoaded`
  - Implementado `localStorage` para persistencia entre sesiones
  - Botones aceptar/cerrar funcionales: guardan consentimiento y ocultan diálogo

- **Documentación Actualizada:**
  - README.md ampliado con sección "Responsive Design" incluyendo breakpoints, características móviles, instrucciones de testing y debugging
  - Especificación de breakpoints: 480px (móvil), 768px (tablet), 1024px (desktop)
  - Logs de debug documentados para verificación en DevTools

- **Mejoras CSS:**
  - Centro de `.menu-links` con `justify-content: center` y `flex: 1`
  - Estilos mejorados para mobile: botones expandidos, padding aumentado, inputs y formularios responsivos
  - Tablas con scroll horizontal en móviles

- **Testing y Validación:**
  - Todas las páginas verificadas para viewport meta y escalado correcto
  - Hamburger menu testeado con DevTools (modo responsive)
  - Cookie persistence validada en localStorage

## 2025-10-23
- Actualizado el CSS principal por Miguel Ángel Prieto: nueva paleta de colores, mejora de contraste y coherencia visual en todos los componentes.
- Refactorizados tamaños y jerarquía tipográfica para mayor legibilidad y estructura.
- Añadido modo mock para frontend: permite trabajar el diseño sin depender del backend ni la base de datos.
- Mejorada la estructura de carpetas y modularización de estilos.
- README y ROADMAP actualizados para reflejar nuevas prioridades y tareas.
- Preparado `database.sql` con estructura mínima y datos de ejemplo para pruebas.
- Añadido `.env.example` y `.gitignore` para proteger credenciales y facilitar despliegue seguro.

## 2025-10-16
- Se continuó el desarrollo de todas las áreas del proyecto según lo planificado en el roadmap.
- Mejoras en el backend del servidor: optimización de consultas y refactorización de controladores para mayor rendimiento.
- Implementación de nuevas rutas y endpoints en la API para gestión de plantillas y estadísticas.
- Pruebas de integración entre frontend y backend usando datos simulados y reales.
- Documentación interna ampliada para facilitar el trabajo en equipo.
- Se revisaron logs del servidor y se corrigieron pequeños bugs detectados en producción.

## 2025-09-22
- Hardening de seguridad en PHP: CSRF, sesiones seguras, hashing de contraseñas, rate limiting en login.
- Configuración de Nginx y PHP-FPM 8.3 adaptada para producción.
- Refactor de endpoints y modularización de funciones.
- Documentación inicial y estructura de carpetas base.
