<?php
require_once __DIR__ . '/../config/db.php';
iniciarSesion();

/* Guard admin */
$usr     = $_SESSION['usuario'] ?? null;
$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($usr && (($usr['rol_id'] ?? null) == 1 || $rolNom === 'administrador' || $rolNom === 'admin'));
if (!$esAdmin) {
  http_response_code(403);
  echo 'Acceso restringido.';
  exit;
}

require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<p>Pedido inválido.</p>'; require_once __DIR__ . '/../includes/footer.php'; exit; }

$bd = obtenerConexion();

/* Cabecera */
$hsql = "
  SELECT p.*, u.nombre AS usuario_nombre, u.correo AS usuario_correo
  FROM pedidos p
  JOIN usuarios u ON u.id = p.usuario_id
  WHERE p.id = ?
  LIMIT 1
";
$h = $bd->prepare($hsql);
$h->execute([$id]);
$pedido = $h->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { echo '<p>Pedido no encontrado.</p>'; require_once __DIR__ . '/../includes/footer.php'; exit; }

/* Detalles */
$dsql = "
  SELECT d.*, pr.nombre AS producto, pr.imagen_url
  FROM detalles_pedido d
  JOIN productos pr ON pr.id = d.producto_id
  WHERE d.pedido_id = ?
";
$d = $bd->prepare($dsql);
$d->execute([$id]);
$detalles = $d->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Pedido #<?= (int)$pedido['id'] ?></h1>

<div class="form-card" style="margin-top:8px">
  <div style="display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between">
    <div>
      <div><strong>Fecha:</strong> <?= htmlspecialchars($pedido['creado_en']) ?></div>
      <div><strong>Cliente:</strong> <?= htmlspecialchars($pedido['usuario_nombre'] ?? '') ?> (<?= htmlspecialchars($pedido['usuario_correo'] ?? '') ?>)</div>
    </div>
    <div style="text-align:right">
      <div style="font-size:14px;color:#475569">Total</div>
      <div style="font-size:22px;font-weight:800;color:#14532d">Q <?= number_format((float)$pedido['total'], 2) ?></div>
    </div>
  </div>
</div>

<div class="table-card" style="margin-top:12px">
  <table class="tabla-admin" style="width:100%">
    <thead>
      <tr>
        <th class="col-img">Img</th>
        <th>Producto</th>
        <th style="width:120px">Cant.</th>
        <th>Precio</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($detalles as $it):
      $img = $it['imagen_url'] ?? null;
      $src = '';
      if ($img) {
        $img = str_replace('\\','/',$img);
        $src = preg_match('#^https?://#i', $img) ? $img :
               ( ($img[0] === '/' ? BASE_URL . '/public' . $img : BASE_URL . '/public/' . $img) );
      }
      $subtotal = (float)$it['precio'] * (int)$it['cantidad'];
    ?>
      <tr>
        <td><?php if ($src): ?><img class="thumb" src="<?= htmlspecialchars($src) ?>" alt=""><?php endif; ?></td>
        <td><?= htmlspecialchars($it['producto']) ?></td>
        <td><?= (int)$it['cantidad'] ?></td>
        <td>Q <?= number_format((float)$it['precio'],2) ?></td>
        <td><strong>Q <?= number_format($subtotal,2) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div style="margin-top:12px">
  <a class="btn" href="<?= BASE_URL ?>/admin/pedidos.php">Volver a pedidos</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
