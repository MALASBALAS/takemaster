<?php
/**
 * dashboard_charts.php
 * Componente: Gráficos de resumen de ingresos/gastos
 */

if (!isset($mis_plantillas, $plantillas_compartidas_conmigo, $totales_propias, $totales_compartidas)) {
    return;
}
?>
<div style="margin:14px auto;display:flex;gap:18px;align-items:center;flex-wrap:wrap;justify-content:center;max-width:900px;">
    <!-- Donut de Plantillas Propias -->
    <div style="flex:1 1 280px;min-width:250px;text-align:center;">
        <h4 style="margin-bottom:12px;color:#0b69ff;"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Mis Plantillas</h4>
        <canvas id="chart-pie-propias" class="cm-chart-widget" data-type="doughnut" 
            data-ingresos="<?php echo htmlspecialchars((string)$totales_propias['ingresos'], ENT_QUOTES); ?>" 
            data-gastos="<?php echo htmlspecialchars((string)$totales_propias['gastos'], ENT_QUOTES); ?>" 
            data-beneficio="<?php echo htmlspecialchars((string)$totales_propias['beneficio'], ENT_QUOTES); ?>" 
            width="200" height="200" style="display:block;max-width:220px;width:100%;height:auto;margin:0 auto;" 
            aria-label="Gráfico circular propias"></canvas>
        <div style="margin-top:8px;font-size:0.9rem;color:#333;">
            <div><strong>Ingresos:</strong> <?php echo number_format($totales_propias['ingresos'],2,',','.'); ?> €</div>
            <div><strong>Gastos:</strong> <?php echo number_format($totales_propias['gastos'],2,',','.'); ?> €</div>
            <div style="margin-top:6px;padding-top:6px;border-top:1px solid #ddd;">
                <strong>Beneficio:</strong> <?php echo number_format($totales_propias['beneficio'],2,',','.'); ?> €
            </div>
        </div>
    </div>

    <!-- Donut de Plantillas Compartidas (si las hay) -->
    <div style="flex:1 1 280px;min-width:250px;text-align:center;">
        <h4 style="margin-bottom:12px;color:#28a745;"><svg class="icon-inline" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v11m-7-2l7-7 7 7M2 20h20v2H2z"/></svg> Plantillas Compartidas</h4>
        <canvas id="chart-pie-compartidas" class="cm-chart-widget" data-type="doughnut" 
            data-ingresos="<?php echo htmlspecialchars((string)$totales_compartidas['ingresos'], ENT_QUOTES); ?>" 
            data-gastos="<?php echo htmlspecialchars((string)$totales_compartidas['gastos'], ENT_QUOTES); ?>" 
            data-beneficio="<?php echo htmlspecialchars((string)$totales_compartidas['beneficio'], ENT_QUOTES); ?>" 
            width="200" height="200" style="display:block;max-width:220px;width:100%;height:auto;margin:0 auto;" 
            aria-label="Gráfico circular compartidas"></canvas>
        <div style="margin-top:8px;font-size:0.9rem;color:#333;">
            <div><strong>Ingresos:</strong> <?php echo number_format($totales_compartidas['ingresos'],2,',','.'); ?> €</div>
            <div><strong>Gastos:</strong> <?php echo number_format($totales_compartidas['gastos'],2,',','.'); ?> €</div>
            <div style="margin-top:6px;padding-top:6px;border-top:1px solid #ddd;">
                <strong>Beneficio:</strong> <?php echo number_format($totales_compartidas['beneficio'],2,',','.'); ?> €
            </div>
        </div>
    </div>
</div>
