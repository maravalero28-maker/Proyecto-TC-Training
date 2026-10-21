<?php
if (!isset($pagosServicios)) {
    header("Location: ../controlador/PagoServicioController.php");
    exit();
}
$safePagos = isset($pagosServicios) ? $pagosServicios : array();
$safeMatrix = isset($serviciosMatrix) ? $serviciosMatrix : array();
$safeYear = isset($year) ? $year : date('Y');
$safeFilters = isset($filters) ? $filters : array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pago de Servicios | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../jv/script.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/partes/toast-notification.php'; ?>

<header class="topbar">
    <button id="hamburger-btn" class="menu-toggle hamburger-menu" aria-label="Abrir menú" style="background: none; border: none; cursor: pointer; padding: 0.5rem; align-items: center">
        <svg width="24" height="24" fill="none"><use href="#icon-menu"></use></svg>
    </button>
    <a href="../controlador/InicioController.php" class="brand">
        <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
        TC-Control
    </a>
    <section class="user-actions" style="margin-left: auto;">
        <span class="text-muted">Administrador</span>
        <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
    </section>
</header>
    
<main class="dashboard-layout">
    <svg class="svg-hidden" style="display: none;">
        <symbol id="icon-menu" viewBox="0 0 24 24">
            <line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
            <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
            <line x1="3" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
        </symbol>
    </svg>

    <section class="layout-body">
        <?php $active = 'servicios'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title">Pago de Otros Servicios</h1>
                    <p class="page-subtitle">Control de gastos operativos y servicios del gimnasio.</p>
                </section>
                <section class="header-actions">
                    <section class="search-group">
                        <input type="search" id="searchInput" placeholder="Buscar concepto">
                        <section class="year-filter">
                            <button type="button" id="yearFilterBtn" class="year-select month-select">Año: <span id="selectedYearLabel"><?php echo htmlspecialchars($safeYear); ?></span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="svg-inline"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <section id="yearMenu" class="year-menu" aria-label="Seleccionar año"></section>
                        </section>
                        <a href="../controlador/PagoServicioController.php?action=nuevo" class="btn btn-primary">+ Nuevo Servicio</a>
                    </section>
                </section>
            </header>

            <?php if (!empty($mensaje) && (!isset($showMessageCard) || $showMessageCard)): ?>
                <section class="card"><article class="card-body"><?php echo $mensaje; ?></article></section>
            <?php endif; ?>

            <section class="card card-analisis">
                <header class="card-header">
                    <h2 class="card-title">Análisis de Servicios</h2>
                    <nav class="card-header-controls">
                        <button type="button" id="exportExcelBtn" class="btn btn-export">Exportar Excel</button>
                        <button type="button" id="exportPdfBtn" class="btn btn-export-secondary">Exportar PDF</button>
                    </nav>
                </header>
                <article class="card-body card-body-analisis">
                    <section class="report-summary">
                        <p class="section-subtitle">Resumen de pagos de servicios</p>
                        <ul class="resumen-list" style="list-style: none; padding: 0; display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                            <li><strong>Período:</strong> <span id="reportPeriodLabel">-</span></li>
                            <li><strong>Total gastado:</strong> <span id="reportTotalLabel">$0.00</span></li>
                            <li><strong>Registros:</strong> <span id="reportCountLabel">0</span></li>
                        </ul>
                    </section>
                    <section class="charts-container" style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
                        <section class="chart-card" style="flex: 1; min-width: 300px; max-width: 600px; height: 260px;">
                            <canvas id="barChart"></canvas>
                        </section>
                    </section>
                </article>
            </section>

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Listado de pagos de servicios</h2>
                    <section class="card-header-controls">
                        <section class="month-filter">
                            <button type="button" id="monthFilterBtn" class="month-select">Mes: <span id="selectedMonthLabel">Todos</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="svg-inline"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <section id="monthMenu" class="month-menu" aria-label="Seleccionar mes">
                                <button type="button" class="month-option" data-month="0">Todos</button>
                            </section>
                        </section>
                    </section>
                </header>
                <article class="card-body">
                    <section class="table-responsive">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Método</th>
                                    <th>Registrado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTableBody"></tbody>
                        </table>
                    </section>
                </article>
            </section>
        </article>
    </section>
</main>

<script>
(function() {
    var initialReport = {
        pagos: <?php echo json_encode($safePagos); ?>,
        serviciosMatrix: <?php echo json_encode($safeMatrix); ?>,
        year: <?php echo json_encode($safeYear); ?>,
        filters: <?php echo json_encode($safeFilters); ?>
    };

    var searchInput = document.getElementById('searchInput');
    var yearFilterBtn = document.getElementById('yearFilterBtn');
    var yearMenu = document.getElementById('yearMenu');
    var selectedYearLabel = document.getElementById('selectedYearLabel');
    var monthFilterBtn = document.getElementById('monthFilterBtn');
    var monthMenu = document.getElementById('monthMenu');
    var selectedMonthLabel = document.getElementById('selectedMonthLabel');
    var exportExcelBtn = document.getElementById('exportExcelBtn');
    var exportPdfBtn = document.getElementById('exportPdfBtn');
    var paymentsTableBody = document.getElementById('paymentsTableBody');

    var currentReport = initialReport;
    var selectedMonth = 0;
    var selectedYear = Number(initialReport.year) || (new Date()).getFullYear();
    var debounceTimer = null;

    function formatAmount(value) {
        return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0);
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>\"]+/g, function(match) {
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
            return map[match];
        });
    }

    function renderPaymentsTable(pagos) {
        if (!paymentsTableBody) return;
        if (!pagos || pagos.length === 0) {
            paymentsTableBody.innerHTML = '<tr><td colspan="6" class="empty-table-msg">No hay pagos de servicios registrados.</td></tr>';
            return;
        }
        paymentsTableBody.innerHTML = pagos.map(function(p) {
            return '<tr>' +
                '<td>' + escapeHtml(p.concepto || '') + '</td>' +
                '<td>$' + formatAmount(p.monto) + '</td>' +
                '<td>' + escapeHtml(p.fecha || '') + '</td>' +
                '<td>' + escapeHtml(p.metodo_pago || p.nombre_metodo || '') + '</td>' +
                '<td>' + escapeHtml(p.NombreAdmin || 'Sistema') + '</td>' +
                '<td>' +
                    '<section class="transactions-actions" style="display:flex; gap:10px;">' +
                        '<a href="../controlador/PagoServicioController.php?action=editar&id=' + encodeURIComponent(p.id_pago_servicio) + '" class="text-primary" title="Editar">' +
                            '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>' +
                        '</a>' +
                        '<a href="../controlador/PagoServicioController.php?action=eliminar&id=' + encodeURIComponent(p.id_pago_servicio) + '" class="text-danger" title="Eliminar" onclick="return confirm(\'¿Eliminar este pago de servicio?\');">' +
                            '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                        '</a>' +
                    '</section>' +
                '</td>' +
            '</tr>';
        }).join('');
    }

    var barChartInstance = null;

    function buildReportLabels(selectedMonth, selectedYear) {
        if (selectedMonth && selectedMonth !== 0) {
            return new Date(selectedYear, selectedMonth - 1).toLocaleString('es-ES', { month: 'long', year: 'numeric' });
        }
        return 'Año ' + selectedYear;
    }

    function summarizePagos(pagos) {
        var totalsByMethod = {};
        var totalAmount = 0;
        pagos.forEach(function(p) {
            var amount = Number(p.monto) || 0;
            var method = p.metodo_pago || 'Efectivo';
            totalsByMethod[method] = (totalsByMethod[method] || 0) + amount;
            totalAmount += amount;
        });
        return { totalAmount: totalAmount, totalCount: pagos.length, totalsByMethod: totalsByMethod };
    }

    function updateReportPreview(pagos) {
        var summary = summarizePagos(pagos);
        var reportPeriod = buildReportLabels(selectedMonth, selectedYear);

        document.getElementById('reportPeriodLabel').textContent = reportPeriod;
        document.getElementById('reportTotalLabel').textContent = '$' + formatAmount(summary.totalAmount);
        document.getElementById('reportCountLabel').textContent = summary.totalCount;

        var barLabels = Object.keys(summary.totalsByMethod);
        var barValues = barLabels.map(function(key) { return Number(summary.totalsByMethod[key] || 0); });

        var barCtx = document.getElementById('barChart');
        if (barCtx) {
            if (barChartInstance) barChartInstance.destroy();
            barChartInstance = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Servicios por método',
                        data: barValues,
                        backgroundColor: 'rgba(239, 68, 68, 0.75)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return '$' + value; } } } }
                }
            });
        }
    }

    function getFilteredPagos(report) {
        if (!report || !Array.isArray(report.pagos)) return [];
        if (!selectedMonth || selectedMonth === 0) return report.pagos;
        return report.pagos.filter(function(p) {
            var fecha = p.fecha ? new Date(p.fecha) : null;
            return fecha && (fecha.getMonth() + 1) === selectedMonth;
        });
    }

    function updatePage(data) {
        currentReport = data;
        var pagosFiltrados = getFilteredPagos(data);
        renderPaymentsTable(pagosFiltrados);
        updateReportPreview(pagosFiltrados);
    }

    function buildMonthMenu() {
        if (!monthMenu) return;
        var months = ['Todos','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        monthMenu.innerHTML = '';
        months.forEach(function(label, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'month-option' + (selectedMonth === index ? ' active' : '');
            btn.textContent = label;
            btn.addEventListener('click', function() {
                selectedMonth = index;
                selectedMonthLabel.textContent = label;
                monthMenu.classList.remove('show');
                buildMonthMenu();
                updatePage(currentReport);
            });
            monthMenu.appendChild(btn);
        });
    }

    function buildYearMenu() {
        if (!yearMenu) return;
        yearMenu.innerHTML = '';
        for (var yr = 2100; yr >= 2026; yr--) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'year-option' + (selectedYear === yr ? ' active' : '');
            btn.textContent = String(yr);
            btn.addEventListener('click', function() {
                selectedYear = Number(this.dataset.year || this.textContent);
                selectedYearLabel.textContent = String(selectedYear);
                yearMenu.classList.remove('show');
                buildYearMenu();
                selectedMonth = 0;
                selectedMonthLabel.textContent = 'Todos';
                buildMonthMenu();
                fetchReport();
            });
            yearMenu.appendChild(btn);
        }
    }

    function fetchReport() {
        var query = new URLSearchParams({ q: searchInput ? searchInput.value.trim() : '', year: selectedYear, ajax: '1' }).toString();
        fetch('../controlador/PagoServicioController.php?' + query)
            .then(function(res) { return res.json(); })
            .then(function(data) { updatePage(data); });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchReport, 350);
        });
    }

    if (yearFilterBtn) yearFilterBtn.addEventListener('click', function(e) { e.stopPropagation(); yearMenu.classList.toggle('show'); });
    if (monthFilterBtn) monthFilterBtn.addEventListener('click', function(e) { e.stopPropagation(); monthMenu.classList.toggle('show'); });
    document.addEventListener('click', function() { if(yearMenu) yearMenu.classList.remove('show'); if(monthMenu) monthMenu.classList.remove('show'); });

    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function() {
            var pagos = getFilteredPagos(currentReport);
            var rows = [['Concepto', 'Monto', 'Fecha', 'Método', 'Registrado por']];
            pagos.forEach(function(p) { rows.push([p.concepto, parseFloat(p.monto), p.fecha, p.metodo_pago, p.NombreAdmin || 'Sistema']); });
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rows), 'Servicios');
            XLSX.writeFile(wb, 'reporte-servicios.xlsx');
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function() {
            var pagos = getFilteredPagos(currentReport);
            var jsPDF = window.jspdf.jsPDF;
            var doc = new jsPDF();
            doc.text('Reporte de Servicios', 14, 16);
            var body = pagos.map(function(p) { return [p.concepto, '$' + formatAmount(p.monto), p.fecha, p.metodo_pago, p.NombreAdmin || 'Sistema']; });
            doc.autoTable({ startY: 24, head: [['Concepto', 'Monto', 'Fecha', 'Método', 'Registrado por']], body: body });
            doc.save('reporte-servicios.pdf');
        });
    }

    buildYearMenu();
    buildMonthMenu();
    updatePage(initialReport);
})();
</script>
</body>
</html>