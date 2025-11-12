# 📋 Checklist de Implementación - Seguridad en Plantillas

## ⚠️ ANTES DE EMPEZAR

✅ **Estado actual:**
- Tienes 3 archivos de seguridad creados
- La migration SQL está lista
- Las funciones PHP están listas
- Este checklist está listo

❌ **QUÉ FALTA AHORA:**

### 1️⃣ PASO CRÍTICO: Ejecutar Migration SQL en Servidor (5 minutos)

**Ubicación del archivo:**
```
/migrations/002_plantillas_security.sql
```

**Cómo ejecutar (elige UNO):**

#### Opción A: phpMyAdmin (Más fácil)
1. Abre phpMyAdmin en tu servidor (testtakemaster.balbe.xyz/phpmyadmin)
2. Selecciona BD: `takemaster`
3. Haz clic en pestaña **"SQL"**
4. Copia TODO el contenido de `002_plantillas_security.sql`
5. Pégalo en el editor SQL
6. Haz clic en **"Ejecutar"**
7. Espera a que termine (debe decir "Query executed successfully")

#### Opción B: Terminal SSH (Más rápido)
```bash
# Conecta a tu servidor SSH
ssh usuario@testtakemaster.balbe.xyz

# Navega a la carpeta del proyecto
cd /ruta/a/takemaster

# Ejecuta la migration
mysql -u tu_usuario -p tu_password takemaster < migrations/002_plantillas_security.sql

# Ingresa contraseña cuando pida
```

#### Opción C: Cliente MySQL local
```bash
# Si tienes MySQL Client instalado localmente
mysql -h testtakemaster.balbe.xyz -u tu_usuario -p tu_password takemaster < C:\ruta\local\002_plantillas_security.sql
```

**✅ Verificar que funcionó:**
```sql
-- Ejecuta esto en phpMyAdmin para confirmar
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'takemaster' 
AND TABLE_NAME IN ('plantillas_auditoria', 'plantillas_versiones');

-- Debe mostrar 2 filas
```

---

### 2️⃣ Migración de Datos Existentes (1 minuto)

**Ejecuta estos 2 comandos SQL después de la migration:**

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
)
ON DUPLICATE KEY UPDATE version_numero = version_numero;

-- Actualizar version_actual en plantillas
UPDATE plantillas
SET version_actual = (
    SELECT MAX(version_numero) FROM plantillas_versiones
    WHERE plantillas_versiones.plantilla_id = plantillas.id
)
WHERE version_actual IS NULL OR version_actual = 0;
```

---

## 📋 CHECKLIST - ORDEN EXACTO

### Fase 1: BD (5-10 minutos)
- [ ] Subir archivo `migrations/002_plantillas_security.sql` al servidor (carpeta `/migrations/`)
- [ ] Ejecutar migration en phpMyAdmin o SSH
- [ ] Verificar que se crearon `plantillas_auditoria` y `plantillas_versiones`
- [ ] Ejecutar scripts de migración de datos existentes

### Fase 2: PHP Nuevo (Ya en servidor)
- [ ] Subir archivo `/funciones/plantillas_security.php` al servidor
- [ ] Verificar que existe en: `/funciones/plantillas_security.php`

### Fase 3: Modificar dashboard.php (10-15 minutos)
En tu archivo local `dashboard.php`:

**Línea 65-75 - Cambiar creación de plantilla:**
```php
// VIEJO (busca esto)
if (isset($_POST['crear_plantilla'])) {
    error_log('[dashboard.php] crear_plantilla POST parameter found');
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    $nombrePlantilla = $_POST['nombre_plantilla'];
    
    $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $nombrePlantilla);
    $stmt->execute();
    $plantillaId = $stmt->insert_id;
    $stmt->close();

// NUEVO (reemplaza con esto)
if (isset($_POST['crear_plantilla'])) {
    error_log('[dashboard.php] crear_plantilla POST parameter found');
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = crear_plantilla_segura(
        $conn,
        $username,
        $_POST['nombre_plantilla'],
        [],
        get_client_ip()
    );
    
    if (!$resultado['success']) {
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => $resultado['error']]));
    }
    
    $plantillaId = $resultado['plantilla_id'];
```

- [ ] Cambiar función de creación en dashboard.php
- [ ] Subir archivo modificado al servidor

### Fase 4: Modificar guardar_datos.php (10-15 minutos)
En tu archivo local `/funciones/guardar_datos.php`:

**Busca línea con `UPDATE plantillas SET contenido`**
```php
// VIEJO
$stmt = $conn->prepare("UPDATE plantillas SET contenido = ? WHERE id = ? AND username = ?");
$stmt->bind_param("sis", $json, $id, $username);
$stmt->execute();

// NUEVO
require_once __DIR__ . '/plantillas_security.php';

$resultado = actualizar_plantilla_segura(
    $conn,
    $id,
    $username,
    $json_array,  // o string JSON - la función acepta ambos
    'Cambios guardados desde editor',
    get_client_ip()
);

if (!$resultado['success']) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => $resultado['error']]));
}
```

- [ ] Cambiar función de actualización en guardar_datos.php
- [ ] Subir archivo modificado al servidor

### Fase 5: Modificar micuenta.php (5-10 minutos)
En tu archivo local `/pags/micuenta.php`:

**Busca las líneas 14-40 donde está el POST de eliminación:**
```php
// ACTUAL (busca algo como)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_plantilla'])) {
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    $idPlantilla = (int)$_POST['eliminar_plantilla'];
    try {
        $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
        // ... etc

// NUEVO (reemplaza con)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_plantilla'])) {
    require_once __DIR__ . '/../funciones/plantillas_security.php';
    
    $resultado = eliminar_plantilla_segura(
        $conn,
        (int)$_POST['eliminar_plantilla'],
        $username,
        get_client_ip()
    );
    
    if (!$resultado['success']) {
        http_response_code(400);
        die('Error: ' . $resultado['error']);
    }
    
    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard", true, 302);
    exit;
}
```

- [ ] Cambiar función de eliminación en micuenta.php
- [ ] Subir archivo modificado al servidor

### Fase 6: Pruebas (5-10 minutos)
- [ ] Crear plantilla nueva → verifica que aparece
- [ ] Editar plantilla → verifica que se guarda
- [ ] Ver versiones antiguas (si implementas esa interfaz)
- [ ] Eliminar plantilla → verifica que desaparece
- [ ] Revisar log de auditoría en BD

---

## 🎯 RESULTADO FINAL

### ✅ Después de completar TODO:

1. **Auditoría automática:**
   - Cada creación, actualización, eliminación se registra
   - Se captura: quién (usuario), cuándo, desde dónde (IP)
   - Consultable en `plantillas_auditoria`

2. **Control de versiones:**
   - Cada cambio en contenido crea nueva versión
   - Se puede hacer rollback a versión anterior
   - Historial completo en `plantillas_versiones`

3. **Soft deletes:**
   - Eliminación reversible
   - Datos no se pierden
   - Se puede restaurar cuando quiera

4. **Seguridad:**
   - Validación de permisos (solo usuario propietario)
   - Transacciones SQL (consistency)
   - Registros IP (trazabilidad)

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### Si algo falla en la migration SQL:
1. Revisa el error en phpMyAdmin
2. Comprueba que tienes permisos CREATE TABLE
3. Intenta ejecutar línea por línea si hay error
4. Contacta al hosting si necesitas permisos elevados

### Si la migration SQL es muy grande:
- Divide el archivo en partes más pequeñas
- Ejecuta primero las CREATE TABLE
- Luego los disparadores (TRIGGER)
- Luego los procedimientos almacenados

### Performance:
- Los triggers podrían ralentizar inserts/updates ligeramente
- Pero es imperceptible en la mayoría de casos
- Los índices están optimizados para búsquedas rápidas

### Espacio en BD:
- Cada versión ocupa espacio (depende del tamaño JSON)
- Recomendado: ejecutar limpieza cada mes
- Usar: `CALL sp_mantenimiento_archivar_plantillas();`

---

## 🚀 PRÓXIMO PASO

**¿Tienes acceso SSH a tu servidor o usas phpMyAdmin?**

- Si dices SÍ a SSH → Te ayudo a ejecutar por terminal
- Si dices SÍ a phpMyAdmin → Te guío paso a paso visual
- Si no sabes → Pregunta a tu hosting cómo ejecutar SQL

**¿Quieres que comience ahora o prefieres hacerlo tú primero?**
