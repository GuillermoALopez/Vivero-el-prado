<?php
require_once __DIR__ . '/../config/db.php';
iniciarSesion();

/* Guard admin ANTES del header para evitar render innecesario */
$usr     = $_SESSION['usuario'] ?? null;
$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($usr && (($usr['rol_id'] ?? null) == 1 || $rolNom === 'administrador' || $rolNom === 'admin'));
if (!$esAdmin) {
  http_response_code(403);
  echo 'Acceso restringido.';
  exit;
}

require_once __DIR__ . '/../includes/header.php';

$bd = obtenerConexion();

$q = trim($_GET['q'] ?? '');

$params = [];
$where  = '';
if ($q !== '') {
  $where = "WHERE (u.nombre LIKE :q OR u.correo LIKE :q OR p.id = :qid)";
  $params[':q']   = "%{$q}%";
  $params[':qid'] = (int)$q;
}

$sql = "
  SELECT p.id, p.total, p.creado_en,
         u.nombre AS cliente, u.correo,
         COUNT(d.id) AS lineas,
         COALESCE(SUM(d.cantidad),0) AS unidades
  FROM pedidos p
  JOIN usuarios u ON u.id = p.usuario_id
  LEFT JOIN detalles_pedido d ON d.pedido_id = p.id
  $where
  GROUP BY p.id
  ORDER BY p.id DESC
";
$st = $bd->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<h1>Pedidos (admin)</h1>

<form method="get" class="searchbar" style="margin-bottom:12px">
  <div class="search-input">
    <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/>
    </svg>
    <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por cliente, correo o #ID...">
  </div>
  <button class="search-btn" type="submit">Buscar</button>
  <?php if ($q !== ''): ?>
    <a class="btn" href="<?= BASE_URL ?>/admin/pedidos.php">Limpiar</a>
  <?php endif; ?>
</form>

<?php if ($flash): ?>
  <p class="flash-success"><?= htmlspecialchars($flash) ?></p>
<?php endif; ?>

<?php if (!$rows): ?>
  <p>No hay pedidos.</p>
<?php else: ?>
  <div class="table-card">
    <table class="tabla-admin" style="width:100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Cliente</th>
          <th>Ítems</th>
          <th>Total</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['creado_en']) ?></td>
          <td><?= htmlspecialchars($r['cliente']) ?> <span class="muted">(<?= htmlspecialchars($r['correo']) ?>)</span></td>
          <td><?= (int)$r['lineas'] ?> líneas / <?= (int)$r['unidades'] ?> und.</td>
          <td><strong>Q <?= number_format((float)$r['total'], 2) ?></strong></td>
          <td><a class="btn" href="<?= BASE_URL ?>/admin/pedido_ver.php?id=<?= (int)$r['id'] ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
