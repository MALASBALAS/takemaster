# Base de Datos - TAKEMASTER

Base de datos limpia sin datos sensibles, lista para importar en tu servidor.

## 📋 Contenido

- **Tablas estructurales**: Todas las tablas necesarias
- **Datos de referencia**: Provincias y tarifas de cine
- **Relaciones**: Foreign keys correctamente configuradas
- **Triggers**: Actualización automática de timestamps

## 🚀 Cómo usar

### Opción 1: Importar en phpMyAdmin

1. Abre phpMyAdmin
2. Crea una nueva base de datos llamada `takemaster`
3. Ve a la sección "Importar"
4. Selecciona el archivo `takemaster_clean.sql`
5. Haz clic en "Ejecutar"

### Opción 2: Importar desde línea de comandos

```bash
mysql -u usuario -p < takemaster_clean.sql
```

O si especificas la base de datos:

```bash
mysql -u usuario -p takemaster < takemaster_clean.sql
```

## 📊 Tablas incluidas

| Tabla | Descripción |
|-------|-------------|
| `users` | Usuarios del sistema (sin datos sensibles) |
| `plantillas` | Plantillas de trabajo guardadas |
| `plantillas_compartidas` | Registro de plantillas compartidas |
| `payment_methods` | Métodos de pago (estructura sin datos) |
| `dashboards` | Dashboards personalizados de usuarios |
| `provincias` | Lista de provincias españolas |
| `provincias_cine` | Tarifas de cine por provincia |

## 🔐 Seguridad

✅ **Eliminado**:
- Contraseñas de usuarios (agregar después)
- Emails reales
- Datos de tarjetas de crédito
- Registros de plantillas con datos personales
- Registros de compartición

✅ **Conservado**:
- Estructura de todas las tablas
- Relaciones y constraints
- Datos de referencia (provincias, tarifas)
- Índices y triggers

## ⚙️ Configuración después de importar

### 1. Crear usuario de prueba

```sql
INSERT INTO `users` (`username`, `email`, `password`, `role_id`, `created_at`) VALUES
('testuser', 'test@example.com', '$2y$10$...hash_bcrypt...', 2, NOW());
```

### 2. Verificar conexión en config.php

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'takemaster');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 📝 Notas

- Las contraseñas deben ser hasheadas con `password_hash()` de PHP
- Los AUTO_INCREMENT se reinician a 0 para nuevas inserciones
- Los triggers se recrean automáticamente
- Las foreign keys están activadas

## 🛠️ Mantenimiento

Para actualizar el archivo limpio después de cambios estructurales:

```bash
mysqldump -u usuario -p --no-data takemaster > takemaster_clean.sql
```

---

**Última actualización**: 2025-11-09
**Versión**: 1.0 - Estructura Limpia
