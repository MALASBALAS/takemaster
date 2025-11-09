# ENTREGA RA1 - DISEÑO DE INTERFACES GRÁFICAS
## Proyecto TAKEMASTER

**Equipo:** Álvaro Balas y Miguel Ángel Prieto  
**Asignatura:** Diseño de Interfaces Gráficas  

---

## OBJETIVO DEL PROYECTO

TAKEMASTER es una **aplicación web de gestión de datos para profesionales del audiovisual**, diseñada para facilitar el cálculo de ingresos, gastos y tarifas en jornadas de trabajo.

### Propósito Principal
- Registrar datos de jornadas de trabajo (estudios, tipos de trabajo, CGS)
- Calcular automáticamente ingresos y gastos
- Generar plantillas reutilizables
- Acceder desde cualquier dispositivo (móvil, tablet, escritorio)

---

## PÚBLICO DESTINO

**Usuarios Primarios:**
- Actores de cine y televisión (profesionales independientes)
- Técnicos audiovisuales (fotógrafos, operadores)
- Freelancers en el sector (directores, editores)

**Características del Público:**
- Necesitan rapidez en cálculos de tarifas
- Requieren acceso remoto desde diferentes ubicaciones
- Buscan herramientas simples sin complejidad técnica
- Valoran la disponibilidad móvil (uso sobre la marcha)

---

## VALOR APORTADO

### 1. **Eficiencia Operativa**
- Automatización de cálculos complejos
- Eliminación de cálculos manuales propensos a errores
- Ahorro de tiempo en gestión de jornadas

### 2. **Accesibilidad y Movilidad**
- Interfaz responsive (móvil, tablet, desktop)
- Acceso desde cualquier dispositivo
- Menú hamburguesa optimizado para pantallas pequeñas

### 3. **Seguridad de Datos**
- Autenticación con contraseñas hasheadas
- Sesiones seguras (cookies HttpOnly)
- Protección CSRF en formularios
- Consultas preparadas (prevención SQL injection)

### 4. **Experiencia de Usuario Profesional**
- Diseño coherente y moderno
- Navegación intuitiva
- Feedback inmediato (diálogos, validaciones)
- Cookie consent con persistencia

### 5. **Herramienta de Productividad**
- Generación de plantillas reutilizables
- Exportación de datos
- Panel de control personalizado

---

## AUTOEVALUACIÓN RÚBRICA RA1

### **CRITERIO A: Creación de Interfaz Visual (1.5 puntos)**

| Indicador | Estado |
|-----------|--------|
| Uso de editor visual (VS Code) | SI - Avanzado |
| Layouts y contenedores | SI - Flexbox, Grid, media queries |
| Componentes coherentes | SI - Reutilizables en todas las páginas |

**Justificación:**
- Utilizamos VS Code con extensiones para diseño responsive
- HTML semántico con layouts flexbox y contenedores centrados
- Variables CSS para mantener coherencia visual
- Componentes reutilizables (.action-btn, navbar, footer)

**Evidencia:**
```
- src/css/styles.css (897 líneas, layouts, variables)
- src/nav/topnav.php (navbar semántica)
- Componentes consistentes en 10+ páginas
```

---

### **CRITERIO B: Ubicación y Configuración de Componentes (1 punto)**

| Indicador | Estado |
|-----------|--------|
| Alineación de componentes | SI - Correcta |
| Propiedades bien configuradas | SI - ID, class, ARIA completos |
| Responsive en breakpoints | SI - 375px, 768px, 1920px |

**Justificación:**
- 4 breakpoints testeados (1024px, 768px, 480px, mobile-first)
- Flexbox layout con justify-content, align-items, gap
- Hamburger button: 44x44px, aria-expanded, aria-controls
- Tamaños táctiles accesibles en móvil

**Evidencia:**
```
- Media queries: @media (max-width: 1024px), (768px), (480px)
- Componentes: button.menu-toggle (44x44px)
- ARIA: aria-expanded, aria-controls, role="dialog"
- DevTools responsive: testado en 375px, 768px, 1920px
```

---

### **CRITERIO C: Personalización de Propiedades y Estilos (1 punto)**

| Indicador | Estado |
|-----------|--------|
| Paleta de colores profesional | SI - Variables CSS |
| Tipografía legible | SI - System fonts escalados |
| Estilos CSS externos | SI - Sin estilos inline |
| Coherencia visual | SI - Completa |

**Justificación:**
- Paleta profesional: --color-primary (#007BFF), --color-panel (#f5f5f5)
- Colores secundarios para validaciones (verde/rojo)
- Tipografía: system fonts, tamaños escalados (16px→18px)
- CSS externo en styles.css, style-form.css, style-table.css
- Espaciados uniformes: gap: 15px, padding: 12px

**Evidencia:**
```
- Variables CSS: :root { --color-primary, --color-panel, --color-border, ... }
- Tipografía: body { font-family: system-ui; font-size: 1rem; }
- Sin estilos inline: todo en styles.css (900 líneas)
- Componentes reutilizables: .action-btn, .form-group, .card, .alert
```

---

### **CRITERIO D: Asociación de Eventos y Acciones (1.5 puntos)**

| Indicador | Estado |
|-----------|--------|
| Eventos JavaScript correctos | SI - IIFE, addEventListener |
| Funcionalidad de botones | SI - Con feedback |
| Formularios con validación | SI - CSRF, requeridos, email |
| Logs de debug | SI - Console logs visibles |

**Justificación:**
- Hamburger menu: IIFE pattern, classList.toggle, console.log
- Cookie consent: localStorage, DOMContentLoaded
- Validación: CSRF tokens, campos requeridos, email filter
- Confirmaciones: diálogos antes de eliminar
- Debug: "Menu toggle button found!", "Toggle clicked!", "Menu is now: OPEN/CLOSED"

**Evidencia:**
```
- src/nav/topnav.php: IIFE, addEventListener, classList.toggle, console.log
- index.php: localStorage, DOMContentLoaded, cookie dialog
- auth/login.php: validación CSRF, email filter, mensajes error
- DevTools Console: logs visibles para debugging
```

---

## ESTRUCTURA DEL PROYECTO

```
takemaster/
├── index.php                    # Página principal + diálogo cookies
├── auth/                        # Autenticación
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── dashboard/                   # Panel de control
├── pags/                        # Páginas públicas (about, contact, etc.)
├── funciones/                   # Endpoints (obtener_datos, tarifas)
├── src/
│   ├── css/
│   │   ├── styles.css          # Principal (897 líneas, responsive)
│   │   ├── style-form.css
│   │   └── style-table.css
│   ├── js/                      # Componentes JavaScript
│   ├── nav/                     # Navbar, bootstrap, DB
│   └── img/                     # Logos, iconos, backgrounds
├── documentacion/               # Esta carpeta
└── README.md, CHANGELOG.md
```

---

## SEGURIDAD E ACCESIBILIDAD

- CSRF Protection: Tokens validados en formularios
- Sesiones Seguras: HttpOnly, SameSite, regeneración de ID
- Contraseñas: Hasheadas con password_hash/verify
- SQL Injection: Consultas preparadas con mysqli
- ARIA Labels: aria-expanded, aria-controls, role attributes
- Tamaños Táctiles: 44x44px mínimo en móvil
- Feedback Inmediato: Diálogos, validaciones, logs de debug

---

## MIEMBROS DEL EQUIPO

| Nombre | Especialidad |
|--------|--------------|
| **Álvaro Balas ** | Backend, eventos, testing |
| **Miguel Ángel Prieto** | CSS, diseño, UX |

**Hoja de reparto:** Ver `documentacion/ADMINISTRATIVO/Hoja_Reparto.md`

---

## NOTAS FINALES

Este proyecto demuestra **competencia completa en RA1 (Diseño de Interfaces Gráficas)**:

- Interfaz visual profesional (VS Code, layouts)
- Ubicación correcta de componentes (responsive, flexible)
- Personalización avanzada (colores, tipografía)
- Eventos funcionales (JavaScript, validación, feedback)
- Usabilidad mejorada (móvil, accesibilidad, seguridad)

---

**Estado:** Listo para evaluación
**Calificación Esperada:** 5/5 - EXCELENTE
**Fecha:** 9 de noviembre de 2025  
