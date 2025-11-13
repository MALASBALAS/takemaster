<?php
/**
 * plantilla_item.php
 * Componente: Renderiza un item de plantilla en el dashboard
 * 
 * Variables esperadas:
 * - $plantilla: Array con datos de la plantilla
 * - $is_shared: Boolean, true si es plantilla compartida
 */

if (!isset($plantilla)) {
    return;
}

// Garantizar que BASE_URL esté disponible
if (!isset($BASE_URL) || empty($BASE_URL)) {
    $BASE_URL = $_SERVER['HTTP_X_BASE_URL'] ?? '/';
    if (preg_match('/^http/', $_SERVER['REQUEST_SCHEME'] ?? '')) {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $BASE_URL = $scheme . '://' . $host;
    }
}

// Importar funciones necesarias (solo si no existen)
if (!function_exists('extraer_totales_plantilla')) {
    require_once __DIR__ . '/../../funciones/dashboard_helpers.php';
}
if (!function_exists('decrypt_data')) {
    require_once __DIR__ . '/../../funciones/encryption.php';
}

$is_shared = $is_shared ?? false;
$ingresos = 0.0;
$gastos = 0.0;

// Extraer totales
if (!function_exists('extraer_totales_plantilla')) {
    $totales = ['ingresos' => 0.0, 'gastos' => 0.0];
} else {
    $totales = extraer_totales_plantilla($plantilla);
}

$ingresos = $totales['ingresos'];
$gastos = $totales['gastos'];
$beneficio = $ingresos - $gastos;
$show_charts = (abs($ingresos) > 0.0001) || (abs($gastos) > 0.0001);

// Obtener permisos si es compartida
$permisos = null;
if ($is_shared) {
    $rol = $plantilla['rol'] ?? 'lector';
    if (!function_exists('obtener_permisos_rol')) {
        $permisos = ['label' => $rol, 'color' => '#333', 'bg' => '#f0f0f0', 'icon' => '?', 'isReadOnly' => true, 'canEdit' => false, 'canShare' => false, 'message' => 'Rol desconocido'];
    } else {
        $permisos = obtener_permisos_rol($rol);
    }
}

// ID único para canvas
$canvas_id = $is_shared ? 'chart-shared-' . $plantilla['id'] : 'chart-' . $plantilla['id'];
?>
<li class="plantillas-item">
    <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $plantilla['id']; ?>">
        <?php echo htmlspecialchars($plantilla['nombre']); ?>
    </a>
    
    <?php if ($is_shared): ?>
    <div style="font-size:0.85rem;color:#666;font-style:italic;">
        📤 Compartida por: <?php echo htmlspecialchars($plantilla['compartida_por']); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($show_charts): ?>
    <div style="margin-top:8px;display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
        <div style="flex:1 1 320px;max-width:320px;">
            <canvas id="<?php echo htmlspecialchars($canvas_id); ?>" class="plantilla-chart cm-chart-widget" 
                data-type="bar" 
                data-ingresos="<?php echo htmlspecialchars((string)$ingresos, ENT_QUOTES); ?>" 
                data-gastos="<?php echo htmlspecialchars((string)$gastos, ENT_QUOTES); ?>" 
                width="320" height="120" 
                style="display:block;max-width:320px;width:100%;height:auto;" 
                aria-label="Gráfico de ingresos y gastos"></canvas>
            <div style="display:flex;gap:6px;align-items:center;justify-content:center;margin-top:8px;">
                <button type="button" class="chart-filter" data-target="<?php echo htmlspecialchars($canvas_id); ?>" data-mode="both">Todos</button>
                <button type="button" class="chart-filter" data-target="<?php echo htmlspecialchars($canvas_id); ?>" data-mode="ingresos">Ingresos</button>
                <button type="button" class="chart-filter" data-target="<?php echo htmlspecialchars($canvas_id); ?>" data-mode="gastos">Gastos</button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="margin-top:8px;padding:10px;border-radius:6px;background:rgba(0,0,0,0.02);color:#666;font-size:0.95rem;">
        <em>No hay datos suficientes para mostrar gráficos.</em>
    </div>
    <?php endif; ?>
    
    <?php if ($is_shared && $permisos): ?>
    <!-- Información de rol para plantillas compartidas -->
    <div style="margin-top:8px;padding:8px 10px;border-radius:4px;background:<?php echo $permisos['bg']; ?>;color:<?php echo $permisos['color']; ?>;font-size:0.9rem;border:1px solid <?php echo $permisos['color']; ?>;">
        <strong>Tu rol:</strong> <?php echo htmlspecialchars($permisos['label']); ?>
        - <?php echo $permisos['icon']; ?> <?php echo htmlspecialchars($permisos['message']); ?>
    </div>
    
    <!-- Botones de acción para compartidas -->
    <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($permisos['canEdit']): ?>
        <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $plantilla['id']; ?>" 
            style="display:inline-block;padding:8px 14px;background:#0b69ff;color:#fff;border-radius:4px;text-decoration:none;font-size:0.9rem;font-weight:500;">
            ✎ Editar
        </a>
        <?php elseif ($permisos['isReadOnly']): ?>
        <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $plantilla['id']; ?>" 
            style="display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;border-radius:4px;text-decoration:none;font-size:0.9rem;font-weight:500;">
            👁️ Ver
        </a>
        <?php endif; ?>
        
        <?php if ($permisos['canShare']): ?>
        <button type="button" class="share-btn-shared" data-plantilla-id="<?php echo $plantilla['id']; ?>" 
            style="padding:8px 14px;background:#28a745;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.9rem;font-weight:500;">
            📤 Administrar acceso
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Botones de acción para plantillas propias -->
    <div style="display:flex;gap:8px;margin-top:8px;">
        <button type="button" class="delete-btn" data-plantilla-id="<?php echo $plantilla['id']; ?>" 
            style="padding:6px 10px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.9rem;">
            Eliminar
        </button>
        <button type="button" class="share-btn" data-plantilla-id="<?php echo $plantilla['id']; ?>" 
            style="padding:6px 10px;background:#28a745;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.9rem;">
            Compartir
        </button>
    </div>
    <?php endif; ?>
</li>
