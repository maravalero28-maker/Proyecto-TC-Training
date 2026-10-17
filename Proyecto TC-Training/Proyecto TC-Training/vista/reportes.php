<?php require_once(__DIR__ . '/partes/header.php'); ?>

<main class="principal-reportes">
    <section class="seccion-reportes">
        <h2>Módulo de Generación de Reportes</h2>
        
        <form action="../controlador/Reportecontrolador.php?action=generar" method="POST" class="formulario-reportes">
            
            <fieldset class="grupo-campo">
                <label for="tipo_reporte">Seleccione el Tipo de Reporte:</label>
                <select name="tipo_reporte" id="tipo_reporte" required>
                    <option value="movimientos">Trazabilidad de Accesos (Movimientos)</option>
                    <option value="usuarios">Padrón de Usuarios</option>
                </select>
            </fieldset>

            <fieldset class="grupo-campo">
                <legend>Parámetros de Fechas (Para Movimientos)</legend>
                
                <p>
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo date('Y-m-01'); ?>">
                </p>

                <p>
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                </p>
            </fieldset>

            <fieldset class="grupo-campo">
                <legend>Filtros Opcionales (Para Usuarios)</legend>
                
                <p>
                    <label for="rol">Filtrar por Rol:</label>
                    <input type="text" name="rol" id="rol" placeholder="Ej: Visitante, Personal...">
                </p>

                <p>
                    <label for="activo">Estatus:</label>
                    <select name="activo" id="activo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </p>
            </fieldset>

            <button type="submit" class="boton-enviar">Generar Reporte</button>
        </form>
    </section>
</main>

<?php require_once(__DIR__ . '/partes/footer.php'); ?>