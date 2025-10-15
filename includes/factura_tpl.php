<?php
// includes/factura_tpl.php

if (!function_exists('factura_tpl_html')) {

  function _money($n, $simbolo = 'Q') {
    return $simbolo . ' ' . number_format((float)$n, 2, '.', ',');
  }

  function _logo_data_uri(?string $path): ?string {
    if (!$path || !is_file($path)) return null;
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = @file_get_contents($path);
    if ($data === false) return null;
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
  }

  function factura_tpl_html(array $pedido, array $lineas, float $subtotal, array $opts): string {
    $brand   = $opts['brand_name']  ?? 'Vivero';
    $cPastel = $opts['brand_color'] ?? '#CFEEDC';
    $cDark   = $opts['brand_dark']  ?? '#1A5B50';
    $logo    = _logo_data_uri($opts['logo_path'] ?? null);
    $simbolo = $opts['moneda'] ?? 'Q';

    $cliNom = trim((string)($pedido['cliente_nombre'] ?? ''));
    $cliMail= trim((string)($pedido['cliente_correo'] ?? ''));
    $folio  = str_pad((string)$pedido['id'], 5, '0', STR_PAD_LEFT);
    $fecha  = date('d/m/Y', strtotime($pedido['creado_en'] ?? 'now'));

    $contact = $opts['contact'] ?? [];
    $tel       = htmlspecialchars($contact['tel'] ?? '');
    $correo    = htmlspecialchars($contact['correo'] ?? '');
    $direccion = htmlspecialchars($contact['direccion'] ?? '');
    $sitio     = htmlspecialchars($contact['sitio'] ?? '');

    ob_start(); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  /* Reservamos espacio inferior para el footer fijo */
  @page { margin: 28mm 18mm 40mm 18mm; }

  * { box-sizing: border-box; }
  body{ font-family: "Segoe UI", Roboto, Arial, sans-serif; color:#0f172a; font-size:12px; }
  .row{ display:flex; align-items:flex-start; justify-content:space-between; }
  .brand{ display:flex; align-items:center; gap:14px; }
  .logo{
    width:70px;height:70px;border-radius:12px;border:1.5px dashed #9ad0b3;
    display:flex;align-items:center;justify-content:center;background:#f6fff9;
    color:#6aa387;font-size:11px
  }
  .title{ font-size:28px; letter-spacing:1px; color:<?= $cDark ?>; font-weight:800; }
  .muted{ color:#475569; }
  .hr{ height:10px; background:<?= $cPastel ?>; border-radius:8px; margin:14px 0 18px; }

  .meta { margin-top:6px; }
  .meta b { color:#111827; }

  table{ width:100%; border-collapse:collapse; }
  th{
    text-align:left; background:<?= $cPastel ?>; color:#0f3b32; padding:10px 10px;
    font-weight:700; border-bottom:2px solid #b7e4cf; font-size:12px;
  }
  td{ padding:10px; border-bottom:1px solid #e5e7eb; vertical-align:top; }
  td.qty, td.price, td.total { text-align:right; white-space:nowrap; }
  .no-bb td{ border-bottom:0; }

  .totals{
    margin-top:10px; width:38%; margin-left:auto; font-size:12.5px;
  }
  .totals .row{ margin:4px 0; }
  .totals .lbl{ color:#334155; }
  .totals .val{ font-weight:700; text-align:right; }
  .totals .grand{ font-size:15px; color:<?= $cDark ?>; }

  /* Footer FIJO al fondo de la página (queda despejado por el margin-bottom de @page) */
  .footer{
    position: fixed;
    left: 18mm; right: 18mm; bottom: 18mm;
    padding-top:10px;
    border-top:2px solid <?= $cPastel ?>;
    display:grid; grid-template-columns:1fr; gap:6px; font-size:12px;
    background: transparent;
  }
  .footer .b{ font-weight:700; color:#111827; }
</style>
</head>
<body>

  <!-- Encabezado -->
  <div class="row">
    <div class="brand">
      <?php if ($logo): ?>
        <img src="<?= $logo ?>" alt="logo" style="width:200px; height:200px; border-radius:12px;">
      <?php else: ?>
        <div class="logo">Tu logo aquí</div>
      <?php endif; ?>
      <div>
        <div class="title">FACTURA</div>
        <div class="muted" style="margin-top:4px"><?= htmlspecialchars($brand) ?></div>
      </div>
    </div>

    <div class="meta">
      <div><b>Factura n°:</b> <?= $folio ?></div>
      <div><b>Fecha:</b> <?= $fecha ?></div>
      <div><b>Cliente:</b> <?= htmlspecialchars($cliNom ?: $cliMail) ?></div>
    </div>
  </div>

  <div class="hr"></div>

  <!-- Tabla -->
  <table>
    <thead>
      <tr>
        <th style="width:54%">Descripción</th>
        <th style="width:12%">Cantidad</th>
        <th style="width:17%">Precio</th>
        <th style="width:17%">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$lineas): ?>
        <tr class="no-bb"><td colspan="4" class="muted">Sin artículos.</td></tr>
      <?php else: foreach ($lineas as $l): 
        $cant  = (int)$l['cantidad'];
        $price = (float)$l['precio'];
        $tot   = $cant * $price;
      ?>
        <tr>
          <td><?= htmlspecialchars($l['producto'] ?? 'Artículo') ?></td>
          <td class="qty"><?= $cant ?></td>
          <td class="price"><?= _money($price, $simbolo) ?></td>
          <td class="total"><?= _money($tot,   $simbolo) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- Totales (sin impuestos) -->
  <div class="totals">
    <div class="row">
      <div class="lbl">Subtotal</div>
      <div class="val"><?= _money($subtotal, $simbolo) ?></div>
    </div>
    <div class="row">
      <div class="lbl grand">TOTAL</div>
      <div class="val grand"><?= _money($subtotal, $simbolo) ?></div>
    </div>
  </div>

  <!-- Contacto al final de la página -->
  <div class="footer">
    <div class="b">Contacto</div>
    <div>Tel: <?= $tel ?></div>
    <div>Correo: <?= $correo ?></div>
    <div>Dirección: <?= $direccion ?></div>
    <div>Sitio web: <?= $sitio ?></div>
  </div>

</body>
</html>
<?php
    return ob_get_clean();
  }
}
