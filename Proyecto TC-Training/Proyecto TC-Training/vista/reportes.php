<?php
// Mensaje (éxito/error) que deja el controlador tras intentar enviar un correo
$flash = $_SESSION['reporte_msg'] ?? null;
unset($_SESSION['reporte_msg']);

// Datos del correo emisor (los entrega el controlador; valores por defecto si no llegan)
$configCorreo = $configCorreo ?? [
    'configurado' => false, 'host' => 'smtp.gmail.com', 'puerto' => 587,
    'seguridad' => 'tls', 'usuario' => '', 'remitente_nombre' => 'TC-Control',
];
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
    <style>
        /* Círculo de carga junto al botón de envío por correo */
        .spinner-envio { align-items: center; gap: .5rem; margin-left: .75rem; vertical-align: middle; }
        .spinner-circulo {
            width: 1.1rem; height: 1.1rem; border-radius: 50%;
            border: 3px solid #d1d5db; border-top-color: #b00000;
            animation: girar-envio .8s linear infinite;
        }
        @keyframes girar-envio { to { transform: rotate(360deg); } }
    </style>
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

                <?php if ($flash): ?>
                    <?php if ($flash['tipo'] === 'ok'): ?>
                        <aside class="reportes-alerta" role="status"
                               style="background:#e6f7ee;border:1px solid #2e9e5b;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem;">
                            <p><?php echo htmlspecialchars($flash['texto']); ?></p>
                        </aside>
                    <?php else: ?>
                        <aside class="alerta-advertencia reportes-alerta" role="alert">
                            <p><?php echo htmlspecialchars($flash['texto']); ?></p>
                        </aside>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="../controlador/Reportecontrolador.php?action=generar" method="POST" class="formulario-reportes" id="form-reportes">

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
                                <option value="Binance">Binance</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="PagoMovil">PagoMóvil</option>
                                <option value="Paypal">PayPal</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Zelle">Zelle</option>
                            </select>
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
                            <select name="formato" id="formato" onchange="toggleCorreo(this.value)">
                                <option value="html">Visualizar en Pantalla / Descargar PDF</option>
                                <option value="excel">Descargar Excel (CSV)</option>
                                <option value="correo">Enviar por correo electrónico</option>
                            </select>
                        </p>
                    </fieldset>

                    <fieldset class="grupo-campo reportes-fieldset is-hidden" id="seccion-correo">
                        <legend>5. Envío por Correo</legend>
                        <p>
                            <label for="correo_destino">Correo destinatario:</label>
                            <input type="email" name="correo_destino" id="correo_destino"
                                   placeholder="director@empresa.com" maxlength="254" autocomplete="email">
                        </p>
                        <p>
                            <label><input type="checkbox" name="adjuntos[]" value="pdf" checked> Adjuntar PDF</label>
                            <label><input type="checkbox" name="adjuntos[]" value="xlsx"> Adjuntar Excel (.xlsx)</label>
                        </p>
                        <p class="text-muted">El correo llega con un asunto claro y un resumen con los indicadores clave (KPIs) del reporte.</p>
                        <?php if (!$configCorreo['configurado']): ?>
                            <p><strong>Antes de enviar:</strong> configura el correo emisor en la sección "Configuración del correo emisor" que está debajo del formulario.</p>
                        <?php endif; ?>
                    </fieldset>

                    <button type="submit" class="btn btn-primary" id="btn-generar">Generar reporte</button>
                    <span id="spinner-envio" class="spinner-envio" style="display:none;" role="status" aria-live="polite">
                        <span class="spinner-circulo" aria-hidden="true"></span> Enviando correo...
                    </span>
                </form>

                <!-- Configuración del correo emisor (SMTP), se guarda en la base de datos -->
                <details class="grupo-campo reportes-fieldset" style="margin-top:1.5rem;" <?php echo $configCorreo['configurado'] ? '' : 'open'; ?>>
                    <summary>
                        <strong>Configuración del correo emisor</strong>
                        — <?php echo $configCorreo['configurado']
                            ? 'Configurado con ' . htmlspecialchars($configCorreo['usuario'])
                            : 'Sin configurar'; ?>
                    </summary>

                    <form action="../controlador/Reportecontrolador.php?action=guardarConfig" method="POST" class="formulario-reportes">
                        <p class="text-muted">
                            Con Gmail: activa la verificación en 2 pasos y crea una
                            <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">contraseña de aplicación</a>.
                            Pégala aquí tal como te la da Google (con o sin espacios).
                        </p>
                        <p>
                            <label for="cfg_usuario">Correo emisor (Gmail):</label>
                            <input type="email" name="cfg_usuario" id="cfg_usuario" required maxlength="190"
                                   value="<?php echo htmlspecialchars($configCorreo['usuario']); ?>" autocomplete="off">
                        </p>
                        <p>
                            <label for="cfg_clave">Contraseña de aplicación:</label>
                            <input type="password" name="cfg_clave" id="cfg_clave" autocomplete="new-password"
                                   <?php echo $configCorreo['configurado'] ? '' : 'required'; ?>
                                   placeholder="<?php echo $configCorreo['configurado'] ? 'Guardada (déjala vacía para conservarla)' : 'xxxx xxxx xxxx xxxx'; ?>">
                        </p>
                        <p>
                            <label for="cfg_nombre">Nombre del remitente:</label>
                            <input type="text" name="cfg_nombre" id="cfg_nombre" maxlength="100"
                                   value="<?php echo htmlspecialchars($configCorreo['remitente_nombre']); ?>">
                        </p>

                        <details>
                            <summary>Opciones avanzadas (servidor SMTP)</summary>
                            <p>
                                <label for="cfg_host">Servidor:</label>
                                <input type="text" name="cfg_host" id="cfg_host" maxlength="150"
                                       value="<?php echo htmlspecialchars($configCorreo['host']); ?>">
                                <label for="cfg_puerto">Puerto:</label>
                                <input type="number" name="cfg_puerto" id="cfg_puerto" min="1" max="65535"
                                       value="<?php echo (int) $configCorreo['puerto']; ?>">
                                <label for="cfg_seguridad">Seguridad:</label>
                                <select name="cfg_seguridad" id="cfg_seguridad">
                                    <option value="tls" <?php echo $configCorreo['seguridad'] === 'tls' ? 'selected' : ''; ?>>TLS (puerto 587)</option>
                                    <option value="ssl" <?php echo $configCorreo['seguridad'] === 'ssl' ? 'selected' : ''; ?>>SSL (puerto 465)</option>
                                </select>
                            </p>
                        </details>

                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </details>
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
function toggleCorreo(val) {
    var esCorreo = (val === 'correo');
    document.getElementById('seccion-correo').classList.toggle('is-hidden', !esCorreo);
    // "required" solo cuando el campo está visible; si no, el navegador bloquea el envío
    document.getElementById('correo_destino').required = esCorreo;
    document.getElementById('btn-generar').textContent = esCorreo ? 'Enviar reporte por correo' : 'Generar reporte';
}
toggleFiltros(document.getElementById('modulo').value);
toggleCorreo(document.getElementById('formato').value);

// Al enviar por correo: muestra el círculo de carga y evita doble clic mientras se envía
document.getElementById('form-reportes').addEventListener('submit', function () {
    if (document.getElementById('formato').value !== 'correo') { return; }
    var btn = document.getElementById('btn-generar');
    document.getElementById('spinner-envio').style.display = 'inline-flex';
    btn.textContent = 'Enviando...';
    setTimeout(function () { btn.disabled = true; }, 0);
});

// Si el usuario vuelve atrás con el navegador, se restablece el botón
window.addEventListener('pageshow', function () {
    document.getElementById('spinner-envio').style.display = 'none';
    document.getElementById('btn-generar').disabled = false;
    toggleCorreo(document.getElementById('formato').value);
});
</script>

</body>
</html>
