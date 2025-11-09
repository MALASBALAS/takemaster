// chart-widget.js
// Initializes Chart.js charts for canvases with class `.cm-chart-widget`.
// Stores instances in window.ChartWidgets keyed by canvas id.
(function(){
    if (!window.Chart) {
        // Chart.js must be loaded before this file
        console.warn('ChartWidget: Chart.js not found. Ensure Chart.js is loaded before chart-widget.js');
        return;
    }

    window.ChartWidgets = window.ChartWidgets || {};

    function parseFloatSafe(v){ return parseFloat(v)||0; }

    function initCanvas(canvas){
        if (!canvas || !canvas.getContext) return null;
        var id = canvas.id || ('chart-' + Math.random().toString(36).slice(2,9));
        canvas.id = id;
        var type = (canvas.dataset.type || '').toLowerCase() || 'bar';
        var ingresos = parseFloatSafe(canvas.dataset.ingresos);
        var gastos = parseFloatSafe(canvas.dataset.gastos);
        var beneficio = parseFloatSafe(canvas.dataset.beneficio);
        var ctx = canvas.getContext('2d');

        var cfg = null;
        if (type === 'doughnut' || type === 'pie') {
            var labels = [];
            var data = [];
            var colors = [];
            if (!isNaN(beneficio) && beneficio >= 0) {
                labels = ['Gastos','Beneficio'];
                data = [gastos, beneficio];
                colors = [getComputedStyle(document.documentElement).getPropertyValue('--accent') ? 'rgba(11,105,255,0.85)' : 'rgba(54,162,235,0.9)', 'rgba(40,167,69,0.9)'];
            } else if (!isNaN(beneficio) && beneficio < 0) {
                labels = ['Gastos','Pérdida'];
                data = [gastos, Math.abs(beneficio)];
                colors = [getComputedStyle(document.documentElement).getPropertyValue('--accent') ? 'rgba(11,105,255,0.85)' : 'rgba(54,162,235,0.9)', 'rgba(220,53,69,0.95)'];
            } else {
                labels = ['Gastos','Ingresos'];
                data = [gastos, ingresos];
                colors = [getComputedStyle(document.documentElement).getPropertyValue('--accent') ? 'rgba(11,105,255,0.85)' : 'rgba(54,162,235,0.9)', 'rgba(40,167,69,0.9)'];
            }
            cfg = {
                type: 'doughnut',
                data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }] },
                options: { responsive: false, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth:12 } }, tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed+' €';}} } } }
            };
        } else {
            // default: bar
            cfg = {
                type: 'bar',
                data: { labels: ['Ingresos','Gastos'], datasets: [{ label: 'Euros', data: [ingresos, gastos], backgroundColor: [getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#0b69ff', 'rgba(220,53,69,0.8)'], borderRadius: 6 }] },
                options: { indexAxis: 'y', responsive: false, maintainAspectRatio: true, plugins: {legend:{display:false}}, scales: { x: { ticks: { beginAtZero: true }, grid: { display: false } }, y: { grid: { display: false } } } }
            };
        }

        try {
            var chart = new Chart(ctx, cfg);
            window.ChartWidgets[id] = chart;
            return chart;
        } catch (e) {
            console.error('ChartWidget init error for', id, e);
            return null;
        }
    }

    function initAll(){
        document.querySelectorAll('.cm-chart-widget').forEach(function(c){
            // avoid re-initializing if already present in window.ChartWidgets
            if (c.id && window.ChartWidgets[c.id]) return;
            initCanvas(c);
        });
    }

    // Attach filter buttons handler (buttons with class chart-filter and data-target/data-mode)
    function initFilters(){
        document.querySelectorAll('.chart-filter').forEach(function(btn){
            btn.addEventListener('click', function(){
                var target = this.dataset.target;
                var mode = this.dataset.mode;
                // Build the correct canvas id from target
                var canvasId = 'chart-' + target;
                var chart = window.ChartWidgets[canvasId];
                var canvas = document.getElementById(canvasId);
                if (!chart || !canvas) {
                    console.warn('ChartWidget filter: Chart or canvas not found for target', target, 'canvasId:', canvasId);
                    return;
                }
                
                var ingresosData = parseFloatSafe(canvas.dataset.ingresos);
                var gastosData = parseFloatSafe(canvas.dataset.gastos);
                
                if (mode === 'both') {
                    chart.data.datasets[0].data = [ingresosData, gastosData];
                } else if (mode === 'ingresos') {
                    chart.data.datasets[0].data = [ingresosData, 0];
                } else if (mode === 'gastos') {
                    chart.data.datasets[0].data = [0, gastosData];
                }
                chart.update();
            });
        });
    }

    // Initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ initAll(); initFilters(); });
    } else { initAll(); initFilters(); }

})();
