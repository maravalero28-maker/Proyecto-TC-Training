<?php
// Genera reportes en PDF (Dompdf) y Excel .xlsx (PhpSpreadsheet) y los entrega como descarga.
// Usa las mismas librerías que ya tiene el proyecto en /vendor (las de ReporteMailer).
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteExportador {

    // Nombres bonitos para los encabezados (si una columna no está aquí, se arma con ucwords)
    private const ETIQUETAS = [
        'id_pago' => 'N.º pago', 'id_nomina' => 'N.º nómina', 'id_servicio' => 'N.º servicio',
        'id_cliente' => 'N.º cliente', 'id_cuenta' => 'N.º cuenta', 'id_admin' => 'N.º usuario',
        'cedula' => 'Cédula', 'telefono' => 'Teléfono', 'metodo_pago' => 'Método de pago',
        'banco_receptor' => 'Banco', 'fecha_pago' => 'Fecha de pago', 'estatus_solvencia' => 'Solvencia',
        'nombre_plan' => 'Plan', 'fecha_registro' => 'Fecha de registro', 'pagos_realizados' => 'Pagos realizados',
        'total_pagado_usd' => 'Total pagado (USD)', 'ultimo_pago' => 'Último pago',
        'num_cuenta' => 'N.º de cuenta / referencia', 'tipo_moneda' => 'Moneda', 'saldo_inicial' => 'Saldo inicial',
        'saldo_actual' => 'Saldo actual', 'ingresos_usd' => 'Ingresos (USD)', 'egresos_usd' => 'Egresos (USD)',
        'saldo_usd' => 'Saldo (USD)', 'monto_usd' => 'Monto (USD)', 'descripcion_rol' => 'Descripción del rol',
        'modulos_acceso' => 'Módulos con acceso', 'fecha_creacion' => 'Fecha de creación',
        'pregunta_seguridad' => 'Pregunta de seguridad configurada', 'tipo' => 'Tipo de movimiento',
        'concepto' => 'Concepto', 'tasa' => 'Tasa de cambio',
    ];

    // Columnas que siempre se escriben como texto (para no perder ceros ni convertirlas en números)
    private const COLUMNAS_TEXTO = ['cedula', 'telefono', 'num_cuenta'];

    // ---------- Utilidades ----------

    public static function etiqueta($columna) {
        $columna = (string) $columna;
        return self::ETIQUETAS[$columna] ?? ucwords(str_replace('_', ' ', $columna));
    }

    private static function h($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private static function esTexto($columna) {
        return in_array($columna, self::COLUMNAS_TEXTO, true);
    }

    private static function esTasa($columna) {
        return $columna === 'tasa';
    }

    private static function esMonto($columna) {
        return (bool) preg_match('/(monto|saldo|ingresos|egresos|total_pagado)/', (string) $columna);
    }

    private static function nombreHoja($titulo, array &$usados) {
        $base = trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '', (string) $titulo));
        $base = mb_substr($base === '' ? 'Hoja' : $base, 0, 31);
        $nombre = $base;
        $n = 2;
        while (in_array(mb_strtolower($nombre), $usados, true)) {
            $sufijo = ' ' . $n++;
            $nombre = mb_substr($base, 0, 31 - mb_strlen($sufijo)) . $sufijo;
        }
        $usados[] = mb_strtolower($nombre);
        return $nombre;
    }

    private static function esFilaTotal(array $fila) {
        foreach ($fila as $v) {
            if (is_string($v) && strpos($v, 'TOTAL') === 0) {
                return true;
            }
        }
        return false;
    }

    // ---------- Excel (.xlsx) ----------

    /**
     * $secciones: [ ['titulo' => 'Pagos de clientes', 'datos' => [ [col => valor, ...], ... ]], ... ]
     * $resumen:   ['Indicador' => 'valor'] (hoja "Resumen"; se omite si va vacío)
     */
    public static function generarXlsx($titulo, array $secciones, array $resumen = []) {
        $libro = new Spreadsheet();
        $usados = [];
        $primera = true;

        if (!empty($resumen)) {
            $hoja = $libro->getActiveSheet();
            $hoja->setTitle(self::nombreHoja('Resumen', $usados));
            $hoja->setCellValueExplicit('A1', (string) $titulo, DataType::TYPE_STRING);
            $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $hoja->setCellValueExplicit('A2', 'Generado el ' . date('d/m/Y H:i'), DataType::TYPE_STRING);
            $fila = 4;
            foreach ($resumen as $indicador => $valor) {
                $hoja->setCellValueExplicit('A' . $fila, (string) $indicador, DataType::TYPE_STRING);
                $hoja->setCellValueExplicit('B' . $fila, (string) $valor, DataType::TYPE_STRING);
                $hoja->getStyle('A' . $fila)->getFont()->setBold(true);
                $fila++;
            }
            $hoja->getColumnDimension('A')->setAutoSize(true);
            $hoja->getColumnDimension('B')->setAutoSize(true);
            $primera = false;
        }

        foreach ($secciones as $seccion) {
            if ($primera) {
                $hoja = $libro->getActiveSheet();
                $primera = false;
            } else {
                $hoja = $libro->createSheet();
            }
            $hoja->setTitle(self::nombreHoja($seccion['titulo'], $usados));
            self::escribirTabla($hoja, $seccion['titulo'], $seccion['datos']);
        }

        $libro->setActiveSheetIndex(0);

        $ruta = tempnam(sys_get_temp_dir(), 'rep');
        (new Xlsx($libro))->save($ruta);
        $contenido = file_get_contents($ruta);
        unlink($ruta);
        return $contenido;
    }

    private static function escribirTabla($hoja, $titulo, array $datos) {
        $hoja->setCellValueExplicit('A1', (string) $titulo, DataType::TYPE_STRING);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->setCellValueExplicit('A2', 'Generado el ' . date('d/m/Y H:i') . ' · ' . count($datos) . ' registro(s)', DataType::TYPE_STRING);

        if (empty($datos)) {
            $hoja->setCellValueExplicit('A4', 'Sin registros.', DataType::TYPE_STRING);
            $hoja->getColumnDimension('A')->setAutoSize(true);
            return;
        }

        $columnas = array_keys($datos[0]);
        $nCols = count($columnas);
        $filaEnc = 4;

        // Encabezados
        foreach ($columnas as $i => $col) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $hoja->setCellValueExplicit($letra . $filaEnc, self::etiqueta($col), DataType::TYPE_STRING);
        }
        $ultimaLetra = Coordinate::stringFromColumnIndex($nCols);
        $estiloEnc = $hoja->getStyle('A' . $filaEnc . ':' . $ultimaLetra . $filaEnc);
        $estiloEnc->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estiloEnc->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F3A5F');

        // Datos
        $fila = $filaEnc + 1;
        foreach ($datos as $registro) {
            foreach ($columnas as $i => $col) {
                $v = $registro[$col] ?? null;
                if ($v === null || $v === '') {
                    continue;
                }
                $celda = Coordinate::stringFromColumnIndex($i + 1) . $fila;
                if (!self::esTexto($col) && is_numeric($v)) {
                    $hoja->setCellValueExplicit($celda, (float) $v, DataType::TYPE_NUMERIC);
                } else {
                    $hoja->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING);
                }
            }
            if (self::esFilaTotal($registro)) {
                $hoja->getStyle('A' . $fila . ':' . $ultimaLetra . $fila)->getFont()->setBold(true);
            }
            $fila++;
        }
        $ultimaFila = $fila - 1;

        // Formatos numéricos y ancho de columnas
        foreach ($columnas as $i => $col) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            if (self::esTasa($col)) {
                $hoja->getStyle($letra . ($filaEnc + 1) . ':' . $letra . $ultimaFila)->getNumberFormat()->setFormatCode('#,##0.0000');
            } elseif (self::esMonto($col)) {
                $hoja->getStyle($letra . ($filaEnc + 1) . ':' . $letra . $ultimaFila)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $hoja->getColumnDimension($letra)->setAutoSize(true);
        }

        $hoja->setAutoFilter('A' . $filaEnc . ':' . $ultimaLetra . $ultimaFila);
        $hoja->freezePane('A' . ($filaEnc + 1));
    }

    // ---------- PDF ----------

    public static function generarPdf($titulo, array $secciones, array $resumen = []) {
        $html = '<html><head><meta charset="UTF-8"><style>'
            . '@page { margin: 28px 28px 38px 28px; }'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#1f2937;}'
            . 'h1{font-size:18px;color:#1f3a5f;margin:0 0 2px 0;}'
            . 'h2{font-size:13px;color:#1f3a5f;margin:0 0 2px 0;border-bottom:2px solid #1f3a5f;padding-bottom:3px;}'
            . '.sub{font-size:9px;color:#6b7280;margin:0 0 10px 0;}'
            . 'table{width:100%;border-collapse:collapse;margin-top:6px;}'
            . 'thead{display:table-header-group;}'
            . 'th,td{border:1px solid #d1d5db;padding:4px 5px;text-align:left;word-wrap:break-word;}'
            . 'th{background:#1f3a5f;color:#ffffff;font-size:8px;}'
            . 'tr:nth-child(even) td{background:#f4f6fb;}'
            . 'td.num{text-align:right;}'
            . 'tr.total td{font-weight:bold;background:#e5e9f2;}'
            . '.kpi td{font-size:10px;padding:6px 8px;}'
            . '.kpi td.et{width:45%;font-weight:bold;background:#f4f6fb;}'
            . '.salto{page-break-before:always;}'
            . '</style></head><body>';

        $html .= '<h1>' . self::h($titulo) . '</h1>'
               . '<p class="sub">TC-Control · Generado el ' . date('d/m/Y H:i') . '</p>';

        if (!empty($resumen)) {
            $html .= '<h2>Resumen</h2><table class="kpi"><tbody>';
            foreach ($resumen as $indicador => $valor) {
                $html .= '<tr><td class="et">' . self::h($indicador) . '</td><td>' . self::h($valor) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $total = count($secciones);
        foreach ($secciones as $idx => $seccion) {
            $datos = $seccion['datos'];
            // Cada sección nueva va en página aparte, salvo la primera cuando no hay resumen
            $salto = (!empty($resumen) || $idx > 0) ? ' class="salto"' : '';
            $html .= '<div' . $salto . '>';
            if ($total > 1 || !empty($resumen)) {
                $html .= '<h2>' . self::h($seccion['titulo']) . '</h2>'
                       . '<p class="sub">' . count($datos) . ' registro(s)</p>';
            } else {
                $html .= '<p class="sub">' . count($datos) . ' registro(s)</p>';
            }

            if (empty($datos)) {
                $html .= '<p>Sin registros.</p></div>';
                continue;
            }

            $columnas = array_keys($datos[0]);
            $html .= '<table><thead><tr>';
            foreach ($columnas as $col) {
                $html .= '<th>' . self::h(self::etiqueta($col)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($datos as $registro) {
                $html .= '<tr' . (self::esFilaTotal($registro) ? ' class="total"' : '') . '>';
                foreach ($columnas as $col) {
                    $v = $registro[$col] ?? null;
                    if ($v === null || $v === '') {
                        $html .= '<td>-</td>';
                    } elseif (!self::esTexto($col) && is_numeric($v) && self::esTasa($col)) {
                        $html .= '<td class="num">' . number_format((float) $v, 4, ',', '.') . '</td>';
                    } elseif (!self::esTexto($col) && is_numeric($v) && self::esMonto($col)) {
                        $html .= '<td class="num">' . number_format((float) $v, 2, ',', '.') . '</td>';
                    } else {
                        $html .= '<td>' . self::h($v) . '</td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Numeración de páginas
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(715, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 8, [0.42, 0.45, 0.5]);

        return $dompdf->output();
    }

    // ---------- Descarga ----------

    /**
     * Genera el archivo y lo envía al navegador como descarga. Termina la ejecución.
     * $formato: 'pdf' o 'xlsx'.
     */
    public static function descargar($formato, $nombreBase, $titulo, array $secciones, array $resumen = []) {
        if ($formato === 'pdf') {
            $contenido = self::generarPdf($titulo, $secciones, $resumen);
            $mime = 'application/pdf';
            $ext = 'pdf';
        } else {
            $contenido = self::generarXlsx($titulo, $secciones, $resumen);
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $ext = 'xlsx';
        }

        // Limpia cualquier salida previa para que el archivo no llegue corrupto
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $nombreBase . '.' . $ext . '"');
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $contenido;
        exit();
    }
}
