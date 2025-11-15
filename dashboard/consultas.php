<?php
/**
 * consultas.php - Sección de Consultas Avanzadas
 * Permite filtrar trabajos por estudio, tipo, fechas y generar reportes con gráficos
 * 
 * NOTA: Este archivo debe incluirse desde micuenta.php o dashboard.php
 * que ya cargan $conn y $username
 */

require_once __DIR__ . '/../funciones/encryption.php';

if (!function_exists('normalize_filter_value')) {
    function normalize_filter_value($value)
    {
        $normalized = trim((string)$value);
        return $normalized === '' ? '' : mb_strtolower($normalized, 'UTF-8');
    }
}

if (!function_exists('resolve_trabajo_tipo')) {
    function resolve_trabajo_tipo(array $trabajo): string
    {
        $raw = $trabajo['tipo_trabajo'] ?? $trabajo['tipo'] ?? '';
        return trim((string)$raw);
    }
}

if (!function_exists('tipo_matches_filter')) {
    function tipo_matches_filter(string $tipo, string $normalizedFiltro): bool
    {
        if ($normalizedFiltro === '') {
            return true;
        }
        $normalizedTipo = normalize_filter_value($tipo);
        if ($normalizedTipo === '') {
            return false;
        }
        return strpos($normalizedTipo, $normalizedFiltro) !== false || strpos($normalizedFiltro, $normalizedTipo) !== false;
    }
}

if (!function_exists('build_consultas_query')) {
    function build_consultas_query(array $overrides = []): string
    {
        $query = array_merge($_GET, ['section' => 'consultas']);
        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($query[$key]);
                continue;
            }
            $query[$key] = $value;
        }
        return http_build_query($query);
    }

    if (!function_exists('get_next_sort_order')) {
        function get_next_sort_order(string $column, string $currentSortBy, string $currentSortOrder): string
        {
            if ($currentSortBy !== $column) {
                return 'asc';
            }
            if ($currentSortOrder === 'asc') {
                return 'desc';
            }
            if ($currentSortOrder === 'desc') {
                return '';
            }
            return 'asc';
        }
    }

    if (!function_exists('render_sort_icon')) {
        function render_sort_icon(string $column, string $currentSortBy, string $currentSortOrder): string
        {
            if ($currentSortBy !== $column) {
                return '&#x2195;';
            }
            return $currentSortOrder === 'asc' ? '&#x25B2;' : '&#x25BC;';
        }
    }
}

$sortableColumns = ['estudio', 'tipo', 'fecha', 'takes', 'cgs', 'total'];
$sortBy = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $sortableColumns, true) ? $_GET['sort_by'] : '';
$sortOrder = isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['asc', 'desc'], true) ? $_GET['sort_order'] : '';

// Validar que tengamos acceso a las variables necesarias
error_log('[consultas.php] Archivo cargado correctamente');
if (!isset($conn) || !isset($username)) {
    error_log('[consultas.php] Variables no disponibles: conn=' . (isset($conn) ? 'si' : 'no') . ', username=' . (isset($username) ? 'si' : 'no'));
    ?>
    <div style="padding:20px;background:#fee;border:1px solid #fcc;border-radius:4px;color:#c00;">
        <strong>Error en consultas.php:</strong> Variables no disponibles 
        (conn=<?= isset($conn) ? 'si' : 'no' ?>, username=<?= isset($username) ? 'si' : 'no' ?>)
    </div>
    <?php
    return;
}

// Obtener estudios únicos del usuario
$estudios = [];
$stmtEstudios = $conn->prepare("
    SELECT DISTINCT contenido FROM plantillas 
    WHERE username = ? AND deleted_at IS NULL AND contenido IS NOT NULL
    LIMIT 100
");

if (!$stmtEstudios) {
    error_log('[consultas.php] Error en prepare: ' . $conn->error);
    echo '<p style="color: red;">Error al consultar estudios.</p>';
    $estudios = [];
} else {
    $stmtEstudios->bind_param('s', $username);
    if (!$stmtEstudios->execute()) {
        error_log('[consultas.php] Error en execute: ' . $stmtEstudios->error);
        $estudios = [];
    } else {
        $resultEstudios = $stmtEstudios->get_result();
        
        while ($row = $resultEstudios->fetch_assoc()) {
            try {
                $contenido = decrypt_content($row['contenido']);
                $decoded = json_decode($contenido, true);
                if (is_array($decoded) && !empty($decoded['trabajo'])) {
                    foreach ($decoded['trabajo'] as $trabajo) {
                        if (!empty($trabajo['estudio']) && !in_array($trabajo['estudio'], $estudios)) {
                            $estudios[] = $trabajo['estudio'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('[consultas.php] Error desencriptando: ' . $e->getMessage());
                continue;
            }
        }
    }
    $stmtEstudios->close();
}
sort($estudios);

// Tipos de trabajo disponibles
$tiposTrabajoBase = ['Cine', 'Serie', 'Prueba', 'Spot', 'Publicidad', 'Dirección Cine', 'Otros'];
$tiposTrabajoMap = [];
foreach ($tiposTrabajoBase as $tipoBase) {
    $normalizedTipoBase = normalize_filter_value($tipoBase);
    if ($normalizedTipoBase !== '') {
        $tiposTrabajoMap[$normalizedTipoBase] = $tipoBase;
    }
}

// Procesar filtros
$filtroEstudio = isset($_GET['estudio']) ? (is_array($_GET['estudio']) ? $_GET['estudio'] : [$_GET['estudio']]) : [];
$filtroTipo = isset($_GET['tipo']) ? (is_array($_GET['tipo']) ? $_GET['tipo'] : [$_GET['tipo']]) : [];
$filtroFechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtroFechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Limpiar valores vacíos
$filtroEstudio = array_filter($filtroEstudio);
$filtroTipo = array_filter($filtroTipo);

$normalizedFiltroTipo = array_map('normalize_filter_value', $filtroTipo);

// Recopilar datos según filtros
$datosConsulta = [];
$totalIngresos = 0;
$totalTakes = 0;
$totalCgs = 0;

$stmtDatos = $conn->prepare("
    SELECT id, nombre, contenido FROM plantillas 
    WHERE username = ? AND deleted_at IS NULL AND contenido IS NOT NULL
");

if (!$stmtDatos) {
    error_log('[consultas.php] Error en prepare datos: ' . $conn->error);
} else {
    $stmtDatos->bind_param('s', $username);
    if (!$stmtDatos->execute()) {
        error_log('[consultas.php] Error en execute datos: ' . $stmtDatos->error);
    } else {
        $resultDatos = $stmtDatos->get_result();
        
        while ($row = $resultDatos->fetch_assoc()) {
            try {
                $contenido = decrypt_content($row['contenido']);
                $decoded = json_decode($contenido, true);
                if (is_array($decoded) && !empty($decoded['trabajo'])) {
                    foreach ($decoded['trabajo'] as $trabajo) {
                        // Aplicar filtros
                        if (!empty($filtroEstudio) && !in_array($trabajo['estudio'] ?? '', $filtroEstudio)) {
                            continue;
                        }
                        $tipoTrabajoOriginal = resolve_trabajo_tipo($trabajo);
                        $normalizedTipoTrabajo = normalize_filter_value($tipoTrabajoOriginal);
                        if ($normalizedTipoTrabajo !== '' && !isset($tiposTrabajoMap[$normalizedTipoTrabajo])) {
                            $tiposTrabajoMap[$normalizedTipoTrabajo] = $tipoTrabajoOriginal;
                        }
                        if (!empty($filtroTipo) && !in_array($tipoTrabajoOriginal, $filtroTipo)) {
                            continue;
                        }
                        if (!empty($filtroFechaDesde) && (!isset($trabajo['fecha']) || strtotime($trabajo['fecha']) < strtotime($filtroFechaDesde))) {
                            continue;
                        }
                        if (!empty($filtroFechaHasta) && (!isset($trabajo['fecha']) || strtotime($trabajo['fecha']) > strtotime($filtroFechaHasta))) {
                            continue;
                        }
                        
                        // Agregar a resultados
                        $trabajo['_plantilla_id'] = $row['id'];
                        $trabajo['_plantilla_nombre'] = $row['nombre'] ?? '';
                        $datosConsulta[] = $trabajo;
                        $totalTakes += (int)($trabajo['takes'] ?? 0);
                        $totalCgs += (int)($trabajo['cgs'] ?? 0);
                        
                        // Calcular ingresos según tarifa
                        $tarifa = (float)($trabajo['total'] ?? 0);
                        $totalIngresos += $tarifa;
                    }
                }
            } catch (Exception $e) {
                error_log('[consultas.php] Error desencriptando datos: ' . $e->getMessage());
                continue;
            }
        }
    }
    $stmtDatos->close();
}

    // Ordenar los tipos recolectados antes de renderizar
    $tiposTrabajo = array_values($tiposTrabajoMap);
    usort($tiposTrabajo, function($a, $b) {
        return strcasecmp($a, $b);
    });

// Aplicar ordenamiento si está especificado
if ($sortBy !== '' && $sortOrder !== '' && !empty($datosConsulta)) {
    usort($datosConsulta, function($a, $b) use ($sortBy, $sortOrder) {
        $resolve = function(array $item, string $column) {
            switch ($column) {
                case 'fecha':
                    return $item['fecha'] ?? '';
                case 'takes':
                    return (int)($item['takes'] ?? 0);
                case 'cgs':
                    return (int)($item['cgs'] ?? 0);
                case 'total':
                    return (float)($item['total'] ?? 0);
                case 'tipo':
                    return resolve_trabajo_tipo($item);
                case 'estudio':
                default:
                    return $item['estudio'] ?? '';
            }
        };
        $valA = $resolve($a, $sortBy);
        $valB = $resolve($b, $sortBy);
        if ($valA === $valB) {
            return 0;
        }
        $direction = $sortOrder === 'asc' ? 1 : -1;
        if (is_numeric($valA) && is_numeric($valB)) {
            return ($valA < $valB ? -1 : 1) * $direction;
        }
        return (strcasecmp((string)$valA, (string)$valB) < 0 ? -1 : 1) * $direction;
    });
}

// Agrupar por estudio para gráficos
$datosPorEstudio = [];
foreach ($datosConsulta as $trabajo) {
    $estudio = $trabajo['estudio'] ?? 'Sin estudio';
    if (!isset($datosPorEstudio[$estudio])) {
        $datosPorEstudio[$estudio] = [
            'ingresos' => 0,
            'trabajos' => 0,
            'takes' => 0
        ];
    }
    $datosPorEstudio[$estudio]['ingresos'] += (float)($trabajo['total'] ?? 0);
    $datosPorEstudio[$estudio]['trabajos']++;
    $datosPorEstudio[$estudio]['takes'] += (int)($trabajo['takes'] ?? 0);
}

// Agrupar por tipo para gráficos
$datosPorTipo = [];
foreach ($datosConsulta as $trabajo) {
    $tipo = resolve_trabajo_tipo($trabajo);
    if ($tipo === '') {
        $tipo = 'Sin tipo';
    }
    if (!isset($datosPorTipo[$tipo])) {
        $datosPorTipo[$tipo] = [
            'ingresos' => 0,
            'trabajos' => 0
        ];
    }
    $datosPorTipo[$tipo]['ingresos'] += (float)($trabajo['total'] ?? 0);
    $datosPorTipo[$tipo]['trabajos']++;
}

// Procesar exportación vía AJAX/JavaScript (sin POST backend)
// La exportación se maneja enteramente desde JavaScript recolectando datos del DOM
?>

<style>
    .consulta-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .filtro-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .filtro-group {
        margin-bottom: 15px;
    }
    
    .filtro-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 0.95rem;
    }
    
    .filtro-group input,
    .filtro-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    .filtro-group select[multiple] {
        padding: 4px;
        min-height: 150px;
    }
    
    .filtro-group select[multiple] option {
        padding: 8px 12px;
        margin: 4px 0;
        background: white;
        color: #333;
        border-radius: 4px;
    }
    
    .filtro-group select[multiple] option:checked {
        background: linear-gradient(135deg, #0b69ff 0%, #0055cc 100%);
        color: white;
        font-weight: 600;
    }
    
    .filtro-group input:focus,
    .filtro-group select:focus {
        outline: none;
        border-color: #0b69ff;
        box-shadow: 0 0 0 2px rgba(11, 105, 255, 0.1);
    }
    
    .resultados-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        grid-column: 1 / -1;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #0b69ff 0%, #0055cc 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    .stat-card h3 {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .stat-card .value {
        font-size: 1.8rem;
        font-weight: bold;
        margin-top: 8px;
    }
    
    .graficos-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .grafico-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .grafico-box h3 {
        margin-top: 0;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }
    
    .tabla-resultados {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .tabla-resultados th {
        background: #f5f5f5;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .tabla-resultados td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .tabla-resultados tr:hover {
        background: #fafafa;
    }

    .sortable-header a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        color: inherit;
        font-weight: 600;
    }

    .sortable-header .sort-icon {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    tr.data-row {
        cursor: pointer;
    }

    tr.data-row td {
        transition: background 0.2s ease;
    }

    tr.data-row:hover td {
        background: rgba(11, 105, 255, 0.05);
    }

    .export-dropdown {
        position: relative;
        display: inline-flex;
    }

    #export-toggle {
        padding: 10px 16px;
        border-radius: 6px;
        border: none;
        background: #28a745;
        color: white;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
    }

    #export-toggle:hover {
        background: #218838;
    }

    .export-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        border: 1px solid #dfe3e8;
        border-radius: 8px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
        margin-top: 8px;
        min-width: 200px;
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
        animation: slideDown 0.2s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .export-menu.visible {
        display: flex;
    }

    .export-item {
        background: transparent;
        color: #111;
        text-align: left;
        border: none;
        padding: 12px 14px;
        width: 100%;
        cursor: pointer;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .export-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #0b69ff 0%, #0055cc 100%);
        opacity: 0.05;
        transition: left 0.3s ease;
        z-index: -1;
    }

    .export-item:hover::before {
        left: 0;
    }

    .export-item:hover {
        background: rgba(11, 105, 255, 0.08);
        color: #0b69ff;
        font-weight: 500;
        padding-left: 16px;
    }

    .export-item:active {
        background: rgba(11, 105, 255, 0.12);
    }

    .export-item:first-child {
        border-radius: 8px 8px 0 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .export-item:last-child {
        border-radius: 0 0 8px 8px;
    }

    .export-item:not(:last-child) {
        border-bottom: 1px solid #f0f0f0;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 8px;
        margin-top: 16px;
    }

    .table-responsive table {
        min-width: 640px;
    }
    
    .acciones {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-exportar {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .btn-exportar:hover {
        background: #218838;
    }
    
    .btn-limpiar {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .btn-limpiar:hover {
        background: #5a6268;
    }
    
    @media (max-width: 900px) {
        .consulta-container {
            grid-template-columns: 1fr;
        }
        .graficos-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 800px) {
        .acciones {
            flex-direction: column;
            align-items: stretch;
        }
        .acciones form {
            width: 100%;
        }
        .acciones select,
        .acciones .btn-exportar,
        .acciones .btn-limpiar {
            width: 100%;
            text-align: center;
        }
    }

    /* Estilos mejorados para select múltiple */
    .filtro-group select[multiple] option {
        background: white;
        color: #333;
        padding: 10px 12px;
        margin: 2px 0;
        line-height: 1.6;
        cursor: pointer;
    }
    
    .filtro-group select[multiple] option:checked {
        background: linear-gradient(135deg, #0b69ff 0%, #0055cc 100%);
        color: white;
        font-weight: 600;
        box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.1);
    }
    
    .filtro-group select[multiple] option:checked:after {
        content: "✓";
    }
</style>

<h2>Consultas Avanzadas</h2>

<div class="consulta-container">
    <!-- Panel de Filtros -->
    <div class="filtro-section">
        <h3>Filtros</h3>
        <form method="GET" id="form-filtros">
            <input type="hidden" name="section" value="consultas">
            
            <div class="filtro-group">
                <label for="estudio">Estudio</label>
                <select name="estudio[]" id="estudio" multiple>
                    <?php foreach ($estudios as $est): ?>
                        <option value="<?php echo htmlspecialchars($est); ?>" <?php echo (is_array($filtroEstudio) && in_array($est, $filtroEstudio)) || $filtroEstudio === $est ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filtro-group">
                <label for="tipo">Tipo de Trabajo</label>
                <select name="tipo[]" id="tipo" multiple>
                    <?php foreach ($tiposTrabajo as $tipo): ?>
                        <?php $normalizedTipoOption = normalize_filter_value($tipo); ?>
                        <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo (is_array($filtroTipo) && in_array($tipo, $filtroTipo)) || (is_string($filtroTipo) && $normalizedFiltroTipo !== '' && $normalizedFiltroTipo === $normalizedTipoOption) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filtro-group">
                <label for="fecha_desde">Desde fecha</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="<?php echo htmlspecialchars($filtroFechaDesde); ?>">
            </div>
            
            <div class="filtro-group">
                <label for="fecha_hasta">Hasta fecha</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?php echo htmlspecialchars($filtroFechaHasta); ?>">
            </div>
            
            <div class="acciones">
                <button type="submit" class="btn-exportar" style="background: #0b69ff;">Aplicar Filtros</button>
                <a href="?section=consultas" class="btn-limpiar" style="text-decoration: none; display: inline-block;">Limpiar</a>
            </div>
        </form>
    </div>
    
    <!-- Resumen de Resultados -->
    <div class="filtro-section">
        <h3>Resumen</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Trabajos</h3>
                <div class="value"><?php echo count($datosConsulta); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Takes</h3>
                <div class="value"><?php echo $totalTakes; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total CGs</h3>
                <div class="value"><?php echo $totalCgs; ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <h3>Ingresos</h3>
                <div class="value"><?php echo number_format($totalIngresos, 2); ?>€</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<?php if (!empty($datosPorEstudio)): ?>
<div class="graficos-container">
    <div class="grafico-box">
        <h3>Ingresos por Estudio</h3>
        <canvas id="grafico-ingresos-estudio"></canvas>
    </div>
    <div class="grafico-box">
        <h3>Ingresos por Tipo</h3>
        <canvas id="grafico-ingresos-tipo"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Resultados en Tabla -->
<div class="resultados-section">
    <h3>Detalles de Trabajos</h3>
    
    <?php if (!empty($datosConsulta)): ?>
        <div class="table-responsive">
            <table class="tabla-resultados">
            <thead>
                <tr>
                    <?php
                    $columns = ['estudio' => 'Estudio', 'tipo' => 'Tipo', 'fecha' => 'Fecha', 'takes' => 'Takes', 'cgs' => 'CGs', 'total' => 'Ingresos'];
                    foreach ($columns as $colKey => $label):
                        $nextOrder = get_next_sort_order($colKey, $sortBy, $sortOrder);
                        $query = $nextOrder === ''
                            ? build_consultas_query(['sort_by' => null, 'sort_order' => null])
                            : build_consultas_query(['sort_by' => $colKey, 'sort_order' => $nextOrder]);
                    ?>
                        <th class="sortable-header">
                            <a href="/pags/micuenta.php?<?= htmlspecialchars($query); ?>">
                                <?php echo $label; ?> <span class="sort-icon"><?= render_sort_icon($colKey, $sortBy, $sortOrder); ?></span>
                            </a>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datosConsulta as $trabajo): ?>
                    <?php $rowLink = isset($trabajo['_plantilla_id']) ? (BASE_URL . '/plantillas/miplantilla.php?id=' . urlencode($trabajo['_plantilla_id'])) : ''; ?>
                    <tr class="data-row" onclick="<?= $rowLink ? "window.location.href='" . htmlspecialchars($rowLink, ENT_QUOTES, 'UTF-8') . "';" : "" ?>" style="<?= $rowLink ? 'cursor: pointer;' : '' ?>">
                        <td><?php echo htmlspecialchars($trabajo['estudio'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(resolve_trabajo_tipo($trabajo)); ?></td>
                        <td><?php echo htmlspecialchars($trabajo['fecha'] ?? ''); ?></td>
                        <td><?php echo (int)($trabajo['takes'] ?? 0); ?></td>
                        <td><?php echo (int)($trabajo['cgs'] ?? 0); ?></td>
                        <td><?php echo number_format($trabajo['total'] ?? 0, 2, ',', '.'); ?>€</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #999; padding: 20px;">No hay datos que coincidan con los filtros seleccionados.</p>
    <?php endif; ?>
    
    <!-- Exportación -->
    <?php if (!empty($datosConsulta)): ?>
    <div class="acciones">
        <div class="export-dropdown" id="export-dropdown">
            <button id="export-btn" class="btn-exportar" type="button"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/><path d="M12 13v4m2-2h-4" stroke="currentColor" stroke-width="2" fill="none"/></svg> Exportar</button>
            <div id="export-menu" class="export-menu">
                <button class="export-item" data-format="csv" type="button"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M6 6h3v3H6zm5 0h3v3h-3zm5 0h3v3h-3zM6 11h3v3H6zm5 0h3v3h-3zm5 0h3v3h-3zM6 16h3v3H6zm5 0h3v3h-3zm5 0h3v3h-3z" fill="white"/></svg> Exportar a Excel (CSV)</button>
                <button class="export-item" data-format="pdf" type="button"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg> Exportar a PDF</button>
                <button class="export-item" data-format="xml" type="button"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9l-7-7z"/></svg> Exportar a XML</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($datosPorEstudio)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de Ingresos por Estudio
    const estudios = <?php echo json_encode(array_keys($datosPorEstudio)); ?>;
    const ingresosEstudio = <?php echo json_encode(array_map(function($d) { return $d['ingresos']; }, $datosPorEstudio)); ?>;
    
    const ctxIngresosEstudio = document.getElementById('grafico-ingresos-estudio');
    if (ctxIngresosEstudio) {
        new Chart(ctxIngresosEstudio.getContext('2d'), {
            type: 'bar',
            data: {
                labels: estudios,
                datasets: [{
                    label: 'Ingresos (€)',
                    data: ingresosEstudio,
                    backgroundColor: 'rgba(11, 105, 255, 0.7)',
                    borderColor: 'rgba(11, 105, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    
    // Gráfico de Ingresos por Tipo
    const tipos = <?php echo json_encode(array_keys($datosPorTipo)); ?>;
    const ingresosTipo = <?php echo json_encode(array_map(function($d) { return $d['ingresos']; }, $datosPorTipo)); ?>;
    
    const ctxIngresosTipo = document.getElementById('grafico-ingresos-tipo');
    if (ctxIngresosTipo) {
        new Chart(ctxIngresosTipo.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: tipos,
                datasets: [{
                    label: 'Ingresos (€)',
                    data: ingresosTipo,
                    backgroundColor: [
                        'rgba(11, 105, 255, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(156, 39, 176, 0.7)',
                        'rgba(0, 188, 212, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }
</script>

<script>
    function toggleExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('export-menu');
        if (menu) {
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    // Cerrar menú de exportar al hacer click fuera
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('export-dropdown');
        const menu = document.getElementById('export-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        // Mejorar visualización de select múltiple con comportamiento toggle
        const multiSelects = document.querySelectorAll('select[multiple]');
        multiSelects.forEach(select => {
            // Función para actualizar estilos
            const updateStyles = (selectElement) => {
                const selected = Array.from(selectElement.selectedOptions).map(o => o.value);
                const options = selectElement.querySelectorAll('option');
                options.forEach(option => {
                    if (selected.includes(option.value)) {
                        option.style.backgroundColor = '#0b69ff';
                        option.style.color = 'white';
                        option.style.fontWeight = '600';
                    } else {
                        option.style.backgroundColor = 'white';
                        option.style.color = '#333';
                        option.style.fontWeight = 'normal';
                    }
                });
            };
            
            select.addEventListener('change', function() {
                updateStyles(this);
            });
            
            // Interceptar clicks para toggle sin necesidad de Ctrl
            select.addEventListener('mousedown', function(e) {
                if (e.target.tagName === 'OPTION') {
                    e.preventDefault();
                    e.target.selected = !e.target.selected;
                    this.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            
            // Trigger initial styling
            updateStyles(select);
        });

        // Export menu handlers
        const exportBtn = document.getElementById('export-btn');
        const exportMenu = document.getElementById('export-menu');

        if (exportBtn && exportMenu) {
            exportBtn.addEventListener('click', function(e){
                e.stopPropagation();
                exportMenu.style.display = exportMenu.style.display === 'none' ? 'block' : 'none';
            });

            document.querySelectorAll('.export-item').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const fmt = btn.getAttribute('data-format');
                    const data = gatherTableData();
                    if (fmt === 'csv') downloadCSV(data);
                    else if (fmt === 'pdf') downloadPDF(data);
                    else if (fmt === 'xml') downloadXML(data);
                    exportMenu.style.display = 'none';
                });
            });
        }
    });

    // Helpers to collect data from the table
    function gatherTableData(){
        const trabajos = [];
        const tableBody = document.querySelector('.tabla-resultados tbody');
        
        if (tableBody) {
            tableBody.querySelectorAll('tr').forEach(function(tr){
                const cells = tr.querySelectorAll('td');
                if (cells.length >= 6) {
                    trabajos.push({
                        estudio: cells[0].textContent.trim(),
                        tipo: cells[1].textContent.trim(),
                        fecha: cells[2].textContent.trim(),
                        takes: cells[3].textContent.trim(),
                        cgs: cells[4].textContent.trim(),
                        total: cells[5].textContent.trim()
                    });
                }
            });
        }

        return {
            trabajos: trabajos,
            totales: {
                trabajos: document.querySelector('.stat-card .value') ? 
                    document.querySelectorAll('.stat-card .value')[0].textContent.trim() : '0',
                takes: document.querySelector('.stat-card .value') ? 
                    document.querySelectorAll('.stat-card .value')[1].textContent.trim() : '0',
                cgs: document.querySelector('.stat-card .value') ? 
                    document.querySelectorAll('.stat-card .value')[2].textContent.trim() : '0',
                ingresos: document.querySelector('.stat-card .value') ? 
                    document.querySelectorAll('.stat-card .value')[3].textContent.trim() : '0'
            }
        };
    }

    // CSV download (Excel-friendly CSV)
    function downloadCSV(data){
        let lines = [];
        
        // Encabezado Takemaster
        lines.push(['TAKEMASTER - Consulta de Trabajos']);
        lines.push(['Generado: ' + new Date().toLocaleString('es-ES')]);
        lines.push([]);
        
        // Encabezados
        lines.push(['Estudio','Tipo','Fecha','Takes','CGs','Ingresos'].map(escapeCsv).join(','));
        
        // Datos
        data.trabajos.forEach(function(t){
            lines.push([t.estudio, t.tipo, t.fecha, t.takes, t.cgs, t.total].map(escapeCsv).join(','));
        });
        
        // Resumen
        lines.push('');
        lines.push(['RESUMEN', '', '', '', '', ''].map(escapeCsv).join(','));
        lines.push(['Total Trabajos', data.totales.trabajos, '', '', '', ''].map(escapeCsv).join(','));
        lines.push(['Total Takes', data.totales.takes, '', '', '', ''].map(escapeCsv).join(','));
        lines.push(['Total CGs', data.totales.cgs, '', '', '', ''].map(escapeCsv).join(','));
        lines.push(['Total Ingresos', data.totales.ingresos, '', '', '', ''].map(escapeCsv).join(','));

        const blob = new Blob([lines.join('\r\n')], {type:'text/csv;charset=utf-8;'});
        const filename = 'consulta_' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.csv';
        triggerDownload(blob, filename);
    }

    function escapeCsv(v){
        if (v === null || v === undefined) return '';
        const s = String(v);
        if (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r')){
            return '"' + s.replace(/"/g,'""') + '"';
        }
        return s;
    }

    // PDF download - enviar datos al backend
    function downloadPDF(data){
        // Mostrar indicador de carga
        const btn = document.querySelector('[data-format="pdf"]');
        const originalText = btn ? btn.textContent : '';
        if (btn) btn.textContent = '⏳ Generando PDF...';
        
        fetch('/dashboard/export_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Error generando PDF');
                });
            }
            // Obtener el nombre del archivo del header
            const filename = 'consulta_' + new Date().toISOString().slice(0,10) + '.pdf';
            
            // Convertir response a blob y descargar
            return response.blob().then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                
                if (btn) btn.textContent = originalText;
            });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generando PDF: ' + error.message);
            if (btn) btn.textContent = originalText;
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // XML download
    function downloadXML(data){
        function esc(s){
            if (s === null || s === undefined) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&apos;');
        }
        let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
        xml += '<consulta fecha_generacion="' + new Date().toISOString() + '" aplicacion="TAKEMASTER">\n';

        xml += '  <trabajos>\n';
        data.trabajos.forEach(function(t){
            xml += '    <trabajo>\n';
            xml += '      <estudio>'+esc(t.estudio)+'</estudio>\n';
            xml += '      <tipo>'+esc(t.tipo)+'</tipo>\n';
            xml += '      <fecha>'+esc(t.fecha)+'</fecha>\n';
            xml += '      <takes>'+esc(t.takes)+'</takes>\n';
            xml += '      <cgs>'+esc(t.cgs)+'</cgs>\n';
            xml += '      <ingresos>'+esc(t.total)+'</ingresos>\n';
            xml += '    </trabajo>\n';
        });
        xml += '  </trabajos>\n';

        xml += '  <resumen>\n';
        xml += '    <total_trabajos>'+esc(data.totales.trabajos)+'</total_trabajos>\n';
        xml += '    <total_takes>'+esc(data.totales.takes)+'</total_takes>\n';
        xml += '    <total_cgs>'+esc(data.totales.cgs)+'</total_cgs>\n';
        xml += '    <total_ingresos>'+esc(data.totales.ingresos)+'</total_ingresos>\n';
        xml += '  </resumen>\n';

        xml += '</consulta>';

        const blob = new Blob([xml], {type:'application/xml;charset=utf-8;'});
        const filename = 'consulta_' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.xml';
        triggerDownload(blob, filename);
    }

    function triggerDownload(blob, filename){
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = filename; document.body.appendChild(a); a.click();
        setTimeout(function(){ URL.revokeObjectURL(url); a.remove(); }, 1000);
    }
</script>
<?php endif; ?>

