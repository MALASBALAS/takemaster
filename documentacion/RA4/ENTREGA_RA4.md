# ENTREGA RA4 - DISEÑO DE INTERFACES CON USABILIDAD
## Proyecto TAKEMASTER

**Equipo:** Álvaro Balas  y Miguel Ángel Prieto  
**Asignatura:** Diseño de Interfaces Gráficas  

---

## OBJETIVO RA4

Evaluar la **usabilidad e implementación de criterios de diseño** en la interfaz gráfica del proyecto TAKEMASTER, siguiendo estándares ISO 9241, WCAG 2.1 y principios de Nielsen.

---

## CRITERIOS DE USABILIDAD

### **1. Menús y Navegación Estándar**

**Descripción:** El proyecto implementa menús accesibles y estándares de navegación, tanto en escritorio como en dispositivos móviles (diseño responsive).

- IMPLEMENTADO:
- Navbar fija en la parte superior (ubicación estándar)
- Hamburger menu en móvil (<768px de pantalla)
- Menú expandible con links claros
- Navegación por teclado funcional

**Evidencia:**
```
- src/nav/topnav.php: navbar con logo, menú principal, hamburger
- Iconos estándar (≡ para hamburguesa)
- Links con href correctos y descriptivos
- aria-expanded="true/false" para estado del menú
```

---

### **2. Distribución Lógica de Elementos**

**Descripción:** Elementos ubicados donde el usuario los espera.

- IMPLEMENTADO:
- Logo/marca en esquina superior izquierda
- Barra de búsqueda/funciones principales arriba
- Contenido principal en el centro
- Footer con información adicional abajo
- Botones de acción en lugar visible

**Evidencia:**
```
- Estructura consistente en todas las páginas
- Breadcrumb/ruta clara (en algunos lugares)
- Formularios con labels claros
- Botones de acción al final del formulario
- Confirmaciones antes de acciones críticas
```

---

### **3. Controles e Inputs Estándar**

**Descripción:** Controles que el usuario reconoce inmediatamente.

- IMPLEMENTADO:
- Botones con texto descriptivo (no solo iconos)
- Inputs con labels asociados (<label for="...">)
- Checkboxes y radio buttons estándar
- Select dropdowns para selecciones múltiples
- Confirmaciones con OK/Cancelar

**Evidencia:**
```
- <input type="text" name="email"> con label
- <input type="password"> para contraseñas
- <button type="submit">Guardar</button>
- Estilos consistentes en todos los inputs
- Placeholders descriptivos
```

---

### **4. Mensajes y Retroalimentación del Sistema**

**Descripción:** El sistema comunica claramente el estado y resultado de acciones.

- IMPLEMENTADO:
- Mensajes de error claros y específicos
- Confirmaciones de éxito (ej: "Datos guardados")
- Indicadores de estado (cargando, completado)
- Logs de debug en consola (F12)
- Diálogos modales para confirmaciones

**Evidencia:**
```
- Validación de campos con mensajes específicos
- Cookie dialog informando sobre cookies
- Console logs: "Menu toggle button found!", "Menu is now: OPEN"
- Confirmación: confirm("¿Estás seguro?")
- Alert dialogs en acciones críticas
```

---

### **5. Tipos de Texto Identificables**

**Descripción:** El usuario distingue fácilmente diferente información.

- IMPLEMENTADO:
- Títulos (h1, h2, h3) diferenciados por tamaño
- Texto normal en párrafos (p)
- Enlaces en color diferenciado (azul #007BFF)
- Destacados en negrita o colores
- Etiquetas/badges diferenciadas

**Evidencia:**
```
- h1 { font-size: 2rem; font-weight: bold; }
- h2 { font-size: 1.5rem; }
- a { color: var(--color-primary); text-decoration: underline; }
- strong, b { font-weight: bold; }
- .badge { background-color: var(--color-primary); color: white; }
- Jerarquía visual clara
```

---

### **6. Legibilidad del Texto**

**Descripción:** El texto es fácil de leer (contraste, tamaño, espaciado).

- IMPLEMENTADO:
- Contraste adecuado (texto oscuro sobre fondo claro)
- Tamaño de fuente legible (16px+)
- Line-height adecuado (1.5+)
- Espaciado entre párrafos
- Colores consistentes

**Evidencia:**
```
- Contraste WCAG AA: #333333 (texto) sobre #ffffff (fondo) = 12.6:1
- Font-size: 16px en desktop, 18px en móvil
- Line-height: 1.5 en párrafos
- Margin-bottom: 1rem entre párrafos
- Sin justificación (texto alineado a la izquierda)
- Sin demasiadas líneas de texto ancho (max-width: 600px)
```

---

### **7. Prevención de Errores**

**Descripción:** El sistema previene o facilita la recuperación de errores.

- IMPLEMENTADO:
- Validación de campos requeridos
- Formato de email validado
- Confirmaciones antes de eliminar
- Contraseña con requisitos claros
- Deshacer/volver (breadcrumb, botón atrás)

**Evidencia:**
```
- <input required> en campos obligatorios
- Email validado con filter_var(..., FILTER_VALIDATE_EMAIL)
- Confirmación: if (!confirm('¿Deseas eliminar?')) return;
- Botón "Volver" en formularios
- Mensajes de error antes de procesar
- Revalidación en servidor (no solo cliente)
```

---

## CONCLUSIÓN

Nuestro proyecto **TAKEMASTER** demuestra excelente usabilidad e implementación de criterios de diseño (RA4) con:

- Menús y navegación estándares
- Distribución lógica de elementos
- Controles e inputs reconocibles
- Mensajes claros y retroalimentación
- Texto legible y bien estructurado
- Prevención de errores efectiva
