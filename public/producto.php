<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$bd = obtenerConexion();
$id = (int)($_GET['id'] ?? 0);

$stmt = $bd->prepare("
  SELECT p.*, c.nombre AS categoria
  FROM productos p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  WHERE p.id = ?
");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
  echo '<p>Producto no encontrado.</p>';
  echo '<p><a class="btn" href="' . BASE_URL . '/public/index.php">Volver al catálogo</a></p>';
  require_once __DIR__ . '/../includes/footer.php'; exit;
}

/* Helper de imagen */
function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}
$src = url_imagen($producto['imagen_url'] ?? null);

/* “Volver” preservando filtros si vinieran en la URL */
$backQuery = [];
foreach (['categoria_id','pagina','todos','q'] as $k) {  // incluí q por si filtras texto
  if (isset($_GET[$k]) && $_GET[$k] !== '') $backQuery[$k] = $_GET[$k];
}
$volverUrl = BASE_URL . '/public/index.php' . ( $backQuery ? ('?' . http_build_query($backQuery)) : '' );

/* Flash de operaciones del carrito */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stock = (int)$producto['stock'];
?>
<?php if ($flash): ?>
  <p class="flash-success"><?php echo htmlspecialchars($flash); ?></p>
<?php endif; ?>

<h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>

<div class="product-wrap">
  <div class="media-hero">
    <?php if ($src): ?>
      <img src="<?php echo htmlspecialchars($src); ?>"
           alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
    <?php endif; ?>
  </div>

  <aside class="info-card">
    <?php if (!empty($producto['categoria'])): ?>
      <a class="badge"
         href="<?php echo BASE_URL; ?>/public/index.php?categoria_id=<?php echo (int)$producto['categoria_id']; ?>">
        <?php echo htmlspecialchars($producto['categoria']); ?>
      </a>
    <?php endif; ?>

    <p style="margin:6px 0 10px;color:#475569">
      <?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? '')); ?>
    </p>

    <div class="price">Q <?php echo number_format((float)$producto['precio'], 2); ?></div>

    <div class="stock">
      <span class="dot" style="background: <?php echo ($stock>0?'#16a34a':'#dc2626'); ?>"></span>
      Stock: <?php echo $stock; ?>
    </div>

    <div style="margin-top:14px; display:flex; gap:8px; flex-wrap:wrap; align-items:center">
      <!-- Añadir al carrito -->
      <form method="post" action="<?php echo BASE_URL; ?>/public/carrito_agregar.php" style="display:flex; gap:8px; align-items:center">
        <input type="hidden" name="id" value="<?php echo (int)$producto['id']; ?>">
        <input type="hidden" name="back" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
        <label style="font-weight:600;color:#14532d">Cantidad</label>
        <input
          class="input"
          type="number"
          name="qty"
          min="1"
          <?php if ($stock > 0): ?>
            max="<?php echo $stock; ?>"
            value="1"
          <?php else: ?>
            value="0" disabled
          <?php endif; ?>
          style="width:90px"
        >
        <button class="btn primary" type="submit" <?php echo $stock<=0 ? 'disabled' : ''; ?>>
          Añadir al carrito
        </button>
      </form>

      <!-- Volver -->
      <a class="btn ghost" href="<?php echo htmlspecialchars($volverUrl); ?>">Volver al catálogo</a>
    </div>
  </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
