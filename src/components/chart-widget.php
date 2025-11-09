<?php
// chart-widget.php
// Simple PHP fragment to render a Chart.js canvas with standardized data-attributes
// Usage example before including: set $chart_id, $chart_type, and $chart_data as needed

if (!isset($chart_id)) $chart_id = 'chart-' . bin2hex(random_bytes(4));
if (!isset($chart_type)) $chart_type = 'bar'; // 'bar' | 'doughnut'
// $chart_data may contain keys: ingresos, gastos, beneficio

$ingresos = isset($chart_data['ingresos']) ? (string)$chart_data['ingresos'] : '';
$gastos = isset($chart_data['gastos']) ? (string)$chart_data['gastos'] : '';
$beneficio = isset($chart_data['beneficio']) ? (string)$chart_data['beneficio'] : '';

?>
<canvas id="<?php echo htmlspecialchars($chart_id, ENT_QUOTES, 'UTF-8'); ?>"
    class="cm-chart-widget"
    data-type="<?php echo htmlspecialchars($chart_type, ENT_QUOTES, 'UTF-8'); ?>"
    data-ingresos="<?php echo htmlspecialchars($ingresos, ENT_QUOTES, 'UTF-8'); ?>"
    data-gastos="<?php echo htmlspecialchars($gastos, ENT_QUOTES, 'UTF-8'); ?>"
    data-beneficio="<?php echo htmlspecialchars($beneficio, ENT_QUOTES, 'UTF-8'); ?>"
    width="320" height="120" style="display:block;max-width:100%;width:100%;height:auto;"
    aria-label="Gráfico"></canvas>
