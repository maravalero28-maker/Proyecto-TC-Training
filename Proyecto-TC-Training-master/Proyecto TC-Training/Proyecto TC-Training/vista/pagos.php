<?php
if (!isset($pagos) || !isset($filtro)) {
    header("Location: ../controlador/pagoscontrolador.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pagos | TC-Control</title>
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
            <svg width="24" height="24" fill="none" style="pointer-events: none;">
                <use href="#icon-menu"></use>
            </svg>
        </button>

        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            <?php echo htmlspecialchars($config['nombre_empresa'] ?? 'TC-Control'); ?>
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
        <?php $active = 'pagos'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title">Pagos registrados</h1>
                    <p class="page-subtitle">Buscar pagos y exportar registros con selección de año rápida.</p>
                </section>
                <section class="header-actions">
                    <section class="search-group">
                        <input type="search" id="searchInput" placeholder="Buscar cliente">
                        <section class="year-filter">
                            <button type="button" id="yearFilterBtn" class="year-select month-select">Año: <span id="selectedYearLabel"><?php echo htmlspecialchars($year); ?></span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="svg-inline"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <section id="yearMenu" class="year-menu" aria-label="Seleccionar año"></section>
                        </section>
                        
                        <a href="../controlador/pagoscontrolador.php?action=nuevo" class="btn btn-primary">+ Nuevo Pago</a>
                    </section>
                </section>
            </header>

            <?php if (!empty($mensaje) && (!isset($showMessageCard) || $showMessageCard)): ?>
                <section class="card">
                    <article class="card-body">
                        <?php echo $mensaje; ?>
                    </article>
                </section>
            <?php endif; ?>

            <!-- NUEVA SECCIÓN: Resumen y Gráficos -->
            <section class="card card-analisis">
                <header class="card-header">
                    <h2 class="card-title">Análisis de Pagos</h2>
                    <nav class="card-header-controls">
                        <button type="button" id="exportExcelBtn" class="btn btn-export">Exportar Excel</button>
                        <button type="button" id="exportPdfBtn" class="btn btn-export-secondary">Exportar PDF</button>
                    </nav>
                </header>
                <article class="card-body card-body-analisis">
                    <section class="report-summary">
                        <p class="section-subtitle">Resumen de filtros actuales</p>
                        <ul class="resumen-list" style="list-style: none; padding: 0; display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                            <li><strong>Período:</strong> <span id="reportPeriodLabel">-</span></li>
                            <li><strong>Filtros:</strong> <span id="reportFiltersLabel">Todos</span></li>
                            <li><strong>Método de pago:</strong> <span id="reportMethodLabel">Todos</span></li>
                            <li><strong>Total ingresos:</strong> <span id="reportTotalLabel">$0.00</span></li>
                            <li><strong>Pagos registrados:</strong> <span id="reportCountLabel">0</span></li>
                        </ul>
                    </section>
                    <section class="charts-container" style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: space-between;">
                        <section class="chart-card" style="flex: 1; min-width: 300px; max-width: 50%; height: 250px;">
                            <canvas id="barChart"></canvas>
                        </section>
                        <section class="chart-card" style="flex: 1; min-width: 300px; max-width: 50%; height: 250px;">
                            <canvas id="pieChart"></canvas>
                        </section>
                    </section>
                </article>
            </section>
            <!-- FIN NUEVA SECCIÓN -->

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Listado de pagos</h2>
                    <section class="card-header-controls">
                        <section class="month-filter">
                            <button type="button" id="monthFilterBtn" class="month-select">Mes: <span id="selectedMonthLabel">Todos</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="svg-inline"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Método</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTableBody"></tbody>
                        </table>
                    </section>
                </article>
            </section>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Matriz de Pagos - <?php echo htmlspecialchars($year); ?></h2></header>
                <article class="card-body">
                    <section class="table-responsive">
                        <section id="paymentsMatrixContainer"></section>
                    </section>
                </article>
            </section>
        </article>
    </section>
</main>

<script>
(function() {
    const initialReport = {
        pagos: <?php echo json_encode($pagos ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        clientsList: <?php echo json_encode($clientsList ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        paymentsMatrix: <?php echo json_encode($paymentsMatrix ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        year: <?php echo json_encode($year ?? date('Y')); ?>,
        filters: <?php echo json_encode($filters ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        solvencyDistribution: <?php echo json_encode($solvencyDistribution ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>
    };

    const form = document.getElementById('filterForm');
    const searchInput = document.getElementById('searchInput');
    const yearFilterBtn = document.getElementById('yearFilterBtn');
    const yearMenu = document.getElementById('yearMenu');
    const selectedYearLabel = document.getElementById('selectedYearLabel');
    const monthFilterBtn = document.getElementById('monthFilterBtn');
    const monthMenu = document.getElementById('monthMenu');
    const selectedMonthLabel = document.getElementById('selectedMonthLabel');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const paymentsTableBody = document.getElementById('paymentsTableBody');
    const paymentsMatrixContainer = document.getElementById('paymentsMatrixContainer');

    let currentReport = initialReport;
    let selectedMonth = 0;
    let selectedYear = Number(initialReport.year) || (new Date()).getFullYear();
    let debounceTimer = null;

    function formatAmount(value) {
        return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0);
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>\"]+/g, function(match) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
            return map[match];
        });
    }

    function renderPaymentsTable(pagos) {
        if (!paymentsTableBody) return;
        if (!pagos || pagos.length === 0) {
            paymentsTableBody.innerHTML = '<tr><td colspan="5" class="empty-table-msg">No hay pagos registrados.</td></tr>';
            return;
        }
        paymentsTableBody.innerHTML = pagos.map(p => `
            <tr>
                <td>${escapeHtml((p.NombreCliente || '').replace(/\s+/g, ' '))}</td>
                <td>$${formatAmount(p.Monto)}</td>
                <td>${escapeHtml(p.Fecha || '')}</td>
                <td>${escapeHtml(p.MetodoPago || '')}</td>
                <td>
                    <section class="transactions-actions" style="display:flex; gap:10px;">
                        <a href="../controlador/pagoscontrolador.php?action=editar&id=${encodeURIComponent(p.id_pago)}" class="text-primary" title="Editar pago">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <a href="../controlador/pagoscontrolador.php?action=eliminar&id=${encodeURIComponent(p.id_pago)}" class="text-danger" title="Eliminar pago" onclick="return confirm('¿Eliminar este pago?');">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </a>
                    </section>
                </td>
            </tr>
        `).join('');
    }

    function renderPaymentsMatrix(data) {
        if (!paymentsMatrixContainer) return;
        const clients = data.clientsList || [];
        const matrix = data.paymentsMatrix || {};
        if (!clients.length) {
            paymentsMatrixContainer.innerHTML = '<p>No hay clientes para mostrar la matriz.</p>';
            return;
        }
        let html = '<table class="payments-matrix">';
        html += '<thead><tr><th>Cliente</th>';
        for (let m = 1; m <= 12; m += 1) {
            const month = new Date(0, m - 1).toLocaleString('es-ES', { month: 'short' });
            html += `<th title="${month}">${month}</th>`;
        }
        html += '</tr></thead><tbody>';
        clients.forEach(client => {
            html += '<tr>';
            html += `<td>${escapeHtml((client.nombre_completo || '').trim())}</td>`;
            for (let m = 1; m <= 12; m += 1) {
                const cell = matrix[client.id_cliente] && matrix[client.id_cliente][m];
                if (cell) {
                    const count = Number(cell.count) || 0;
                    const total = Number(cell.total) || 0;
                    const labelCount = count === 1 ? '1 pago' : `${count} pagos`;
                    html += `<td><section class="month-paid"><section class="month-paid-amount">$${formatAmount(total)}</section><section class="month-paid-count" style="font-size:0.75rem; color:#6b7280;">(${escapeHtml(labelCount)})</section></section></td>`;
                } else {
                    html += '<td><span class="month-empty">-</span></td>';
                }
            }
            html += '</tr>';
        });
        html += '</tbody></table>';
        paymentsMatrixContainer.innerHTML = html;
    }

    let barChartInstance = null;
    let pieChartInstance = null;

    function buildReportLabels(filters, selectedMonth, selectedYear) {
        const labels = [];
        if (filters.start_date || filters.end_date) {
            if (filters.start_date && filters.end_date) {
                labels.push(`${filters.start_date} → ${filters.end_date}`);
            } else if (filters.start_date) {
                labels.push(`Desde ${filters.start_date}`);
            } else if (filters.end_date) {
                labels.push(`Hasta ${filters.end_date}`);
            }
        } else if (selectedMonth && selectedMonth !== 0) {
            const monthName = new Date(selectedYear, selectedMonth - 1).toLocaleString('es-ES', { month: 'long' });
            labels.push(`${monthName} ${selectedYear}`);
        } else {
            labels.push(`Año ${selectedYear}`);
        }
        return labels.join(' | ');
    }

    function summarizePagos(pagos) {
        const totalsByMethod = {};
        const uniqueClients = new Map();
        let totalAmount = 0;

        pagos.forEach(p => {
            const amount = Number(p.Monto) || 0;
            const method = p.MetodoPago || 'Otros';
            totalsByMethod[method] = (totalsByMethod[method] || 0) + amount;
            totalAmount += amount;
            const clientKey = `${p.id_cliente || p.NombreCliente || ''}::${p.Solvencia || 'No definido'}`;
            if (!uniqueClients.has(clientKey)) {
                uniqueClients.set(clientKey, p.Solvencia || 'No definido');
            }
        });

        const clientsBySolvency = {};
        uniqueClients.forEach(sol => {
            clientsBySolvency[sol] = (clientsBySolvency[sol] || 0) + 1;
        });

        return {
            totalAmount,
            totalCount: pagos.length,
            totalsByMethod,
            clientsBySolvency
        };
    }

    function updateReportPreview(pagos) {
        const summary = summarizePagos(pagos);
        const reportPeriod = buildReportLabels(currentReport.filters || {}, selectedMonth, selectedYear);
        const filtroLabel = [];
        
        if (currentReport.filters && currentReport.filters.solvencia && currentReport.filters.solvencia !== 'todos') {
            filtroLabel.push(`Solvencia: ${currentReport.filters.solvencia}`);
        }
        if (currentReport.filters && currentReport.filters.plan && currentReport.filters.plan !== 'todos') {
            filtroLabel.push(`Plan: ${currentReport.filters.plan}`);
        }
        if (currentReport.filters && currentReport.filters.metodo_pago && currentReport.filters.metodo_pago !== 'todos') {
            filtroLabel.push(`Método: ${currentReport.filters.metodo_pago}`);
        }

        // Elementos DOM (Protegidos con condicionales por seguridad)
        const periodLabel = document.getElementById('reportPeriodLabel');
        const filtersLabel = document.getElementById('reportFiltersLabel');
        const methodLabel = document.getElementById('reportMethodLabel');
        const totalLabel = document.getElementById('reportTotalLabel');
        const countLabel = document.getElementById('reportCountLabel');

        if (periodLabel) periodLabel.textContent = reportPeriod;
        if (filtersLabel) filtersLabel.textContent = filtroLabel.length ? filtroLabel.join(' · ') : 'Todos';
        if (methodLabel) methodLabel.textContent = Object.keys(summary.totalsByMethod).join(', ') || 'Todos';
        if (totalLabel) totalLabel.textContent = `$${formatAmount(summary.totalAmount)}`;
        if (countLabel) countLabel.textContent = summary.totalCount;

        const barLabels = Object.keys(summary.totalsByMethod);
        const barValues = barLabels.map(key => Number(summary.totalsByMethod[key] || 0));
        const pieLabels = Object.keys(currentReport.solvencyDistribution || summary.clientsBySolvency);
        const pieValues = pieLabels.map(label => Number((currentReport.solvencyDistribution || summary.clientsBySolvency)[label] || 0));

        const barCtx = document.getElementById('barChart');
        const pieCtx = document.getElementById('pieChart');

        if (barCtx) {
            if (barChartInstance) barChartInstance.destroy();
            barChartInstance = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Ingresos por método',
                        data: barValues,
                        backgroundColor: barLabels.map(() => 'rgba(59, 130, 246, 0.75)'),
                        borderColor: barLabels.map(() => 'rgba(59, 130, 246, 1)'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: value => '$' + value } }
                    }
                }
            });
        }

        if (pieCtx) {
            if (pieChartInstance) pieChartInstance.destroy();
            pieChartInstance = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieValues,
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
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }

    function getBase64Chart(chartInstance) {
        return chartInstance ? chartInstance.toBase64Image() : null;
    }

    function getFilterSummary() {
        const filters = currentReport.filters || {};
        return {
            periodo: buildReportLabels(filters, selectedMonth, selectedYear),
            solvencia: filters.solvencia && filters.solvencia !== 'todos' ? filters.solvencia : 'Todos',
            plan: filters.plan && filters.plan !== 'todos' ? filters.plan : 'Todos',
            metodo: filters.metodo_pago && filters.metodo_pago !== 'todos' ? filters.metodo_pago : 'Todos'
        };
    }

    function getFiltersQuery() {
        const params = new URLSearchParams();
        if (searchInput && searchInput.value) params.set('q', searchInput.value.trim());
        if (selectedYear) params.set('year', String(selectedYear));
        params.set('ajax', '1');
        params.set('filter', '<?php echo addslashes($filtro ?? ''); ?>');
        return params.toString();
    }

    function getFilteredPagos(report) {
        if (!report || !Array.isArray(report.pagos)) return [];
        if (!selectedMonth || selectedMonth === 0) {
            return report.pagos;
        }
        return report.pagos.filter(p => {
            const fecha = p.Fecha ? new Date(p.Fecha) : null;
            return fecha && (fecha.getMonth() + 1) === selectedMonth;
        });
    }

    function updatePage(data) {
        currentReport = data;
        const pagosFiltrados = getFilteredPagos(data);
        renderPaymentsTable(pagosFiltrados);
        renderPaymentsMatrix(data);
        updateReportPreview(pagosFiltrados);
    }

    function buildMonthMenu() {
        if (!monthMenu) return;
        const months = ['Todos','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        monthMenu.innerHTML = '';
        months.forEach((label, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'month-option' + (selectedMonth === index ? ' active' : '');
            btn.dataset.month = index;
            btn.textContent = label;
            btn.addEventListener('click', () => {
                selectedMonth = index;
                if(selectedMonthLabel) selectedMonthLabel.textContent = label;
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
        for (let yr = 2100; yr >= 2026; yr--) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'year-option' + (selectedYear === yr ? ' active' : '');
            btn.dataset.year = yr;
            btn.textContent = String(yr);
            btn.addEventListener('click', () => {
                selectedYear = yr;
                if (selectedYearLabel) selectedYearLabel.textContent = String(yr);
                yearMenu.classList.remove('show');
                buildYearMenu();
                resetMonthSelection();
                fetchReport();
            });
            yearMenu.appendChild(btn);
        }
    }

    function resetMonthSelection() {
        selectedMonth = 0;
        if (selectedMonthLabel) selectedMonthLabel.textContent = 'Todos';
        if (monthMenu) buildMonthMenu();
    }

    function exportToExcel(pagos) {
        if (!pagos || !pagos.length) return;
        const summary = summarizePagos(pagos);
        const filterSummary = getFilterSummary();
        const rows = [['Cliente', 'Monto', 'Fecha', 'Método', 'Plan', 'Solvencia']];
        pagos.forEach(p => rows.push([
            p.NombreCliente || '',
            parseFloat(p.Monto || 0),
            p.Fecha || '',
            p.MetodoPago || '',
            p.Plan || '',
            p.Solvencia || ''
        ]));

        const summaryRows = [
            ['Reporte', 'Valor'],
            ['Período', filterSummary.periodo],
            ['Solvencia', filterSummary.solvencia],
            ['Plan', filterSummary.plan],
            ['Método de pago', filterSummary.metodo],
            ['Pagos registrados', summary.totalCount],
            ['Total de ingresos', `$${formatAmount(summary.totalAmount)}`]
        ];

        const methodRows = [['Método de pago', 'Ingresos']];
        Object.entries(summary.totalsByMethod).forEach(([method, amount]) => {
            methodRows.push([method, amount]);
        });

        const solvencyRows = [['Estado de solvencia', 'Clientes']];
        const distribution = currentReport.solvencyDistribution || summary.clientsBySolvency;
        Object.entries(distribution).forEach(([status, value]) => {
            solvencyRows.push([status, value]);
        });

        const dailyTotals = {};
        pagos.forEach(p => {
            if (!p.Fecha) return;
            dailyTotals[p.Fecha] = (dailyTotals[p.Fecha] || 0) + Number(p.Monto || 0);
        });
        const dailyRows = [['Fecha', 'Ingresos']];
        Object.keys(dailyTotals).sort((a, b) => new Date(a) - new Date(b)).forEach(fecha => {
            dailyRows.push([fecha, dailyTotals[fecha]]);
        });

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rows), 'Pagos');
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryRows), 'Resumen');
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(methodRows), 'Ingresos por método');
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(solvencyRows), 'Solvencia');
        if (dailyRows.length > 1) {
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dailyRows), 'Ingresos diarios');
        }
        XLSX.writeFile(wb, 'reporte-pagos.xlsx');
    }

    function exportToPDF(pagos) {
        if (!pagos || !pagos.length) return;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });
        const filterSummary = getFilterSummary();
        const summary = summarizePagos(pagos);

        doc.setFontSize(14);
        doc.text('Reporte de Pagos', 14, 16);
        doc.setFontSize(10);
        doc.text(`Período: ${filterSummary.periodo}`, 14, 24);
        doc.text(`Solvencia: ${filterSummary.solvencia}`, 14, 30);
        doc.text(`Plan: ${filterSummary.plan}`, 14, 36);
        doc.text(`Método de pago: ${filterSummary.metodo}`, 14, 42);
        doc.text(`Pagos registrados: ${summary.totalCount}`, 14, 48);
        doc.text(`Total ingresos: $${formatAmount(summary.totalAmount)}`, 14, 54);

        const barImage = getBase64Chart(barChartInstance);
        const pieImage = getBase64Chart(pieChartInstance);
        let imageY = 62;
        if (barImage) doc.addImage(barImage, 'PNG', 14, imageY, 120, 70);
        if (pieImage) doc.addImage(pieImage, 'PNG', 140, imageY, 120, 70);

        doc.addPage();
        const body = pagos.map(p => [
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
        doc.save('reporte-pagos.pdf');
    }

    function fetchReport() {
        const query = getFiltersQuery();
        fetch(`../controlador/pagoscontrolador.php?${query}`)
            .then(response => response.json())
            .then(data => updatePage(data))
            .catch(error => {
                console.error('Error fetching pagos:', error);
                if (typeof mostrarToast === 'function') {
                    mostrarToast('Error al cargar los datos.', 'error');
                }
            });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchReport, 350);
        });
    }

    if (yearFilterBtn) {
        yearFilterBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            if (yearMenu) {
                yearMenu.classList.toggle('show');
                if (yearMenu.classList.contains('show')) {
                    const active = yearMenu.querySelector('.year-option.active');
                    if (active) active.scrollIntoView({block:'center'});
                }
            }
        });
    }

    if (yearMenu) {
        document.addEventListener('click', function(event) {
            if (!yearMenu.contains(event.target) && event.target !== yearFilterBtn) {
                yearMenu.classList.remove('show');
            }
        });
    }

    if (monthFilterBtn) {
        monthFilterBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            if (monthMenu) {
                monthMenu.classList.toggle('show');
                if (monthMenu.classList.contains('show')) {
                    const active = monthMenu.querySelector('.month-option.active');
                    if (active) active.scrollIntoView({block:'center'});
                }
            }
        });
    }

    if (monthMenu) {
        document.addEventListener('click', function(event) {
            if (!monthMenu.contains(event.target) && event.target !== monthFilterBtn) {
                monthMenu.classList.remove('show');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchReport();
        });
    }

    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function() {
            exportToExcel(getFilteredPagos(currentReport || initialReport));
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function() {
            exportToPDF(getFilteredPagos(currentReport || initialReport));
        });
    }

    if (selectedYearLabel) selectedYearLabel.textContent = String(selectedYear);
    buildYearMenu();
    buildMonthMenu();
    resetMonthSelection();
    updatePage(initialReport);
})();
</script>
</body>
</html>