<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';

iniciarSesion();
$bd = obtenerConexion();

// --- Acciones POST: actualizar, eliminar, vaciar (antes de imprimir HTML) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Vaciar
  if (isset($_POST['vaciar'])) {
    cart_clear();
    $_SESSION['flash'] = 'Carrito vaciado.';
    header('Location: ' . BASE_URL . '/public/carrito.php');
    exit;
  }

  // Eliminar 1 ítem
  if (isset($_POST['eliminar_id'])) {
    cart_remove((int)$_POST['eliminar_id']);
    $_SESSION['flash'] = 'Producto eliminado del carrito.';
    header('Location: ' . BASE_URL . '/public/carrito.php');
    exit;
  }

  // Actualizar cantidades (respetando stock)
  if (isset($_POST['qty']) && is_array($_POST['qty'])) {
    // Normaliza a enteros [id => cantidadSolicitada]
    $sol = [];
    foreach ($_POST['qty'] as $pid => $cant) {
      $pid  = (int)$pid;
      $cant = max(0, min(99, (int)$cant));
      $sol[$pid] = $cant;
    }

    if ($sol) {
      // Trae stock de los productos involucrados
      $ids = array_keys($sol);
      $ph  = implode(',', array_fill(0, count($ids), '?'));
      $st  = $bd->prepare("SELECT id, stock FROM productos WHERE id IN ($ph)");
      $st->execute($ids);
      $stocks = [];
      foreach ($st->fetchAll() as $r) $stocks[(int)$r['id']] = (int)$r['stock'];

      $ajustado = false;
      foreach ($sol as $pid => $cant) {
        $limite = isset($stocks[$pid]) ? $stocks[$pid] : 99;
        $final  = min($cant, $limite);
        if ($final !== $cant) $ajustado = true;
        cart_set($pid, $final); // si 0 => elimina
      }

      $_SESSION['flash'] = $ajustado
        ? 'Cantidades actualizadas (algunas ajustadas por stock).'
        : 'Cantidades actualizadas.';
    } else {
      cart_clear();
      $_SESSION['flash'] = 'Carrito vaciado.';
    }

    header('Location: ' . BASE_URL . '/public/carrito.php');
    exit;
  }
}

// --- Ya sin acciones: cargar items + total y recién ahí incluir header ---
$items = cart_items_with_products($bd);
$total = cart_total($bd);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once __DIR__ . '/../includes/header.php';

/* Helper imagen (igual que en index/producto) */
function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}
?>

<h1>Carrito</h1>

<?php if ($flash): ?>
  <p class="flash-success"><?php echo htmlspecialchars($flash); ?></p>
<?php endif; ?>

<?php if (!$items): ?>
  <p>Tu carrito está vacío.</p>
  <a class="btn" href="<?php echo BASE_URL; ?>/public/index.php">Volver al catálogo</a>
<?php else: ?>
  <form method="post">
    <div class="table-card">
      <table class="tabla-admin" style="width:100%">
        <thead>
          <tr>
            <th class="col-img">Img</th>
            <th>Producto</th>
            <th>Precio</th>
            <th style="width:120px">Cant.</th>
            <th>Subtotal</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <?php $src = url_imagen($it['imagen_url'] ?? null); ?>
              <?php if ($src): ?>
                <img class="thumb" src="<?php echo htmlspecialchars($src); ?>" alt="">
              <?php endif; ?>
            </td>
            <td>
              <a href="<?php echo BASE_URL; ?>/public/producto.php?id=<?php echo (int)$it['id']; ?>">
                <?php echo htmlspecialchars($it['nombre']); ?>
              </a>
            </td>
            <td>Q <?php echo number_format((float)$it['precio'], 2); ?></td>
            <td>
              <input
                class="input"
                type="number"
                name="qty[<?php echo (int)$it['id']; ?>]"
                value="<?php echo (int)$it['cantidad']; ?>"
                min="0"
                max="<?php echo (int)$it['stock']; ?>"  <!-- respeta stock -->
              >
            </td>
            <td><strong>Q <?php echo number_format((float)$it['subtotal'], 2); ?></strong></td>
            <td>
              <button class="btn danger" name="eliminar_id" value="<?php echo (int)$it['id']; ?>">Eliminar</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;gap:8px">
      <div>
        <button class="btn" type="submit">Actualizar cantidades</button>
        <button class="btn danger" name="vaciar" value="1">Vaciar carrito</button>
      </div>
      <div style="text-align:right">
        <div style="font-size:14px;color:#475569">Total:</div>
        <div style="font-size:22px;font-weight:800;color:#14532d">Q <?php echo number_format((float)$total, 2); ?></div>
        
        <a class="btn primary" href="<?php echo BASE_URL; ?>/public/index.php">Seguir comprando</a>
        <?php if ($items): ?>
        <a class="btn primary" href="<?php echo BASE_URL; ?>/public/checkout.php">Finalizar compra</a>
        <?php endif; ?>


       
      </div>
    </div>
  </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
