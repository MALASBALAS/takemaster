<?php
/**
 * Script de migración: Encriptar plantillas existentes
 * 
 * Ejecutar una sola vez: php migrations/003_encrypt_plantillas.php
 * 
 * ⚠️ HACER BACKUP ANTES DE EJECUTAR
 */

// Cargar configuración
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/encryption.php';

echo "========================================\n";
echo "🔐 Iniciando encriptación de plantillas\n";
echo "========================================\n\n";

try {
    // ⚠️ Desactivar todas las restricciones temporalmente
    $conn->query("SET SESSION sql_mode=''");
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // ⚠️ REMOVER constraint CHECK de plantillas.contenido
    echo "Removiendo restricción CHECK de tabla plantillas...\n";
    @$conn->query("ALTER TABLE plantillas DROP CONSTRAINT IF EXISTS `chk_plantillas_contenido_json`");
    @$conn->query("ALTER TABLE plantillas DROP CONSTRAINT IF EXISTS `plantillas_chk_1`");
    
    // Igual para plantillas_versiones si existe
    echo "Removiendo restricción CHECK de tabla plantillas_versiones...\n";
    @$conn->query("ALTER TABLE plantillas_versiones DROP CONSTRAINT IF EXISTS `plantillas_versiones_chk_1`");
    
    echo "✓ Restricciones removidas\n\n";
    
    // Obtener plantillas (seleccionar solo id y contenido, NO las columnas generadas)
    $stmt = $conn->prepare("
        SELECT id, contenido 
        FROM plantillas 
        WHERE deleted_at IS NULL 
        AND contenido IS NOT NULL
        ORDER BY id ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $encriptadas = 0;
    $errores = 0;
    $saltadas = 0;
    
    // Contar primero
    $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM plantillas WHERE deleted_at IS NULL AND contenido IS NOT NULL");
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $row2 = $res2->fetch_assoc();
    $total = $row2['total'];
    $stmt2->close();
    
    echo "Total de plantillas a encriptar: {$total}\n\n";
    if ($total === 0) {
        echo "✓ No hay plantillas que encriptar\n";
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        exit(0);
    }
    
    while ($row = $result->fetch_assoc()) {
        $plantilla_id = $row['id'];
        $contenido = $row['contenido'];
        
        try {
            // Verificar si ya está encriptado
            if (is_encrypted($contenido)) {
                echo "[{$plantilla_id}] Ya está encriptada ⏭️\n";
                $saltadas++;
                continue;
            }
            
            // Validar que sea JSON válido
            json_decode($contenido, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON inválido: " . json_last_error_msg());
            }
            
            // Encriptar
            $contenido_encriptado = encrypt_content($contenido);
            
            // Actualizar BD
            $update = $conn->prepare("UPDATE plantillas SET contenido = ? WHERE id = ?");
            $update->bind_param("si", $contenido_encriptado, $plantilla_id);
            
            if ($update->execute()) {
                echo "[{$plantilla_id}] ✓ Encriptada ({$update->affected_rows} rows)\n";
                $encriptadas++;
            } else {
                throw new Exception("Error al actualizar: " . $update->error);
            }
            $update->close();
            
        } catch (Exception $e) {
            echo "[{$plantilla_id}] ✗ Error: {$e->getMessage()}\n";
            $errores++;
        }
    }
    $result->close();
    
    // Encriptar versiones también
    echo "\n--- Encriptando versiones ---\n";
    
    $stmt = $conn->prepare("
        SELECT id, contenido 
        FROM plantillas_versiones 
        WHERE contenido IS NOT NULL
        ORDER BY id ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $versiones_encriptadas = 0;
    $versiones_errores = 0;
    $versiones_saltadas = 0;
    
    while ($row = $result->fetch_assoc()) {
        $version_id = $row['id'];
        $contenido = $row['contenido'];
        
        try {
            // Verificar si ya está encriptado
            if (is_encrypted($contenido)) {
                $versiones_saltadas++;
                continue;
            }
            
            // Validar que sea JSON válido
            json_decode($contenido, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON inválido: " . json_last_error_msg());
            }
            
            // Encriptar
            $contenido_encriptado = encrypt_content($contenido);
            
            // Actualizar BD
            $update = $conn->prepare("UPDATE plantillas_versiones SET contenido = ? WHERE id = ?");
            $update->bind_param("si", $contenido_encriptado, $version_id);
            
            if ($update->execute()) {
                $versiones_encriptadas++;
            } else {
                throw new Exception("Error al actualizar: " . $update->error);
            }
            $update->close();
            
        } catch (Exception $e) {
            echo "[V{$version_id}] ✗ Error: {$e->getMessage()}\n";
            $versiones_errores++;
        }
    }
    $result->close();
    
    // Resumen
    echo "\n========================================\n";
    echo "📊 RESUMEN DE MIGRACIÓN\n";
    echo "========================================\n";
    echo "Plantillas encriptadas: {$encriptadas}\n";
    echo "Plantillas saltadas (ya encriptadas): {$saltadas}\n";
    echo "Plantillas con error: {$errores}\n";
    echo "Versiones encriptadas: {$versiones_encriptadas}\n";
    echo "Versiones saltadas: {$versiones_saltadas}\n";
    echo "Versiones con error: {$versiones_errores}\n";
    echo "========================================\n";
    
    // ✅ Re-activar constraints
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    if ($errores === 0 && $versiones_errores === 0) {
        echo "✅ Migración completada exitosamente\n";
        exit(0);
    } else {
        echo "⚠️  Migración completada con errores\n";
        exit(1);
    }
    
} catch (Exception $e) {
    // ✅ Re-activar checks incluso en error
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    echo "❌ Error fatal: {$e->getMessage()}\n";
    echo $e->getTraceAsString();
    exit(1);
}
