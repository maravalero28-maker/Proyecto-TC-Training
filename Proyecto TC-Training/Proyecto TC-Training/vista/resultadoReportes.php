<?php require_once(__DIR__ . '/partes/header.php'); ?>

<main class="principal-reportes">
	<section class="seccion-reportes">
		<h2><?php echo htmlspecialchars($titulo ?? 'Resultados del Reporte'); ?></h2>

		<?php if (!empty($datos) && is_array($datos)): ?>
			<div class="table-responsive">
				<table class="tabla-reportes">
					<thead>
						<tr>
							<?php foreach (array_keys($datos[0]) as $col): ?>
								<th><?php echo htmlspecialchars($col); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($datos as $fila): ?>
							<tr>
								<?php foreach ($fila as $valor): ?>
									<td><?php echo htmlspecialchars($valor); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else: ?>
			<p>No se encontraron datos para el reporte.</p>
		<?php endif; ?>

		<p><a href="../controlador/Reportecontrolador.php" class="link-back">&larr; Volver</a></p>
	</section>
</main>

<?php require_once(__DIR__ . '/partes/footer.php'); ?>
