# Roadmap de Mejora – TAKEMASTER (3 Semanas)

## Objetivo General
Evolucionar TAKEMASTER hacia una aplicación web robusta, modular y presentable académicamente: base de datos estructurada, API REST, seguridad aplicada, interfaz accesible, estadísticas, exportaciones y documentación completa.

---

## Prioridades Globales (Resumen)
### Frontend (orden de prioridad)
1. Definir y aplicar nueva paleta de colores (variables CSS + contraste AA)
2. Organizar tamaños y jerarquía visual (tipografía, spacing, grid principal)
3. Refactor de componentes base (botones, formularios, tablas)
4. Implementar responsive consistente (mobile-first)
5. Accesibilidad básica (focus, roles ARIA, labels)
6. Dashboard: tabla jornadas + estados vacíos
7. Estadísticas con gráficas (Chart.js)
8. CRUD plantillas (modales + formularios)
9. Compartir plantillas (modal + enlace público)
10. Filtros avanzados y barra de búsqueda
11. Exportaciones (UI: CSV/JSON)
12. Dark mode (CSS vars + persistencia)
13. Página Ayuda / Manual + capturas
14. Pulido final (microinteracciones, print style, refinado iconos)

### Backend (orden de prioridad)
1. Esquema base de datos definitivo + `database.sql`
2. Modelos y servicios (Users, Jornadas, Takes, Plantillas, Stats)
3. Autenticación y sesiones seguras (login + middleware)
4. API mínima (login, jornadas, takes) + validaciones
5. Auditoría básica (`audit_log`) + sesiones_login
6. Roles y panel admin (endpoints)
7. Estadísticas (resumen + series) + optimización consultas
8. CRUD plantillas + instanciación
9. Compartir plantillas (hash público + expiración)
10. Rate limiting login + mejora seguridad
11. Exportaciones (CSV/JSON)
12. Filtros avanzados (fechas, estudio, búsqueda)
13. Autenticación avanzada (tokens/JWT)
14. Caché ligera opcional (stats)
15. Hardening final (CSP, límites tamaño, limpieza warnings)

---

## Semana 1 – Fundamentos y Estructura
**Meta global:** Definir la base de datos, modularizar backend, crear API mínima y unificar interfaz inicial.

### Backend
- Diseñar esquema relacional (`usuarios`, `jornadas`, `takes`, `tarifas`, `plantillas`, `compartidos`, `sesiones_login`, `audit_log`).
- Crear `database.sql` (DDL + datos ejemplo mínimos).
- Estructura `app/` (Models, Services, Controllers o endpoints agrupados).
- Refactor de lógica repetida (migrar a modelos/servicios).
- Implementar API mínima:
  - POST `/api/login`
  - GET/POST `/api/jornadas`
  - GET/POST `/api/jornadas/{id}/takes`
- Middleware autenticación (sesión + posible token simple temporal).
- Tabla y registro inicial de auditoría (`audit_log`).
- Función de validación genérica (`validate($data, $rules)`).

### Frontend
- UN PASO 1: Nueva paleta (variables: --color-primary, --color-bg, --color-surface, --color-accent, --color-text, etc.).
- UN PASO 2: Escalas tipográficas (ej: 14/16/20/24/32) y sistema de spacing (4–8–16–24...).
- Unificar layout (header, footer, navegación reutilizable).
- Normalizar estilos base (botones, formularios, tablas iniciales).
- Implementar tabla de jornadas (estructura inicial) + estados vacíos.
- Accesibilidad: labels, foco visible, roles ARIA básicos.
- Documentar guía visual inicial (`style-guide.md`).

### Entregables Semana 1
- `database.sql`
- Carpeta `app/` operativa
- API mínima funcional
- Tabla jornadas en dashboard
- Borrador `API.md`
- Guía de estilos inicial (paleta + tipografía + spacing)

---

## Semana 2 – Funcionalidad Avanzada y Valor Añadido
**Meta global:** Añadir estadísticas, plantillas, panel de administración, seguridad adicional y primeras pruebas.

### Backend
- Roles (user/admin) y control de acceso.
- Panel admin (endpoints): listar usuarios, últimos accesos (`sesiones_login`).
- Endpoints estadísticas:
  - `/api/stats/resumen` (horas mes, takes mes, media duración)
  - `/api/stats/series?type=horas|takes`
- CRUD plantillas:
  - `/api/plantillas` (GET/POST/PUT/DELETE)
  - POST `/api/plantillas/{id}/instanciar`
- Compartir plantillas:
  - Tabla `compartidos` (hash + expiración opcional)
  - GET `/api/plantillas/public/{hash}`
- Rate limiting login (IP + usuario).
- Registro ampliado en `audit_log` (crear/editar/eliminar jornada, login, compartir plantilla).
- Tests iniciales (mínimo: auth, crear jornada, stats básicas).

### Frontend
- Página Admin: tabla usuarios + badges rol + acciones (reset placeholder).
- Página Estadísticas: contenedores + integración Chart.js + loaders.
- Vista Plantillas: listado + formulario crear/editar.
- Modal Compartir plantilla (muestra URL pública copiable).
- Responsive tablas: scroll horizontal o layout alternativo.
- Validación visual de formularios (errores inline coherentes).
- Microinteracciones (transiciones al abrir/cerrar modales).

### Entregables Semana 2
- Panel admin (UI + endpoints)
- Estadísticas con gráficas dinámicas
- CRUD y compartir plantillas
- Rate limiting y auditoría
- Tests básicos ejecutables
- API actualizada (`API.md`)

---

## Semana 3 – Pulido, Exportaciones y Documentación
**Meta global:** Exportaciones, filtros avanzados, autenticación tokens/JWT, dark mode, documentación y demo final.

### Backend
- Autenticación avanzada (JWT o tokens persistidos en `api_tokens`).
- Exportaciones: `/api/export/jornadas?format=csv|json`.
- Filtros avanzados en `/api/jornadas` (`desde`, `hasta`, `estudio`, `q`).
- Optimización DB (índices en campos fecha/estudio si necesario).
- Endpoints refinados de estadísticas (caché ligera opcional).
- Endurecer seguridad:
  - CSP básica (whitelist local + CDN necesario)
  - Límites de tamaño y longitud entradas
  - Revisión y limpieza de logs/warnings
- Documentación técnica final:
  - `API.md` completo
  - `SECURITY.md`
  - `CHANGELOG.md`
  - `INSTALL.md`
  - Diagrama arquitectura (`/docs/`)
- Script de demo (pasos reproducibles) + usuario demo read-only.

### Frontend
- Botones exportar (CSV / JSON) + feedback (spinner / toast).
- Filtros avanzados: barra búsqueda + filtros por rango de fechas / estudio.
- Dark mode (CSS variables + persistencia localStorage).
- Estados vacíos mejorados (mensajes guiados / CTA).
- Página de Ayuda / Manual con capturas.
- Ajustes finales de accesibilidad (contraste, orden tab, aria-live en mensajes).
- Pulido visual final (espaciado, jerarquía tipográfica, iconos definitivos).
- Integrar modo impresión (estilos print para jornada / reporte mensual).

### Entregables Semana 3
- Exportaciones funcionales
- Filtros avanzados en UI + API
- Dark mode estable
- Documentación completa (técnica + usuario)
- Demo lista (capturas/GIFs en `/docs/`)
- Hardening aplicado (CSP, límites, sin warnings)

---

## Checklist Final
### Backend
- [ ] CRUD completo (jornadas, takes, plantillas)
- [ ] Roles + admin
- [ ] Stats + series
- [ ] Compartir plantillas
- [ ] Export CSV/JSON
- [ ] Autenticación avanzada (tokens/JWT)
- [ ] Auditoría + rate limiting

### Frontend
- [ ] Responsive completo
- [ ] Dashboard con tabla y filtros
- [ ] Estadísticas (gráficas dinámicas)
- [ ] CRUD plantillas intuitivo
- [ ] Dark mode
- [ ] Modales y feedback consistente
- [ ] Página ayuda / documentación visual

### Seguridad
- [ ] CSRF activo
- [ ] Password hashing
- [ ] Headers seguridad + CSP
- [ ] Sin secretos en repo
- [ ] Validaciones robustas

### Documentación
- [ ] README actualizado
- [ ] API.md completo
- [ ] SECURITY.md
- [ ] INSTALL.md
- [ ] CHANGELOG.md
- [ ] Diagrama arquitectura
- [ ] Script demo

### Extras Opcionales
- Notificaciones (poll / SSE)
- Generar PDF resumen mensual
- Cache ligera de stats
- Docker Compose (nginx + php-fpm + mariadb)

---
## Flujo de Demo Sugerido
1. Registro y login.
2. Crear jornada y añadir takes.
3. Ver estadísticas y gráficas.
4. Crear plantilla y usarla para una nueva jornada.
5. Compartir una plantilla (enlace público).
6. Exportar jornadas (CSV).
7. Activar dark mode.
8. Mostrar documentación API y medidas de seguridad.

---

Siguiente paso sugerido: crear `database.sql` y plantilla `API.md`.