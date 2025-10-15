<?php


require_once __DIR__ . '/../config/db.php';
requiereAdmin();
$bd = obtenerConexion();

$categorias = $bd->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();

$errores = [];
$imagen_ruta = null; // ← aquí guardaremos /uploads/archivo.ext si subes imagen

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) Tomar campos
  $nombre       = trim($_POST['nombre'] ?? '');
  $descripcion  = trim($_POST['descripcion'] ?? '');
  $precio       = (float)($_POST['precio'] ?? 0);
  $stock        = (int)($_POST['stock'] ?? 0);
  $categoria_id = $_POST['categoria_id'] !== '' ? (int)$_POST['categoria_id'] : null;

  // 2) Validaciones
  if ($nombre === '') $errores[] = 'El nombre es obligatorio';
  if ($precio <= 0)   $errores[] = 'El precio debe ser mayor a 0';

  // 3) *** LÓGICA DE SUBIDA/VALIDACIÓN/GUARDADO DE LA IMAGEN ***
  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
      $errores[] = 'Error al subir la imagen (código '.$_FILES['imagen']['error'].')';
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
          $errores[] = 'Formato no permitido (usa JPG, PNG, WEBP o GIF)';
        } else {
          $uploadsAbs = __DIR__ . '/../public/uploads';
          if (!is_dir($uploadsAbs)) mkdir($uploadsAbs, 0777, true);

          $ext = $permitidos[$mime];
          $nombreSeguro = bin2hex(random_bytes(8)) . '.' . $ext;
          $destinoAbs = $uploadsAbs . '/' . $nombreSeguro;

          if (!move_uploaded_file($tmp, $destinoAbs)) {
            $errores[] = 'No se pudo guardar la imagen en el servidor';
          } else {
            // Guardamos la ruta relativa desde /public
            $imagen_ruta = '/uploads/' . $nombreSeguro;
          }
        }
      }
    }
  }
  // *** FIN LÓGICA DE SUBIDA ***

  // 4) Insertar si todo está ok
  if (!$errores) {
    $stmt = $bd->prepare("
      INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen_url)
      VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria_id, $imagen_ruta]);
    header('Location: ' . BASE_URL . '/admin/productos.php');
    exit;
  }
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Nuevo producto</h1>

<?php foreach($errores as $e): ?>
  <p style="color:#e11d48"><?php echo htmlspecialchars($e); ?></p>
<?php endforeach; ?>

<!-- IMPORTANTE: enctype para subir archivos -->
<form method="post" enctype="multipart/form-data">
  <label>Nombre</label>
  <input class="input" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">

  <label>Descripción</label>
  <textarea class="input" name="descripcion"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>

  <label>Precio (Q)</label>
  <input class="input" type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>">

  <label>Stock</label>
  <input class="input" type="number" name="stock" value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>">

  <label>Categoría</label>
  <select class="input" name="categoria_id">
    <option value="">-- Selecciona --</option>
    <?php foreach($categorias as $c): ?>
      <option value="<?php echo $c['id']; ?>" <?php if(($_POST['categoria_id'] ?? '') == $c['id']) echo 'selected'; ?>>
        <?php echo htmlspecialchars($c['nombre']); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Imagen (opcional)</label>
  <input class="input" type="file" name="imagen" accept="image/*">

  <button class="btn primary" type="submit">Guardar</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
