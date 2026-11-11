<?php
// Código de país que se antepone a los teléfonos guardados sin él.
// Ejemplos: Venezuela = '58', Colombia = '57', México = '52', España = '34'.
// Un teléfono guardado como 04141234567 se enviará como 584141234567.
define('CODIGO_PAIS', '58');

// Tipos de mensaje permitidos (clave => etiqueta).
define('TIPOS_MENSAJE', serialize([
    'prevencion' => 'Prevención',
    'cobranza'   => 'Cobranza',
    'promocion'  => 'Promoción',
    'otro'       => 'Otro',
]));
?>
