<?php
// includes/factura_pdf.php
use Dompdf\Dompdf;
use Dompdf\Options;

if (!function_exists('factura_pdf_stream')) {
  function factura_pdf_stream(PDO $bd, int $pedidoId, bool $download = false): void {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/factura_tpl.php';

    // --- Datos del pedido ---
    $st = $bd->prepare("
      SELECT p.id, p.total, p.creado_en, u.nombre AS cliente_nombre, u.correo AS cliente_correo
      FROM pedidos p
      JOIN usuarios u ON u.id = p.usuario_id
      WHERE p.id = ?
      LIMIT 1
    ");
    $st->execute([$pedidoId]);
    $pedido = $st->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) { http_response_code(404); exit; }

    $st = $bd->prepare("
      SELECT d.cantidad, d.precio, pr.nombre AS producto
      FROM detalles_pedido d
      JOIN productos pr ON pr.id = d.producto_id
      WHERE d.pedido_id = ?
      ORDER BY d.id
    ");
    $st->execute([$pedidoId]);
    $lineas = $st->fetchAll(PDO::FETCH_ASSOC);

    // Subtotal (sin impuestos)
    $subtotal = 0.0;
    foreach ($lineas as $l) $subtotal += (float)$l['cantidad'] * (float)$l['precio'];

    // === CONFIGURACIÓN EDITABLE ===
    // Cambia SOLO esta ruta si quieres usar otro logo (o pon null para mostrar "Tu logo aquí").
    $logoPath = __DIR__ . '/../public/assets/Logotipo De Hoja.jpeg';

    $opts = [
      'brand_name'  => 'Vivero El Prado',
      'brand_color' => '#CFEEDC',   // verde pastel
      'brand_dark'  => '#1A5B50',   // verde oscuro
      'logo_path'   => (is_file($logoPath) ? $logoPath : null),
      'contact' => [
        'tel'       => '37291383',
        'correo'    => 'madelynlotzoj@gmail.com',
        'direccion' => 'San Martín Jilotepeque, Chimaltenango',
        'sitio'     => 'www.viveroelprado.com',
      ],
      'moneda' => 'Q',
    ];
    // ==============================

    $html = factura_tpl_html($pedido, $lineas, $subtotal, $opts);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'Factura_' . str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => $download ? 1 : 0]);
  }
}
