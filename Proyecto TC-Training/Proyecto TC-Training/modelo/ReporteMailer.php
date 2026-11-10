<?php
// Servicio de envío de reportes por correo (SMTP con PHPMailer).
// Requiere: composer require phpmailer/phpmailer dompdf/dompdf phpoffice/phpspreadsheet
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteMailer {
    private $cfg;

    public function __construct() {
        // La configuración se guarda desde la web (módulo Reportes) en la base de datos
        require_once __DIR__ . '/ConfigCorreo.php';
        $c = (new ConfigCorreo())->obtenerParaEnvio();
        if (!$c) {
            throw new Exception('El correo emisor no está configurado. Configúralo en la sección "Configuración del correo emisor" de Reportes.');
        }
        $this->cfg = [
            'host'       => $c['host'],
            'port'       => (int) $c['puerto'],
            'secure'     => $c['seguridad'],
            'username'   => $c['usuario'],
            'password'   => $c['clave'],
            'from_email' => $c['usuario'],
            'from_name'  => $c['remitente_nombre'],
        ];
    }

    /**
     * KPIs ejecutivos calculados con los datos COMPLETOS (antes de
     * aplicar el nivel de detalle "resumido" o "personalizado").
     */
    public static function calcularKpis($modulo, array $datos) {
        $n = count($datos);

        if (strpos($modulo, 'pagos') === 0) {
            $montos = array_map(function ($f) { return (float) ($f['monto'] ?? 0); }, $datos);
            $total = array_sum($montos);

            $porMetodo = [];
            foreach ($datos as $f) {
                $m = $f['metodo_pago'] ?? 'N/A';
                $porMetodo[$m] = ($porMetodo[$m] ?? 0) + 1;
            }
            arsort($porMetodo);

            return [
                'Registros'            => (string) $n,
                'Monto total (USD)'    => number_format($total, 2, ',', '.'),
                'Pago promedio (USD)'  => number_format($n ? $total / $n : 0, 2, ',', '.'),
                'Pago mayor (USD)'     => number_format($n ? max($montos) : 0, 2, ',', '.'),
                'Método más usado'     => $porMetodo ? array_key_first($porMetodo) . ' (' . reset($porMetodo) . ')' : 'N/A',
            ];
        }

        $solventes = 0;
        foreach ($datos as $f) {
            if (($f['estatus_solvencia'] ?? '') === 'Solvente') {
                $solventes++;
            }
        }
        return [
            'Clientes'     => (string) $n,
            'Solventes'    => (string) $solventes,
            'Insolventes'  => (string) ($n - $solventes),
            '% Solvencia'  => number_format($n ? ($solventes / $n) * 100 : 0, 1, ',', '.') . '%',
        ];
    }

    /**
     * Envía el correo. Lanza una excepción si algo falla.
     * $adjuntos: arreglo con 'pdf' y/o 'xlsx'.
     */
    public function enviar($destinatario, $titulo, array $kpis, array $datos, array $adjuntos = []) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->cfg['username'];
        $mail->Password   = $this->cfg['password'];
        $mail->SMTPSecure = $this->cfg['secure'];
        $mail->Port       = $this->cfg['port'];
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($this->cfg['from_email'], $this->cfg['from_name']);
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'TC-Control | ' . $titulo . ' (' . date('d/m/Y') . ')';
        $mail->Body    = $this->htmlCorreo($titulo, $kpis, $adjuntos);
        $mail->AltBody = $this->textoPlano($titulo, $kpis, $adjuntos);

        $base = 'reporte_' . date('Ymd_His');
        if (in_array('pdf', $adjuntos, true)) {
            $mail->addStringAttachment($this->generarPdf($titulo, $datos), $base . '.pdf', 'base64', 'application/pdf');
        }
        if (in_array('xlsx', $adjuntos, true)) {
            $mail->addStringAttachment(
                $this->generarXlsx($datos),
                $base . '.xlsx',
                'base64',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
        }

        $mail->send();
    }

    // ---------- Cuerpo del correo ----------

    private function htmlCorreo($titulo, array $kpis, array $adjuntos) {
        $filas = '';
        foreach (array_chunk($kpis, 2, true) as $par) {
            $filas .= '<tr>';
            foreach ($par as $etiqueta => $valor) {
                $filas .= '<td width="50%" style="padding:6px;">'
                    . '<div style="background:#f4f6fb;border-radius:8px;padding:14px;text-align:center;">'
                    . '<div style="font-size:12px;color:#6b7280;">' . self::h($etiqueta) . '</div>'
                    . '<div style="font-size:20px;font-weight:bold;color:#111827;margin-top:4px;">' . self::h($valor) . '</div>'
                    . '</div></td>';
            }
            if (count($par) === 1) {
                $filas .= '<td width="50%"></td>';
            }
            $filas .= '</tr>';
        }

        $nota = $adjuntos
            ? 'Se adjunta el reporte completo en: ' . self::h(strtoupper(implode(' y ', $adjuntos))) . '.'
            : 'Este resumen no incluye archivo adjunto.';

        return '<!DOCTYPE html><html><body style="margin:0;background:#eef1f6;font-family:Arial,Helvetica,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px;">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;">'
            . '<tr><td style="background:#1f3a5f;color:#ffffff;padding:20px 24px;border-radius:10px 10px 0 0;">'
            . '<div style="font-size:13px;opacity:.8;">TC-Control</div>'
            . '<div style="font-size:22px;font-weight:bold;">' . self::h($titulo) . '</div>'
            . '<div style="font-size:12px;opacity:.8;margin-top:4px;">Generado el ' . date('d/m/Y H:i') . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:20px 18px;">'
            . '<div style="font-size:14px;color:#374151;padding:0 6px 10px;">Indicadores clave del reporte:</div>'
            . '<table width="100%" cellpadding="0" cellspacing="0">' . $filas . '</table>'
            . '<div style="font-size:13px;color:#6b7280;padding:14px 6px 0;">' . $nota . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 24px;font-size:11px;color:#9ca3af;border-top:1px solid #e5e7eb;">'
            . 'Mensaje automático enviado desde TC-Control.'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private function textoPlano($titulo, array $kpis, array $adjuntos) {
        $t = $titulo . "\nGenerado el " . date('d/m/Y H:i') . "\n\n";
        foreach ($kpis as $k => $v) {
            $t .= $k . ': ' . $v . "\n";
        }
        $t .= $adjuntos ? "\nReporte adjunto: " . implode(', ', $adjuntos) . "\n" : '';
        return $t;
    }

    // ---------- Adjuntos ----------

    private function generarPdf($titulo, array $datos) {
        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:10px;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th,td{border:1px solid #cccccc;padding:4px;text-align:left;}'
            . 'th{background:#e9edf3;}'
            . '</style></head><body>'
            . '<h2>' . self::h($titulo) . '</h2><p>Generado el ' . date('d/m/Y H:i') . '</p>'
            . '<table><thead><tr>';
        foreach (array_keys($datos[0]) as $col) {
            $html .= '<th>' . self::h(self::etiqueta($col)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($datos as $fila) {
            $html .= '<tr>';
            foreach ($fila as $valor) {
                $html .= '<td>' . self::h($valor ?? 'N/A') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->output();
    }

    private function generarXlsx(array $datos) {
        $libro = new Spreadsheet();
        $hoja  = $libro->getActiveSheet();
        $hoja->setTitle('Reporte');

        $encabezados = array_map([self::class, 'etiqueta'], array_keys($datos[0]));
        $hoja->fromArray($encabezados, null, 'A1');
        $hoja->fromArray(array_map('array_values', $datos), null, 'A2');

        $ultima = Coordinate::stringFromColumnIndex(count($encabezados));
        $hoja->getStyle('A1:' . $ultima . '1')->getFont()->setBold(true);
        for ($i = 1; $i <= count($encabezados); $i++) {
            $hoja->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $ruta = tempnam(sys_get_temp_dir(), 'rep');
        (new Xlsx($libro))->save($ruta);
        $contenido = file_get_contents($ruta);
        unlink($ruta);
        return $contenido;
    }

    // ---------- Utilidades ----------

    private static function etiqueta($columna) {
        return ucwords(str_replace('_', ' ', (string) $columna));
    }

    private static function h($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}
