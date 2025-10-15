<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

iniciarSesion();
$bd  = obtenerConexion();
$usr = $_SESSION['usuario'] ?? null;
if (!$usr) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo '<h1>Pedido</h1><p>Falta el ID del pedido.</p>';
  require_once __DIR__ . '/../includes/footer.php'; exit;
}

/* Pedido + usuario */
$st = $bd->prepare("
  SELECT p.id, p.total, p.creado_en,
         u.id AS usuario_id, u.nombre AS cliente, u.correo
  FROM pedidos p
  LEFT JOIN usuarios u ON u.id = p.usuario_id
  WHERE p.id = ?
  LIMIT 1
");
$st->execute([$id]);
$pedido = $st->fetch(PDO::FETCH_ASSOC);
if (!$pedido) {
  echo '<h1>Pedido</h1><p>No se encontró el pedido.</p>';
  require_once __DIR__ . '/../includes/footer.php'; exit;
}

/* Dueño o admin */
$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$rolId   = $usr['rol_id'] ?? null;
$esAdmin = ($rolId == 1) || ($rolNom === 'admin') || ($rolNom === 'administrador');
if (!$esAdmin && (int)$pedido['usuario_id'] !== (int)$usr['id']) {
  echo '<h1>Pedido</h1><p>No tienes permiso para ver este pedido.</p>
        <p><a class="btn" href="' . BASE_URL . '/public/mis_pedidos.php">Ir a mis pedidos</a></p>';
  require_once __DIR__ . '/../includes/footer.php'; exit;
}

/* Ítems */
$di = $bd->prepare("
  SELECT d.producto_id, d.cantidad, d.precio,
         p.nombre, p.imagen_url
  FROM detalles_pedido d
  INNER JOIN productos p ON p.id = d.producto_id
  WHERE d.pedido_id = ?
  ORDER BY d.id ASC
");
$di->execute([$id]);
$items = $di->fetchAll(PDO::FETCH_ASSOC);

/* Helper imagen */
function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}

$fecha  = $pedido['creado_en'] ? date('Y-m-d H:i:s', strtotime($pedido['creado_en'])) : '';
$total  = (float)$pedido['total'];
$pdfUrl = BASE_URL . '/public/pedido_pdf.php?id=' . (int)$pedido['id'];       // << usa el endpoint de PDF correcto
$returnUrl = BASE_URL . '/public/pedido_ver.php?id=' . (int)$pedido['id'];    // volver aquí tras enviar
?>
<h1>Pedido #<?= (int)$pedido['id'] ?></h1>

<div class="table-card" style="margin:10px 0 12px; padding:12px; display:flex; align-items:center; justify-content:space-between; background:var(--green-50)">
  <div>
    <div><strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?></div>
    <div><strong>Cliente:</strong>
      <?php
        $nc = trim($pedido['cliente'] ?? '');
        $cc = trim($pedido['correo']  ?? '');
        echo htmlspecialchars($nc !== '' ? $nc : '—');
        if ($cc !== '') echo ' (' . htmlspecialchars($cc) . ')';
      ?>
    </div>
  </div>
  <div style="text-align:right">
    <div class="muted">Total</div>
    <div style="font-size:22px;font-weight:800;color:#14532d">Q <?= number_format($total, 2) ?></div>
  </div>
</div>

<div class="form-actions" style="margin-top:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
  <a class="btn primary" href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener">📄 Descargar factura (PDF)</a>

  <?php if (!empty($_SESSION['usuario']['correo'])): ?>
    <form method="post" action="<?= BASE_URL ?>/public/factura_enviar.php" style="display:inline;">
      <input type="hidden" name="pedido_id" value="<?= (int)$pedido['id'] ?>">
      <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['usuario']['correo']) ?>">
      <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">
      <button class="btn" type="submit">✉️ Enviarla a mi correo</button>
    </form>
  <?php endif; ?>
</div>

<div class="table-card">
  <table class="tabla-admin" style="width:100%">
    <thead>
      <tr>
        <th class="col-img">Img</th>
        <th>Producto</th>
        <th style="width:90px">Cant.</th>
        <th style="width:120px">Precio</th>
        <th style="width:140px">Subtotal</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$items): ?>
      <tr><td colspan="5">Sin ítems.</td></tr>
    <?php else: foreach ($items as $it):
      $src = url_imagen($it['imagen_url'] ?? null);
      $sub = (float)$it['precio'] * (int)$it['cantidad']; ?>
      <tr>
        <td><?php if ($src): ?><img class="thumb" src="<?= htmlspecialchars($src) ?>" alt=""><?php endif; ?></td>
        <td><?= htmlspecialchars($it['nombre'] ?? '') ?></td>
        <td><?= (int)$it['cantidad'] ?></td>
        <td>Q <?= number_format((float)$it['precio'], 2) ?></td>
        <td><strong>Q <?= number_format($sub, 2) ?></strong></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap">
  <a class="btn" href="<?= BASE_URL ?>/public/mis_pedidos.php">Ver mis pedidos</a>
  <a class="btn" href="<?= BASE_URL ?>/public/index.php">Volver al catálogo</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
