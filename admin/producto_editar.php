<?php


require_once __DIR__ . '/../config/db.php';
requiereAdmin();
$bd = obtenerConexion();

$id = (int)($_GET['id'] ?? 0);
$stmt = $bd->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: ' . BASE_URL . '/admin/productos.php'); exit; }

$categorias = $bd->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre       = trim($_POST['nombre'] ?? '');
  $descripcion  = trim($_POST['descripcion'] ?? '');
  $precio       = (float)($_POST['precio'] ?? 0);
  $stock        = (int)($_POST['stock'] ?? 0);
  $categoria_id = $_POST['categoria_id'] !== '' ? (int)$_POST['categoria_id'] : null;

  if ($nombre === '') $errores[] = 'El nombre es obligatorio';
  if ($precio <= 0)   $errores[] = 'El precio debe ser mayor a 0';

  // Por defecto conservar imagen actual
  $imagen_ruta = $p['imagen_url'];

  // ¿Hay nueva imagen?
  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
      $errores[] = 'Error al subir la imagen (código ' . $_FILES['imagen']['error'] . ')';
    } else {
      $tmp  = $_FILES['imagen']['tmp_name'];
      $size = (int)$_FILES['imagen']['size'];
      if ($size > 2 * 1024 * 1024) {
        $errores[] = 'La imagen supera 2MB';
      } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        $permitidos = [
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
          'image/gif'  => 'gif',
        ];
        if (!isset($permitidos[$mime])) {
          $errores[] = 'Formato de imagen no permitido (JPG, PNG, WEBP o GIF)';
        } else {
          $uploadsAbs = __DIR__ . '/../public/uploads';
          if (!is_dir($uploadsAbs)) mkdir($uploadsAbs, 0777, true);

          $ext = $permitidos[$mime];
          $nombreSeguro = bin2hex(random_bytes(8)) . '.' . $ext;
          $destinoAbs = $uploadsAbs . '/' . $nombreSeguro;

          if (!move_uploaded_file($tmp, $destinoAbs)) {
            $errores[] = 'No se pudo guardar la imagen en el servidor';
          } else {
            // borrar imagen anterior si era local
            if (!empty($p['imagen_url']) && !str_starts_with($p['imagen_url'], 'http')) {
              $anteriorAbs = __DIR__ . '/../public' . str_replace('\\', '/', $p['imagen_url']);
              if (is_file($anteriorAbs)) { @unlink($anteriorAbs); }
            }
            $imagen_ruta = '/uploads/' . $nombreSeguro;
          }
        }
      }
    }
  }

  if (!$errores) {
    $stmt = $bd->prepare("
      UPDATE productos
      SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, imagen_url=?
      WHERE id=?
    ");
    $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria_id, $imagen_ruta, $id]);

    $_SESSION['flash'] = 'Producto actualizado correctamente.';
    header('Location: ' . BASE_URL . '/admin/productos.php');
    exit;
  }
}

require_once __DIR__ . '/../includes/header.php';

// URL para la vista previa
$src = null;
if (!empty($p['imagen_url'])) {
  $img = str_replace('\\', '/', $p['imagen_url']);
  $src = str_starts_with($img, 'http') ? $img : BASE_URL . '/public' . ( $img[0] === '/' ? $img : '/' . $img );
}
?>
<h1>Editar producto #<?php echo (int)$p['id']; ?></h1>

<?php if ($errores): ?>
  <div class="alert-danger">
    <?php foreach($errores as $e): ?>
      <div><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="form-card">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <!-- Columna izquierda: campos -->
    <div>
      <div class="form-group">
        <label>Nombre</label>
        <input class="input" name="nombre" value="<?php echo htmlspecialchars($p['nombre']); ?>">
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea class="input" name="descripcion" rows="4"><?php echo htmlspecialchars($p['descripcion']); ?></textarea>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label>Precio (Q)</label>
          <input class="input" type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($p['precio']); ?>">
          <div class="help">Ej.: 35.00</div>
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input class="input" type="number" name="stock" value="<?php echo htmlspecialchars($p['stock']); ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Categoría</label>
        <select class="input" name="categoria_id">
          <option value="">— Selecciona —</option>
          <?php foreach($categorias as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php if ($p['categoria_id'] == $c['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($c['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-actions">
        <button class="btn primary" type="submit">Actualizar</button>
        <a class="btn outline" href="<?php echo BASE_URL; ?>/admin/productos.php">Cancelar</a>
      </div>
    </div>

    <!-- Columna derecha: imagen -->
    <aside>
      <div class="form-group">
        <label>Imagen actual</label>
        <?php if ($src): ?>
          <div class="preview-box">
            <img src="<?php echo htmlspecialchars($src); ?>" alt="">
          </div>
        <?php else: ?>
          <div class="help">Este producto no tiene imagen.</div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label>Reemplazar imagen (opcional)</label>
        <input class="input" type="file" name="imagen" accept="image/*">
        <div class="help">Máx. 2MB. Formatos: JPG, PNG, WEBP o GIF.</div>
      </div>
    </aside>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
