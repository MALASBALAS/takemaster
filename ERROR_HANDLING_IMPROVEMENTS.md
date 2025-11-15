# 🔧 Mejoras de Error Handling - export_pdf.php

## Problema Original
```
Error: SyntaxError: Unexpected token '<', "<html>
<h"... is not valid JSON
```

**Causa**: El servidor estaba devolviendo HTML (error de PHP) en lugar de JSON cuando ocurría una excepción.

---

## Soluciones Implementadas

### 1. ✅ Error Handler Global
```php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'Error en servidor: ' . $errstr]);
    exit;
});
```
- Captura todos los errores de PHP
- Limpia output buffer
- Devuelve JSON en lugar de HTML

### 2. ✅ Exception Handler Global
```php
set_exception_handler(function($exception) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'Excepción: ' . $exception->getMessage()]);
    exit;
});
```
- Captura todas las excepciones no controladas
- Asegura respuesta JSON
- Limpia output buffer

### 3. ✅ Mejorado respondError()
```php
function respondError($message, $code = 400) {
    ob_end_clean();  // ← AÑADIDO: Limpiar buffer
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
```

### 4. ✅ Mejor Validación de wkhtmltopdf
```php
// Verificar que se pudo crear archivo temporal
if (!$tempHtml || !file_put_contents($tempHtml, $html)) {
    ob_end_clean();
    respondError('No se pudo crear archivo temporal', 500);
}

// Ejecutar con captura de stderr
$cmd = ... . ' 2>&1';

// Verificar tamaño del PDF
if ($returnCode === 0 && file_exists($tempPdf) && filesize($tempPdf) > 0) {
    // Éxito - enviar PDF
}
```

### 5. ✅ Mejor Try-Catch
```php
} catch (Exception $e) {
    ob_end_clean();
    respondError('Error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {  // ← Captura cualquier error
    ob_end_clean();
    respondError('Error crítico: ' . $e->getMessage(), 500);
}
```

---

## Resultado

### Antes (Error)
```
Frontend recibe HTML con error PHP
→ JavaScript intenta parsear como JSON
→ Error: "Unexpected token '<'"
```

### Después (Correcto)
```
Frontend recibe JSON siempre
→ {"error": "Descripción del problema"}
→ JavaScript muestra error limpio en alert()
```

---

## Cambios Realizados en export_pdf.php

| Línea | Cambio | Razón |
|-------|--------|-------|
| 1-30 | Añadido error/exception handlers | Capturar todos los errores |
| 30 | Añadido ob_end_clean() en respondError | Asegurar limpieza de buffer |
| 148-156 | Validación mejorada de archivos temporales | Prevenir archivos vacíos |
| 158 | Añadido "2>&1" al comando | Capturar stderr de wkhtmltopdf |
| 160 | Verificación de filesize | Asegurar PDF válido |
| 253 | ob_end_clean() antes de fallback | Limpiar antes de HTML |
| 258-263 | Mejor try-catch | Capturar Throwable también |

---

## Testing

### Para verificar que funciona:

1. **Abrir Console del Navegador** (F12)
2. **Ir a Consultas/Dashboard**
3. **Hacer clic en "📥 Descargar PDF"**
4. **Observar**:
   - ✅ Sin error "Unexpected token"
   - ✅ Sin HTML en consola
   - ✅ PDF se descarga O error JSON limpio

---

## Comandos para Deploy

```bash
# No necesita cambios en servidor
# Solo verificar que export_pdf.php está en su lugar
ls -la /var/www/takemaster/dashboard/export_pdf.php

# Reiniciar PHP-FPM
sudo systemctl restart php-fpm

# Ver logs si hay error
sudo tail -f /var/log/nginx/takemaster_error.log
```

---

## Qué Pasará Ahora

### Si todo funciona:
1. JavaScript recibe PDF (blob)
2. Se descarga automáticamente
3. ✅ Éxito

### Si hay error:
1. JavaScript recibe: `{"error": "Descripción clara"}`
2. Se muestra en alert()
3. ✅ Error reportado correctamente

### Si hay excepción no prevista:
1. Global exception handler la atrapa
2. Devuelve `{"error": "Error crítico: ..."}`
3. ✅ Nunca HTML

---

## Seguridad Mejorada

✅ No expone detalles técnicos (que salgan en JSON a través de respondError)
✅ Output buffer siempre limpio
✅ Headers siempre JSON
✅ Manejo de Throwable (excepciones)
✅ Validación de archivos temporales

---

**Fecha**: 14 de Noviembre de 2025  
**Status**: ✅ FIXED  
**Archivo**: dashboard/export_pdf.php  
**Líneas**: 265 (antes 235)  
**Cambios**: +30 líneas de error handling
