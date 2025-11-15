-- ============================================
-- MIGRATION 002: SEGURIDAD Y AUDITORÍA EN PLANTILLAS
-- ============================================
-- Fecha: 2025-11-12
-- Descripción: Añade auditoría, control de versiones y soft deletes a plantillas
-- Cambios:
--   1. Tabla plantillas_auditoria (log de cambios)
--   2. Tabla plantillas_versiones (historial de contenido)
--   3. Columnas added_at, deleted_at a plantillas
--   4. Índices para performance

-- ============================================
-- 1. CREAR TABLA DE AUDITORÍA
-- ============================================
CREATE TABLE `plantillas_auditoria` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `plantilla_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `accion` enum('CREATE','UPDATE','DELETE','RESTORE','SHARE','UNSHARE') NOT NULL DEFAULT 'UPDATE',
  `detalles` json DEFAULT NULL COMMENT 'Cambios específicos en formato JSON {campo: {anterior: ..., nuevo: ...}}',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 o IPv6 del cliente',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'navegador/cliente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_plantilla_id` (`plantilla_id`),
  KEY `idx_auditoria_username` (`username`),
  KEY `idx_auditoria_accion` (`accion`),
  KEY `idx_auditoria_created_at` (`created_at`),
  CONSTRAINT `fk_auditoria_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_auditoria_user` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de auditoría de todas las operaciones en plantillas';

-- ============================================
-- 2. CREAR TABLA DE VERSIONES (HISTORIAL)
-- ============================================
CREATE TABLE `plantillas_versiones` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `plantilla_id` int(11) NOT NULL,
  `version_numero` int(11) NOT NULL COMMENT 'Número secuencial de versión (1, 2, 3...)',
  `contenido` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Snapshot del contenido en ese momento',
  `tamaño_bytes` int(11) DEFAULT NULL COMMENT 'Tamaño en bytes del contenido JSON',
  `hash_contenido` char(64) DEFAULT NULL COMMENT 'SHA256 del contenido para detectar cambios duplicados',
  `cambio_descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción del cambio (si lo proporciona el usuario)',
  `guardado_por` varchar(50) NOT NULL COMMENT 'Usuario que guardó esta versión',
  `guardado_desde` varchar(45) DEFAULT NULL COMMENT 'IP desde la cual se guardó',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_version_numero` (`plantilla_id`,`version_numero`),
  KEY `idx_version_plantilla_id` (`plantilla_id`),
  KEY `idx_version_creado` (`created_at`),
  KEY `idx_version_guardado_por` (`guardado_por`),
  CONSTRAINT `fk_version_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_version_user` FOREIGN KEY (`guardado_por`) REFERENCES `users` (`username`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de versiones de cada plantilla (permite rollback)';

-- ============================================
-- 3. MODIFICAR TABLA PLANTILLAS - AÑADIR COLUMNAS DE SEGURIDAD
-- ============================================

-- Columna para marcar eliminación suave (soft delete)
ALTER TABLE `plantillas` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `updated_at`;

-- Columna para registrar cuándo se creó (claramente)
ALTER TABLE `plantillas` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Columna para control de versiones
ALTER TABLE `plantillas` ADD COLUMN `version_actual` int(11) DEFAULT 1 AFTER `deleted_at`;

-- Columna para marcar si está en estado "locked" (en edición por otro usuario)
ALTER TABLE `plantillas` ADD COLUMN `locked_by` varchar(50) DEFAULT NULL AFTER `version_actual`;
ALTER TABLE `plantillas` ADD COLUMN `locked_until` timestamp NULL DEFAULT NULL AFTER `locked_by`;

-- Índice para soft deletes (mostrar solo activas)
ALTER TABLE `plantillas` ADD KEY `idx_plantillas_activas` (`deleted_at`, `username`);

-- Índice para locks
ALTER TABLE `plantillas` ADD KEY `idx_plantillas_locks` (`locked_by`, `locked_until`);

-- ============================================
-- 4. CREAR DISPARADORES PARA AUDITORÍA AUTOMÁTICA
-- ============================================

-- Disparador: Registrar INSERT
DELIMITER $$
CREATE TRIGGER `plantillas_after_insert` AFTER INSERT ON `plantillas` FOR EACH ROW
BEGIN
  INSERT INTO `plantillas_auditoria` (
    `plantilla_id`, `username`, `accion`, `detalles`, `ip_address`
  ) VALUES (
    NEW.`id`,
    NEW.`username`,
    'CREATE',
    JSON_OBJECT('nombre', NEW.`nombre`, 'tamaño', CHAR_LENGTH(NEW.`contenido`)),
    INET6_ATON(IFNULL(@client_ip, '0.0.0.0'))
  );
END$$
DELIMITER ;

-- Disparador: Registrar UPDATE
DELIMITER $$
CREATE TRIGGER `plantillas_after_update` AFTER UPDATE ON `plantillas` FOR EACH ROW
BEGIN
  INSERT INTO `plantillas_auditoria` (
    `plantilla_id`, `username`, `accion`, `detalles`, `ip_address`
  ) VALUES (
    NEW.`id`,
    NEW.`username`,
    'UPDATE',
    JSON_OBJECT(
      'nombre_anterior', OLD.`nombre`,
      'nombre_nuevo', NEW.`nombre`,
      'contenido_cambió', OLD.`contenido` <> NEW.`contenido`,
      'tamaño_anterior', CHAR_LENGTH(OLD.`contenido`),
      'tamaño_nuevo', CHAR_LENGTH(NEW.`contenido`)
    ),
    INET6_ATON(IFNULL(@client_ip, '0.0.0.0'))
  );
END$$
DELIMITER ;

-- Disparador: Registrar UPDATE para versiones de contenido
DELIMITER $$
CREATE TRIGGER `plantillas_version_on_update` AFTER UPDATE ON `plantillas` FOR EACH ROW
BEGIN
  -- Solo crear versión si el contenido cambió
  IF OLD.`contenido` <> NEW.`contenido` OR OLD.`nombre` <> NEW.`nombre` THEN
    INSERT INTO `plantillas_versiones` (
      `plantilla_id`,
      `version_numero`,
      `contenido`,
      `tamaño_bytes`,
      `hash_contenido`,
      `guardado_por`,
      `guardado_desde`
    ) VALUES (
      NEW.`id`,
      NEW.`version_actual`,
      NEW.`contenido`,
      CHAR_LENGTH(NEW.`contenido`),
      UNHEX(SHA2(NEW.`contenido`, 256)),
      NEW.`username`,
      INET6_NTOA(INET6_ATON(IFNULL(@client_ip, '0.0.0.0')))
    );
  END IF;
END$$
DELIMITER ;

-- Disparador: Registrar DELETE (soft delete)
DELIMITER $$
CREATE TRIGGER `plantillas_before_soft_delete` BEFORE UPDATE ON `plantillas` FOR EACH ROW
BEGIN
  -- Si se marca como eliminada (deleted_at pasa de NULL a NOT NULL)
  IF OLD.`deleted_at` IS NULL AND NEW.`deleted_at` IS NOT NULL THEN
    INSERT INTO `plantillas_auditoria` (
      `plantilla_id`, `username`, `accion`, `ip_address`
    ) VALUES (
      NEW.`id`,
      NEW.`username`,
      'DELETE',
      INET6_ATON(IFNULL(@client_ip, '0.0.0.0'))
    );
  END IF;
  
  -- Si se restaura (deleted_at pasa de NOT NULL a NULL)
  IF OLD.`deleted_at` IS NOT NULL AND NEW.`deleted_at` IS NULL THEN
    INSERT INTO `plantillas_auditoria` (
      `plantilla_id`, `username`, `accion`, `ip_address`
    ) VALUES (
      NEW.`id`,
      NEW.`username`,
      'RESTORE',
      INET6_ATON(IFNULL(@client_ip, '0.0.0.0'))
    );
  END IF;
END$$
DELIMITER ;

-- ============================================
-- 5. CREAR VISTAS PARA ACCESO SEGURO
-- ============================================

-- Vista: Plantillas activas (filtro automático)
CREATE OR REPLACE VIEW `v_plantillas_activas` AS
SELECT
  `id`,
  `username`,
  `nombre`,
  `contenido`,
  `created_at`,
  `updated_at`,
  `version_actual`,
  `trabajo_count`,
  `trabajo_primero_tipo`
FROM `plantillas`
WHERE `deleted_at` IS NULL;

-- Vista: Plantillas eliminadas (para recuperación)
CREATE OR REPLACE VIEW `v_plantillas_eliminadas` AS
SELECT
  `id`,
  `username`,
  `nombre`,
  `deleted_at`,
  `created_at`,
  `updated_at`
FROM `plantillas`
WHERE `deleted_at` IS NOT NULL
ORDER BY `deleted_at` DESC;

-- Vista: Auditoria reciente (últimas 100 operaciones)
CREATE OR REPLACE VIEW `v_auditoria_reciente` AS
SELECT
  `id`,
  `plantilla_id`,
  `username`,
  `accion`,
  `detalles`,
  `created_at`,
  `ip_address`
FROM `plantillas_auditoria`
ORDER BY `created_at` DESC
LIMIT 100;

-- ============================================
-- 6. PROCEDIMIENTOS ALMACENADOS PARA OPERACIONES SEGURAS
-- ============================================

-- Procedimiento: Eliminar plantilla de forma segura (soft delete)
DELIMITER $$
CREATE PROCEDURE `sp_plantillas_eliminar_seguro`(
  IN p_plantilla_id INT,
  IN p_username VARCHAR(50),
  IN p_client_ip VARCHAR(45)
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al eliminar plantilla - transacción revertida';
  END;

  START TRANSACTION;
  
  -- Verificar que el usuario es propietario
  IF NOT EXISTS(SELECT 1 FROM `plantillas` WHERE `id` = p_plantilla_id AND `username` = p_username AND `deleted_at` IS NULL) THEN
    SIGNAL SQLSTATE '45001' SET MESSAGE_TEXT = 'Plantilla no encontrada o sin permisos';
  END IF;

  -- Establecer IP para triggers
  SET @client_ip = p_client_ip;

  -- Marcar como eliminada
  UPDATE `plantillas` SET `deleted_at` = NOW() WHERE `id` = p_plantilla_id;

  -- Registrar en auditoría
  INSERT INTO `plantillas_auditoria` (
    `plantilla_id`, `username`, `accion`, `ip_address`, `detalles`
  ) VALUES (
    p_plantilla_id,
    p_username,
    'DELETE',
    INET6_ATON(IFNULL(p_client_ip, '0.0.0.0')),
    JSON_OBJECT('método', 'procedimiento almacenado', 'soft_delete', TRUE)
  );

  COMMIT;
END$$
DELIMITER ;

-- Procedimiento: Restaurar plantilla eliminada
DELIMITER $$
CREATE PROCEDURE `sp_plantillas_restaurar`(
  IN p_plantilla_id INT,
  IN p_username VARCHAR(50),
  IN p_client_ip VARCHAR(45)
)
DETERMINISTIC
MODIFIES SQL DATA
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al restaurar plantilla';
  END;

  START TRANSACTION;

  IF NOT EXISTS(SELECT 1 FROM `plantillas` WHERE `id` = p_plantilla_id AND `username` = p_username) THEN
    SIGNAL SQLSTATE '45001' SET MESSAGE_TEXT = 'Plantilla no encontrada';
  END IF;

  SET @client_ip = p_client_ip;

  UPDATE `plantillas` SET `deleted_at` = NULL WHERE `id` = p_plantilla_id;

  INSERT INTO `plantillas_auditoria` (
    `plantilla_id`, `username`, `accion`, `ip_address`
  ) VALUES (
    p_plantilla_id,
    p_username,
    'RESTORE',
    INET6_ATON(IFNULL(p_client_ip, '0.0.0.0'))
  );

  COMMIT;
END$$
DELIMITER ;

-- Procedimiento: Obtener versión anterior (rollback)
DELIMITER $$
CREATE PROCEDURE `sp_plantillas_obtener_version`(
  IN p_plantilla_id INT,
  IN p_version_numero INT
)
DETERMINISTIC
READS SQL DATA
BEGIN
  SELECT
    `contenido`,
    `version_numero`,
    `guardado_por`,
    `created_at`,
    `tamaño_bytes`
  FROM `plantillas_versiones`
  WHERE `plantilla_id` = p_plantilla_id
    AND `version_numero` = p_version_numero
  LIMIT 1;
END$$
DELIMITER ;

-- Procedimiento: Listar todas las versiones de una plantilla
DELIMITER $$
CREATE PROCEDURE `sp_plantillas_listar_versiones`(
  IN p_plantilla_id INT
)
DETERMINISTIC
READS SQL DATA
BEGIN
  SELECT
    `version_numero`,
    `tamaño_bytes`,
    `cambio_descripcion`,
    `guardado_por`,
    `created_at`
  FROM `plantillas_versiones`
  WHERE `plantilla_id` = p_plantilla_id
  ORDER BY `version_numero` DESC;
END$$
DELIMITER ;

-- ============================================
-- 7. FUNCIONES HELPER
-- ============================================

-- Función: Obtener número de versión siguiente
DELIMITER $$
CREATE FUNCTION `fn_plantillas_siguiente_version`(p_plantilla_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_siguiente INT;
  SELECT COALESCE(MAX(`version_numero`), 0) + 1 INTO v_siguiente
  FROM `plantillas_versiones`
  WHERE `plantilla_id` = p_plantilla_id;
  RETURN v_siguiente;
END$$
DELIMITER ;

-- Función: Verificar si plantilla está locked
DELIMITER $$
CREATE FUNCTION `fn_plantillas_esta_locked`(p_plantilla_id INT)
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_locked BOOLEAN;
  SELECT
    CASE
      WHEN `locked_by` IS NOT NULL AND `locked_until` > NOW() THEN TRUE
      ELSE FALSE
    END INTO v_locked
  FROM `plantillas`
  WHERE `id` = p_plantilla_id
  LIMIT 1;
  RETURN COALESCE(v_locked, FALSE);
END$$
DELIMITER ;

-- ============================================
-- 8. MANTENCIÓN: LIMPIAR DATOS ANTIGUOS
-- ============================================

-- Procedimiento: Archivar plantillas eliminadas hace >90 días
DELIMITER $$
CREATE PROCEDURE `sp_mantenimiento_archivar_plantillas`()
DETERMINISTIC
MODIFIES SQL DATA
COMMENT 'Ejecutar cada mes: elimina plantillas soft-deleted de hace >90 días'
BEGIN
  -- En producción, aquí podrías hacer EXPORT a tabla de archivo antes de DELETE
  -- Por ahora, solo loguear qué se borrará
  
  INSERT INTO `plantillas_auditoria` (
    `plantilla_id`,
    `username`,
    `accion`,
    `detalles`
  )
  SELECT
    `id`,
    `username`,
    'DELETE',
    JSON_OBJECT('método', 'mantenimiento automático', 'razón', 'plantilla borrada hace >90 días')
  FROM `plantillas`
  WHERE `deleted_at` IS NOT NULL
    AND `deleted_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);

  -- DELETE FROM plantillas WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$
DELIMITER ;

-- ============================================
-- FIN DE LA MIGRACIÓN
-- ============================================
COMMIT;
