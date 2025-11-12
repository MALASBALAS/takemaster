# 🔒 Plan de Seguridad para Plantillas - Implementación

## 📋 Resumen Ejecutivo

Se han implementado **3 capas de seguridad** para proteger las plantillas de los usuarios:

1. **Auditoría Completa** - Log de todas las operaciones (CREATE, UPDATE, DELETE, RESTORE)
2. **Control de Versiones** - Historial completo de cambios + rollback a versiones anteriores
3. **Soft Deletes** - Eliminación reversible con retención de datos

---

## 🔧 Cambios en la Base de Datos

### Nuevas Tablas

#### 1. `plantillas_auditoria` (Log de operaciones)
```
- id: Identificador único
- plantilla_id: Referencia a plantilla
- username: Quién realizó la acción
- accion: CREATE, UPDATE, DELETE, RESTORE, SHARE, UNSHARE
- detalles: JSON con cambios específicos
- ip_address: IP del cliente (para seguridad)
- user_agent: Navegador/cliente
- created_at: Cuándo ocurrió
```

**Uso:** Saber quién cambió qué, cuándo y desde dónde.

#### 2. `plantillas_versiones` (Historial de contenido)
```
- id: Identificador único
- plantilla_id: Referencia a plantilla
- version_numero: 1, 2, 3...
- contenido: Snapshot del JSON en ese momento
- tamaño_bytes: Tamaño del contenido
- hash_contenido: SHA256 (detectar duplicados)
- cambio_descripcion: Qué cambió (opcional)
- guardado_por: Quién guardó
- guardado_desde: IP del cliente
- created_at: Cuándo se guardó
```

**Uso:** Poder recuperar cualquier versión anterior de una plantilla.

### Cambios en Tabla `plantillas`

Se añadieron 4 columnas nuevas:

```sql
- deleted_at: NULL (activa) o timestamp (eliminada)
- version_actual: Número de versión actual
- locked_by: Username si está siendo editada
- locked_until: Hasta cuándo está locked
```

### Nuevas Vistas (filtrado automático)

- `v_plantillas_activas`: Solo plantillas no eliminadas
- `v_plantillas_eliminadas`: Solo plantillas soft-deleted
- `v_auditoria_reciente`: Últimas 100 operaciones

### Nuevos Procedimientos Almacenados (Máxima Seguridad)

- `sp_plantillas_eliminar_seguro()`: Delete con transacción
- `sp_plantillas_restaurar()`: Undo con auditoría
- `sp_plantillas_obtener_version()`: Fetch de versión antigua
- `sp_plantillas_listar_versiones()`: Historial completo
- `sp_mantenimiento_archivar_plantillas()`: Limpieza automática

### Nuevas Funciones SQL

- `fn_plantillas_siguiente_version()`: Calcula número de versión siguiente
- `fn_plantillas_esta_locked()`: Verifica si está en edición

### Disparadores Automáticos (Triggers)

- `plantillas_after_insert`: Registra CREATEs en auditoría
- `plantillas_after_update`: Registra UPDATEs en auditoría + crea versión
- `plantillas_before_soft_delete`: Registra DELETEs/RESTOREs

---

## 📝 Código PHP para Usar

Se creó `/funciones/plantillas_security.php` con funciones lista para usar:

### Importar en tus archivos:
```php
require_once __DIR__ . '/../funciones/plantillas_security.php';
```

### Funciones Disponibles:

#### 1. Crear plantilla segura
```php
$resultado = crear_plantilla_segura(
    $conn,
    $username,
    'Mi Plantilla Nueva',
    ['trabajo' => [], 'gastos_fijos' => []],  // contenido inicial
    get_client_ip()  // opcional, se detecta automáticamente
);

if ($resultado['success']) {
    $plantilla_id = $resultado['plantilla_id'];
}
```

#### 2. Actualizar con versionado automático
```php
$resultado = actualizar_plantilla_segura(
    $conn,
    123,  // plantilla_id
    $username,
    $nuevo_contenido,  // puede ser array o JSON string
    'Agregué 3 trabajos nuevos',  // descripción del cambio (opcional)
    get_client_ip()
);

if ($resultado['success']) {
    $nueva_version = $resultado['version_nueva'];
}
```

#### 3. Eliminar de forma segura (soft delete)
```php
$resultado = eliminar_plantilla_segura(
    $conn,
    123,  // plantilla_id
    $username,
    get_client_ip()
);

// La plantilla sigue existiendo en BD, solo marcada como eliminada
```

#### 4. Restaurar plantilla eliminada
```php
$resultado = restaurar_plantilla(
    $conn,
    123,  // plantilla_id
    $username,
    get_client_ip()
);
```

#### 5. Ver historial de versiones
```php
$resultado = obtener_historial_versiones($conn, 123, $username);

if ($resultado['success']) {
    foreach ($resultado['versiones'] as $v) {
        echo "v{$v['version_numero']} - {$v['tamaño_bytes']} bytes - {$v['guardado_por']}";
    }
}
```

#### 6. Rollback a versión anterior
```php
$resultado = restaurar_version_anterior(
    $conn,
    123,      // plantilla_id
    5,        // restaurar a versión 5
    $username,
    get_client_ip()
);

// Se crea versión 6 con contenido de versión 5
```

#### 7. Ver log de auditoría
```php
$resultado = obtener_auditoria_plantilla(
    $conn,
    123,       // plantilla_id
    $username,
    50         // últimos 50 registros
);

if ($resultado['success']) {
    foreach ($resultado['auditoria'] as $evento) {
        echo "{$evento['accion']} por {$evento['username']} desde {$evento['ip_address']}";
    }
}
```

---

## 🔄 Plan de Implementación Progresivo

### ✅ FASE 1: Ejecutar migration SQL (5 minutos)
```bash
# En tu servidor, ejecutar:
mysql -u usuario -p base_datos < migrations/002_plantillas_security.sql
```

**Lo que pasa:**
- Se crean 2 tablas nuevas (auditoria, versiones)
- Se añaden 4 columnas a `plantillas`
- Se activan triggers automáticamente
- Se crean procedimientos almacenados

### ✅ FASE 2: Migración de datos existentes (1 minuto)
```sql
-- Crear versión 1 para cada plantilla existente
INSERT INTO plantillas_versiones (
    plantilla_id, version_numero, contenido, tamaño_bytes,
    hash_contenido, guardado_por, created_at
)
SELECT
    p.id,
    1,
    p.contenido,
    CHAR_LENGTH(p.contenido),
    UNHEX(SHA2(p.contenido, 256)),
    p.username,
    p.created_at
FROM plantillas p
WHERE NOT EXISTS (
    SELECT 1 FROM plantillas_versiones pv
    WHERE pv.plantilla_id = p.id
);

-- Actualizar version_actual en plantillas
UPDATE plantillas
SET version_actual = (
    SELECT MAX(version_numero) FROM plantillas_versiones
    WHERE plantillas_versiones.plantilla_id = plantillas.id
)
WHERE version_actual IS NULL;
```

### ✅ FASE 3: Reemplazar funciones en PHP (10-15 minutos)

**En `dashboard/dashboard.php`:**

**ANTES (línea 65-75):**
```php
if (isset($_POST['crear_plantilla'])) {
    $nombrePlantilla = $_POST['nombre_plantilla'];
    $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $nombrePlantilla);
    $stmt->execute();
    $plantillaId = $stmt->insert_id;
    $stmt->close();
    // ...
}
```

**DESPUÉS:**
```php
if (isset($_POST['crear_plantilla'])) {
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = crear_plantilla_segura(
        $conn,
        $username,
        $_POST['nombre_plantilla'],
        [],
        get_client_ip()
    );
    
    if ($resultado['success']) {
        $plantillaId = $resultado['plantilla_id'];
    } else {
        die(json_encode(['success' => false, 'error' => $resultado['error']]));
    }
}
```

**En `funciones/guardar_datos.php`:**

**ANTES:**
```php
$stmt = $conn->prepare("UPDATE plantillas SET contenido = ? WHERE id = ? AND username = ?");
$stmt->bind_param("sis", $json, $id, $username);
$stmt->execute();
```

**DESPUÉS:**
```php
require_once __DIR__ . '/plantillas_security.php';

$resultado = actualizar_plantilla_segura(
    $conn,
    $id,
    $username,
    $json_array,  // o string JSON
    'Cambios guardados desde editor',
    get_client_ip()
);

if (!$resultado['success']) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => $resultado['error']]));
}
```

**En `pags/micuenta.php` (eliminación):**

**ANTES:**
```php
if (isset($_POST['eliminar_plantilla'])) {
    $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $_POST['eliminar_plantilla'], $username);
    $stmt->execute();
}
```

**DESPUÉS:**
```php
if (isset($_POST['eliminar_plantilla'])) {
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = eliminar_plantilla_segura(
        $conn,
        $_POST['eliminar_plantilla'],
        $username,
        get_client_ip()
    );
    
    if (!$resultado['success']) {
        http_response_code(400);
        die('Error: ' . $resultado['error']);
    }
}
```

### ✅ FASE 4: Crear interfaz de recuperación (opcional pero recomendado)

Crear `/pags/plantillas_recuperar.php` para ver plantillas eliminadas y restaurarlas:

```php
<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/plantillas_security.php';

start_secure_session();

// Listar plantillas eliminadas
$stmt = $conn->prepare("
    SELECT id, nombre, deleted_at FROM plantillas
    WHERE username = ? AND deleted_at IS NOT NULL
    ORDER BY deleted_at DESC
");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$eliminadas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si POST para restaurar
if ($_POST['restaurar_plantilla']) {
    $resultado = restaurar_plantilla(
        $conn,
        (int)$_POST['restaurar_plantilla'],
        $_SESSION['username'],
        get_client_ip()
    );
    // echo JSON response
}
?>
<!-- HTML para listar y botones de restaurar -->
```

---

## 🛡️ Beneficios de Seguridad

### ✅ Auditoría Completa
- **Quién** modificó cada plantilla
- **Cuándo** se hizo cada cambio
- **Desde dónde** (IP del cliente)
- **Qué** cambió exactamente

### ✅ Recuperación de Datos
- Restaurar plantillas eliminadas
- Volver a versiones anteriores si hay error
- Ver historial completo de cambios

### ✅ Cumplimiento Legal
- Log completo para auditorías
- Trazabilidad de operaciones
- Cumple con GDPR (derecho al olvido con soft deletes)

### ✅ Detección de Fraude
- Identificar acceso no autorizado
- Detectar cambios masivos sospechosos
- Monitorear IPs inusuales

### ✅ Protección contra Accidentes
- Si alguien sobrescribe accidentalmente
- Si hay un bug que corrompe datos
- Si se equivocan al editar

---

## 📊 Consultas Útiles para Monitoreo

### Ver plantillas eliminadas hace más de 30 días:
```sql
SELECT id, nombre, username, deleted_at
FROM plantillas
WHERE deleted_at IS NOT NULL
  AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Ver actividad de hoy:
```sql
SELECT * FROM plantillas_auditoria
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
```

### Ver cambios grandes (más de 10KB):
```sql
SELECT pv.version_numero, pv.tamaño_bytes, pv.guardado_por
FROM plantillas_versiones pv
WHERE pv.tamaño_bytes > 10240
ORDER BY pv.created_at DESC;
```

### Crear backup de auditoría (recomendado mensual):
```sql
SELECT * INTO OUTFILE '/tmp/auditoria_backup.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
FROM plantillas_auditoria;
```

---

## 🚨 Consideraciones Finales

### ✅ Performance
- Los triggers se ejecutan automáticamente
- Índices optimizados para búsquedas rápidas
- Soft deletes no ralentizan lecturas (filtro `deleted_at IS NULL`)

### ✅ Almacenamiento
- Cada versión ocupa espacio (depende del contenido)
- Recomendado: Ejecutar `sp_mantenimiento_archivar_plantillas()` cada mes
- Esto archivará plantillas borradas hace >90 días

### ⚠️ Transiciones Suave
- El código actual seguirá funcionando mientras migras
- Puedes reemplazar funciones paulatinamente
- No hay breaking changes

### 🔐 Próximos Pasos Recomendados
1. Ejecutar migration en servidor test first
2. Probar funciones de creación/actualización
3. Probar versiones (crear, listar, restaurar)
4. Implementar interfaz de recuperación
5. Documentar en README para el equipo

---

**Preguntas frecuentes:**

**P: ¿Qué pasa si ejecuto esta migration y algo falla?**
A: Los procedimientos usan transacciones, así que cualquier error hace rollback automático. La BD quedará consistente.

**P: ¿Puedo seguir usando el código antiguo mientras migro?**
A: Sí, pero las nuevas plantillas usarán las funciones de seguridad y tendrán auditoría. Las antiguas no.

**P: ¿Cuánto espacio ocupa esto?**
A: Depende del contenido. Una plantilla con 50KB de JSON + 1 versión = ~50KB extra. Aumenta con cada versión.

**P: ¿Cómo borro todo de la auditoría?**
A: `DELETE FROM plantillas_auditoria; DELETE FROM plantillas_versiones;` (pero NO recomendado en producción)

**P: ¿Puedo cifrar el contenido?**
A: Sí, antes de insertar: `$contenido = encrypt($json)` y después de extraer: `$contenido = decrypt($stored)`
