<?php
if (!isset($config) || !isset($totalActivos) || !isset($totalInsolventes) || !isset($ingresosEstimados)) {
    header("Location: ../controlador/InicioController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inicio | <?php echo htmlspecialchars($config['nombre_empresa'] ?? 'TC-Control'); ?></title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable" defer></script>
    <script src="../jv/script.js" defer></script>
</head>
<body>

<section id="toast-container" aria-live="polite" aria-atomic="true"></section>
<header class="topbar">
        <button id="hamburger-btn" class="hamburger-menu" aria-label="Abrir menú">☰</button>
        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            <?php echo htmlspecialchars($config['nombre_empresa'] ?? 'TC-Control'); ?>
        </a>
        <section class="user-actions">
            <span class="text-muted">Administrador</span>
            <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
        </section>
    </header>
<main class="dashboard-layout">
    

    <section class="layout-body">
       <?php $active = 'inicio'; include __DIR__ . '/partes/sidebar.php'; ?>

        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title font-lexend">Resumen General</h1>
                    <p class="page-subtitle">Bienvenido al panel de control principal</p>
                </section>
            </header>

            <section class="kpi-grid">
                <article class="kpi-card">
                    <span class="kpi-label">Clientes Activos</span>
                    <span class="kpi-value text-success"><?php echo htmlspecialchars($totalActivos); ?></span>
                    <span class="kpi-trend text-success">Datos actuales</span>
                </article>
                <article class="kpi-card">
                    <span class="kpi-label">Ingresos ($- <?php echo htmlspecialchars($periodDisplay ?? 'Mes actual'); ?>)</span>
                    <span class="kpi-value"><?php echo number_format($ingresosEstimados, 2); ?></span>
                    <span class="kpi-trend text-success">Pagos registrados en el período actual</span>
                </article>
                <article class="kpi-card">
                    <span class="kpi-label">Insolventes / Pendientes</span>
                    <span class="kpi-value text-danger"><?php echo htmlspecialchars($totalInsolventes); ?></span>
                    <span class="kpi-trend text-danger">Acción requerida</span>
                </article>
            </section>

            <section class="card card-analisis">
                <header class="card-header">
                    <h2 class="card-title">Análisis rápido</h2>
                    <nav class="card-header-controls">
                        <button id="exportInicioExcel" class="btn btn-export">Exportar Excel</button>
                        <button id="exportInicioPdf" class="btn btn-export-secondary">Exportar PDF</button>
                    </nav>
                </header>
                <article class="card-body card-body-analisis">
                    <section class="report-summary">
                        <p class="section-subtitle">Resumen del reporte</p>
                        <ul class="resumen-list">
                            <li><strong>Período:</strong> <span id="reportInicioPeriodLabel"><?php echo htmlspecialchars($periodDisplay ?? 'Mes actual'); ?></span></li>
                            <li><strong>Estado:</strong> <span id="reportInicioFiltersLabel">Todos</span></li>
                            <li><strong>Método de pago:</strong> <span id="reportInicioMethodLabel">Todos</span></li>
                            <li><strong>Total ingresos:</strong> <span id="reportInicioTotalLabel">$0.00</span></li>
                            <li><strong>Pagos registrados:</strong> <span id="reportInicioCountLabel">0</span></li>
                        </ul>
                    </section>
                    <section class="chart-card chart-card-bar">
                        <p class="section-subtitle">Ingresos por método</p>
                        <canvas id="barChartInicio" class="canvas-bar"></canvas>
                    </section>
                    <section class="chart-card chart-card-pie">
                        <p class="section-subtitle">Solvencia de clientes</p>
                        <canvas id="pieChartInicio" class="canvas-pie"></canvas>
                    </section>
                </article>
            </section>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Próximos Vencimientos</h2></header>
                <article class="card-body">
                    <?php if(!empty($proxVencimientos)): ?>
                    <ul>
                        <?php foreach($proxVencimientos as$p): ?>
                            <li class="prox-item">
                                <section class="prox-left">
                                    <?php echo htmlspecialchars($p['nombre']); ?> 
                                    <small class="text-muted">(vence <?php echo htmlspecialchars($p['next_due']); ?>)</small>
                                </section>
                                <section class="prox-right">
                                    <span class="badge badge-warning">
                                    <?php if (!empty($p['seconds']) &&$p['seconds'] < 86400): ?>
                                        <?php echo $p['hours'] > 0 ? htmlspecialchars($p['hours'] . ' horas') : 'menos de 1 hora'; ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($p['days'] . ' días'); ?>
                                    <?php endif; ?>
                                    </span>
                                    <a href="../controlador/pagoscontrolador.php?action=nuevo&cliente=<?php echo urlencode($p['id_cliente']); ?>" class="btn btn-outline btn-sm">Marcar pago</a>
                                </section>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                        <p class="text-muted">No hay vencimientos próximos.</p>
                    <?php endif; ?>
                </article>
            </section>
        </article>
    </section>
</main>

<script>
(function() {
    const reportData = <?php echo json_encode($reportData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    let barChart = null;
    let pieChart = null;

    function formatAmount(value) {
        return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0);
    }

    function buildResumenLabels() {
        const periodo = reportData.period || 'Periodo actual';
        const metodoKeys = Object.keys(reportData.methodTotals || {});
        const metodoLabel = metodoKeys.length ? metodoKeys.join(', ') : 'Todos';
        const estadoLabel = 'Todos';
        return {
            periodo,
            metodoLabel,
            estadoLabel
        };
    }

    function summarizeReport() {
        const totalAmount = Object.values(reportData.methodTotals || {}).reduce((sum, item) => sum + Number(item || 0), 0);
        const totalCount = (reportData.pagos || []).length;
        return { totalAmount, totalCount };
    }

    function renderCharts() {
        const barCtx = document.getElementById('barChartInicio');
        const pieCtx = document.getElementById('pieChartInicio');
        const methodLabels = Object.keys(reportData.methodTotals || {});
        const methodValues = methodLabels.map(key => Number(reportData.methodTotals[key] || 0));
        const solvencyLabels = Object.keys(reportData.solvencyDistribution || {});
        const solvencyValues = solvencyLabels.map(key => Number(reportData.solvencyDistribution[key] || 0));

        if (barCtx) {
            if (barChart) barChart.destroy();
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: methodLabels,
                    datasets: [{
                        label: 'Ingresos',
                        data: methodValues,
                        backgroundColor: methodLabels.map(() => 'rgba(59, 130, 246, 0.75)'),
                        borderColor: methodLabels.map(() => 'rgba(59, 130, 246, 1)'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0,
                                minRotation: 0
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => '$' + formatAmount(value) }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    layout: { padding: { bottom: 20 } }
                }
            });
        }

        if (pieCtx) {
            if (pieChart) pieChart.destroy();
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: solvencyLabels,
                    datasets: [{
                        data: solvencyValues,
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.85)',
                            'rgba(245, 158, 11, 0.85)',
                            'rgba(239, 68, 68, 0.85)',
                            'rgba(59, 130, 246, 0.85)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    layout: { padding: { bottom: 8 } }
                }
            });
        }
    }

    function updateResumen() {
        const labels = buildResumenLabels();
        const summary = summarizeReport();
        const periodEl = document.getElementById('reportInicioPeriodLabel');
        const filtersEl = document.getElementById('reportInicioFiltersLabel');
        const methodEl = document.getElementById('reportInicioMethodLabel');
        const totalEl = document.getElementById('reportInicioTotalLabel');
        const countEl = document.getElementById('reportInicioCountLabel');

        if (periodEl) periodEl.textContent = labels.periodo;
        if (filtersEl) filtersEl.textContent = labels.estadoLabel;
        if (methodEl) methodEl.textContent = labels.metodoLabel;
        if (totalEl) totalEl.textContent = `$${formatAmount(summary.totalAmount)}`;
        if (countEl) countEl.textContent = summary.totalCount;
    }

    function getBase64Chart(chartInstance) {
        return chartInstance ? chartInstance.toBase64Image() : null;
    }

    function exportInicioExcel() {
        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'TC-Control';
        workbook.created = new Date();

        const summary = summarizeReport();
        const labels = buildResumenLabels();

        const resumenSheet = workbook.addWorksheet('Resumen');
        resumenSheet.columns = [
            { header: 'Campo', key: 'campo', width: 26 },
            { header: 'Valor', key: 'valor', width: 42 }
        ];
        resumenSheet.addRow({ campo: 'Período', valor: labels.periodo });
        resumenSheet.addRow({ campo: 'Estado', valor: labels.estadoLabel });
        resumenSheet.addRow({ campo: 'Método de pago', valor: labels.metodoLabel });
        resumenSheet.addRow({ campo: 'Total ingresos', valor: `$${formatAmount(summary.totalAmount)}` });
        resumenSheet.addRow({ campo: 'Pagos registrados', valor: summary.totalCount });
        resumenSheet.addRow({ campo: '', valor: '' });

        const barImageData = getBase64Chart(barChart);
        const pieImageData = getBase64Chart(pieChart);
        if (barImageData) {
            const imageId = workbook.addImage({ base64: barImageData.split(',')[1], extension: 'png' });
            resumenSheet.addImage(imageId, { tl: { col: 2.5, row: 0.5 }, ext: { width: 360, height: 220 } });
        }
        if (pieImageData) {
            const imageId = workbook.addImage({ base64: pieImageData.split(',')[1], extension: 'png' });
            resumenSheet.addImage(imageId, { tl: { col: 2.5, row: 12 }, ext: { width: 300, height: 220 } });
        }

        const methodSheet = workbook.addWorksheet('Ingresos por método');
        methodSheet.columns = [
            { header: 'Método de pago', key: 'metodo', width: 30 },
            { header: 'Ingresos', key: 'ingresos', width: 18 }
        ];
        Object.entries(reportData.methodTotals || {}).forEach(([method, amount]) => {
            methodSheet.addRow({ metodo: method, ingresos: Number(amount || 0) });
        });

        const solvencySheet = workbook.addWorksheet('Solvencia');
        solvencySheet.columns = [
            { header: 'Estado de solvencia', key: 'estado', width: 28 },
            { header: 'Clientes', key: 'cantidad', width: 14 }
        ];
        Object.entries(reportData.solvencyDistribution || {}).forEach(([status, qty]) => {
            solvencySheet.addRow({ estado: status, cantidad: Number(qty || 0) });
        });

        const dailySheet = workbook.addWorksheet('Ingresos diarios');
        dailySheet.columns = [
            { header: 'Fecha', key: 'fecha', width: 18 },
            { header: 'Ingresos', key: 'ingresos', width: 18 }
        ];
        Object.keys(reportData.dailyTotals || {}).sort((a,b) => new Date(a) - new Date(b)).forEach(fecha => {
            dailySheet.addRow({ fecha, ingresos: Number(reportData.dailyTotals[fecha] || 0) });
        });

        const pagosSheet = workbook.addWorksheet('Pagos');
        pagosSheet.columns = [
            { header: 'Cliente', key: 'cliente', width: 28 },
            { header: 'Monto', key: 'monto', width: 14 },
            { header: 'Fecha', key: 'fecha', width: 18 },
            { header: 'Método', key: 'metodo', width: 20 },
            { header: 'Plan', key: 'plan', width: 18 },
            { header: 'Solvencia', key: 'solvencia', width: 16 }
        ];
        (reportData.pagos || []).forEach(p => {
            pagosSheet.addRow({
                cliente: p.NombreCliente || '',
                monto: Number(p.Monto || 0),
                fecha: p.Fecha || '',
                metodo: p.MetodoPago || '',
                plan: p.Plan || '',
                solvencia: p.Solvencia || ''
            });
        });

        workbook.xlsx.writeBuffer().then(buffer => {
            const blob = new Blob([buffer], { type: 'application/octet-stream' });
            saveAs(blob, 'Reportes.xlsx');
        });
    }

    function exportInicioPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });
        const summary = summarizeReport();
        const labels = buildResumenLabels();

        doc.setFontSize(14);
        doc.text('Reportes', 14, 16);
        doc.setFontSize(10);
        doc.text(`Período: ${labels.periodo}`, 14, 24);
        doc.text(`Estado: ${labels.estadoLabel}`, 14, 30);
        doc.text(`Método de pago: ${labels.metodoLabel}`, 14, 36);
        doc.text(`Pagos registrados: ${summary.totalCount}`, 14, 42);
        doc.text(`Total ingresos: $${formatAmount(summary.totalAmount)}`, 14, 48);

        const barImage = getBase64Chart(barChart);
        const pieImage = getBase64Chart(pieChart);
        const imageY = 56;
        if (barImage) doc.addImage(barImage, 'PNG', 14, imageY, 120, 70);
        if (pieImage) doc.addImage(pieImage, 'PNG', 140, imageY, 120, 70);

        doc.addPage();
        const body = (reportData.pagos || []).map(p => [
            p.NombreCliente || '',
            `$${formatAmount(p.Monto)}`,
            p.Fecha || '',
            p.MetodoPago || '',
            p.Plan || '',
            p.Solvencia || ''
        ]);
        doc.autoTable({
            startY: 14,
            head: [['Cliente', 'Monto', 'Fecha', 'Método', 'Plan', 'Solvencia']],
            body,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [59, 130, 246] },
            theme: 'striped',
            margin: { left: 14, right: 14 }
        });
        doc.save('Reportes.pdf');
    }

    function initInicioAnalytics() {
        renderCharts();
        updateResumen();
        const btnExcel = document.getElementById('exportInicioExcel');
        const btnPdf = document.getElementById('exportInicioPdf');
        if (btnExcel) btnExcel.addEventListener('click', exportInicioExcel);
        if (btnPdf) btnPdf.addEventListener('click', exportInicioPDF);
    }

    window.addEventListener('load', initInicioAnalytics);
})();
</script>
</body>
</html>
