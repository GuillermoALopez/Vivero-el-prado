<?php
// public/mis_pedidos.php

// 1) NADA de HTML antes de estas líneas
require_once __DIR__ . '/../config/config.php'; // para BASE_URL
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
iniciarSesion();

$usr = $_SESSION['usuario'] ?? null;

/* --- si NO hay sesión: manda a registro (o login) y luego regresa aquí --- */
if (!$usr) {
  $returnTo = BASE_URL . '/public/mis_pedidos.php';
  // Si prefieres login en lugar de registro, cambia "registro.php" por "login.php"
  header('Location: ' . BASE_URL . '/auth/registro.php?return_to=' . urlencode($returnTo));
  exit;
}

/* --- si es admin, redirige al panel de pedidos admin (opcional) --- */
$rolId  = $usr['rol_id'] ?? null;
$rolNom = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($rolId == 1) || ($rolNom === 'administrador') || ($rolNom === 'admin');
if ($esAdmin) {
  header('Location: ' . BASE_URL . '/admin/pedidos.php');
  exit;
}

/* --- ya hay cliente logueado: ahora sí procesa y luego imprime HTML --- */
$bd  = obtenerConexion();
$uid = (int)($usr['id'] ?? 0);

/* Trae mis pedidos con conteos */
$sql = "
  SELECT p.id, p.total, p.creado_en,
         COUNT(d.id) AS lineas,
         COALESCE(SUM(d.cantidad),0) AS unidades
  FROM pedidos p
  LEFT JOIN detalles_pedido d ON d.pedido_id = p.id
  WHERE p.usuario_id = ?
  GROUP BY p.id
  ORDER BY p.creado_en DESC
";
$st = $bd->prepare($sql);
$st->execute([$uid]);
$pedidos = $st->fetchAll(PDO::FETCH_ASSOC);

/* Flash opcional (por ejemplo tras enviar la factura) */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* Título de la página antes de incluir header.php */
$pageTitle = 'Mis pedidos';

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Mis pedidos</h1>

<?php if ($flash): ?>
  <p class="flash-success"><?= htmlspecialchars($flash) ?></p>
<?php endif; ?>

<div class="table-card">
  <table class="tabla-admin" style="width:100%">
    <thead>
      <tr>
        <th style="width:90px">ID</th>
        <th>Fecha</th>
        <th style="width:160px">Ítems</th>
        <th style="width:140px">Total</th>
        <th style="width:110px">Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$pedidos): ?>
        <tr><td colspan="5">Aún no tienes compras.</td></tr>
      <?php else: foreach ($pedidos as $p): ?>
        <tr>
          <td>#<?= (int)$p['id'] ?></td>
          <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($p['creado_en']))) ?></td>
          <td><?= (int)$p['lineas'] ?> líneas / <?= (int)$p['unidades'] ?> und.</td>
          <td><strong>Q <?= number_format((float)$p['total'], 2) ?></strong></td>
          <td>
            <a class="btn" href="<?= BASE_URL ?>/public/pedido_ver.php?id=<?= (int)$p['id'] ?>">Ver</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';

/* Importante: no cierres con "?>" para evitar BOM/espacios extra que rompan header() */
