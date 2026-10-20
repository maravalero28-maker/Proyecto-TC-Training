<?php
// Opcional: Validación por si el controlador requiere enviar datos base
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reportes | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>

<section id="toast-container" aria-live="polite" aria-atomic="true"></section>

<!-- 1. LA BARRA SUPERIOR (HEADER) QUE FALTABA -->
<header class="topbar">
    <button id="hamburger-btn" class="hamburger-menu" aria-label="Abrir menú">☰</button>
    <a href="../controlador/InicioController.php" class="brand">
        <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
        TC-Control
    </a>
    <section class="user-actions">
        <span class="text-muted">Administrador</span>
        <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
    </section>
</header>

<!-- 2. LA ESTRUCTURA DE LAYOUT GENERAL IGUAL A INICIO -->
<main class="dashboard-layout">
    <section class="layout-body">
       <?php $active = 'reportes'; include __DIR__ . '/partes/sidebar.php'; ?>

        <!-- 3. CONTENIDO PRINCIPAL DE REPORTES -->
        <article class="main-content">
            <section class="card reportes-card">
                <h2>Centro de Generación de Reportes Específicos</h2>
                
                <form action="../controlador/Reportecontrolador.php?action=generar" method="POST" class="formulario-reportes">
                    
                    <fieldset class="grupo-campo reportes-fieldset">
                        <legend>1. Módulo del Reporte</legend>
                        <p>
                            <label for="modulo">Seleccione el área:</label>
                            <select name="modulo" id="modulo" required onchange="toggleFiltros(this.value)">
                                <option value="pagos_clientes">Pagos - Clientes</option>
                                <option value="pagos_nomina">Pagos - Nómina</option>
                                <option value="pagos_servicios">Pagos - Servicios</option>
                                <option value="clientes">Gestión de Clientes (Solvencia)</option>
                            </select>
                        </p>
                    </fieldset>

                    <fieldset class="grupo-campo reportes-fieldset" id="filtros-pagos">
                        <legend>2. Filtros de Pagos</legend>
                        <p>
                            <label for="fecha_inicio">Fecha Inicio:</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio">
                            <label for="fecha_fin">Fecha Fin:</label>
                            <input type="date" name="fecha_fin" id="fecha_fin">
                        </p>
                        <p>
                            <label for="metodo_pago">Método de Pago:</label>
                            <select name="metodo_pago" id="metodo_pago">
                                <option value="">Todos</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Zelle">Zelle</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Binance">Binance</option>
                            </select>
                        </p>
                        <p>
                            <label for="banco">Banco / Cuenta Receptora:</label>
                            <input type="text" name="banco" id="banco" placeholder="Ej: Banco Mercantil, Banesco...">
                        </p>
                    </fieldset>

                    <fieldset class="grupo-campo reportes-fieldset is-hidden" id="filtros-clientes">
                        <legend>2. Filtros de Clientes</legend>
                        <p>
                            <label for="estatus_financiero">Estatus Financiero / Solvencia:</label>
                            <select name="estatus_financiero" id="estatus_financiero">
                                <option value="">Todos</option>
                                <option value="solvente">Solventes</option>
                                <option value="insolvente">Insolventes / Con deudas pendientes</option>
                            </select>
                        </p>
                    </fieldset>

                    <fieldset class="grupo-campo reportes-fieldset">
                        <legend>3. Nivel de Detalle de los Datos</legend>
                        <p>
                            <label><input type="radio" name="nivel_detalle" value="general" checked onclick="toggleColumnas(false)"> Reporte general (todas las columnas)</label><br>
                            <label><input type="radio" name="nivel_detalle" value="resumido" onclick="toggleColumnas(false)"> Reporte resumido (solo datos esenciales)</label><br>
                            <label><input type="radio" name="nivel_detalle" value="personalizado" onclick="toggleColumnas(true)"> Personalizado (Elegir datos específicos)</label>
                        </p>

                        <fieldset id="seleccion-columnas" class="seleccion-columnas is-hidden">
                            <legend>Seleccione las columnas a incluir:</legend>
                            <label><input type="checkbox" name="columnas[]" value="cedula"> Cédula</label>
                            <label><input type="checkbox" name="columnas[]" value="nombre"> Nombre</label>
                            <label><input type="checkbox" name="columnas[]" value="monto"> Monto</label>
                            <label><input type="checkbox" name="columnas[]" value="metodo_pago"> Método de Pago</label>
                            <label><input type="checkbox" name="columnas[]" value="banco_receptor"> Banco</label>
                            <label><input type="checkbox" name="columnas[]" value="fecha_pago"> Fecha</label>
                            <label><input type="checkbox" name="columnas[]" value="estatus_solvencia"> Solvencia</label>
                        </fieldset>
                    </fieldset>

                    <fieldset class="grupo-campo reportes-fieldset">
                        <legend>4. Formato de Exportación</legend>
                        <p>
                            <select name="formato" id="formato">
                                <option value="html">Visualizar en Pantalla / Descargar PDF</option>
                                <option value="excel">Descargar Excel (CSV)</option>
                            </select>
                        </p>
                    </fieldset>

                    <button type="submit" class="btn btn-primary">Generar reporte</button>
                </form>
            </section>
        </article>
    </section>
</main>

<script>
function toggleFiltros(val) {
    if (val.includes('pagos')) {
        document.getElementById('filtros-pagos').classList.remove('is-hidden');
        document.getElementById('filtros-clientes').classList.add('is-hidden');
    } else {
        document.getElementById('filtros-pagos').classList.add('is-hidden');
        document.getElementById('filtros-clientes').classList.remove('is-hidden');
    }
}
function toggleColumnas(mostrar) {
    document.getElementById('seleccion-columnas').classList.toggle('is-hidden', !mostrar);
}
toggleFiltros(document.getElementById('modulo').value);
</script>

</body>
</html>