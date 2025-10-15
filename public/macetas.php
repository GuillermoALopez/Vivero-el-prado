<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$bd = obtenerConexion();

/* ====== RESOLVER TIPO (macetas) ====== */
$tipoSlug = 'macetas';
$st = $bd->prepare("SELECT id, nombre FROM tipos_producto WHERE slug = ?");
$st->execute([$tipoSlug]);
$tipo = $st->fetch(PDO::FETCH_ASSOC);
if (!$tipo) {
  echo "<h1>Macetas</h1><p>No existe el tipo 'macetas'.</p>";
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}
$tipoId = (int)$tipo['id'];

/* ====== CATEGORÍAS DEL TIPO ====== */
$categorias = $bd->prepare("SELECT id, nombre FROM categorias WHERE tipo_id = ? ORDER BY nombre");
$categorias->execute([$tipoId]);
$categorias = $categorias->fetchAll();

$categoriaSeleccionada = (int)($_GET['categoria_id'] ?? 0);
if ($categoriaSeleccionada < 0) $categoriaSeleccionada = 0;

/* ====== BÚSQUEDA ====== */
$q = trim($_GET['q'] ?? '');

/* ====== PAGINACIÓN ====== */
$porPagina    = 12;
$paginaActual = max(1, (int)($_GET['pagina'] ?? 1));
$mostrarTodos = isset($_GET['todos']) && $_GET['todos'] === '1';

/* ====== WHERE dinámico ====== */
$whereParts = ['c.tipo_id = ?'];
$params = [$tipoId];

if ($categoriaSeleccionada > 0) {
  $whereParts[] = 'p.categoria_id = ?';
  $params[] = $categoriaSeleccionada;
}
if ($q !== '') {
  $whereParts[] = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
  $like = "%$q%";
  $params[] = $like; $params[] = $like;
}
$where = ' WHERE ' . implode(' AND ', $whereParts);

/* ====== Total p/ páginas ====== */
$stmt = $bd->prepare("SELECT COUNT(*) FROM productos p INNER JOIN categorias c ON c.id=p.categoria_id $where");
$stmt->execute($params);
$totalProductos = (int)$stmt->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalProductos / $porPagina));
if (!$mostrarTodos && $paginaActual > $totalPaginas) { $paginaActual = $totalPaginas; }
$offset = ($paginaActual - 1) * $porPagina;

/* ====== URLs (mismo look del catálogo) ====== */
$baseParams = [];
if ($categoriaSeleccionada > 0) $baseParams['categoria_id'] = $categoriaSeleccionada;
if ($q !== '') $baseParams['q'] = $q;

function build_url(array $extra = []): string {
  $base = BASE_URL . '/public/macetas.php'; // <- archivo actual
  $bp = $GLOBALS['baseParams'];
  $params = array_merge($bp, $extra);
  return $base . (count($params) ? ('?' . http_build_query($params)) : '');
}
function url_pagina_catalogo(int $p): string { return build_url(['pagina' => $p]); }
function url_todos_catalogo(): string       { return build_url(['todos' => 1]); }
function url_paginar_catalogo(): string     { return build_url([]); }

/* ====== Consulta de productos ====== */
if ($mostrarTodos) {
  $sql = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c ON c.id = p.categoria_id
    $where
    ORDER BY p.id DESC
  ";
  $stmt = $bd->prepare($sql);
  $stmt->execute($params);
} else {
  $limit = (int)$porPagina;
  $off   = max(0, (int)$offset);
  $sql = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c ON c.id = p.categoria_id
    $where
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $off
  ";
  $stmt = $bd->prepare($sql);
  $stmt->execute($params);
}
$productos = $stmt->fetchAll();

/* ====== Rango mostrado ====== */
if ($mostrarTodos) { $desde = $totalProductos ? 1 : 0; $hasta = $totalProductos; }
else { $desde = $totalProductos ? ($offset + 1) : 0; $hasta = min($offset + $porPagina, $totalProductos); }

/* ====== Helper imagen ====== */
function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}
?>

<h1>Macetas</h1>

<!-- Filtros + Búsqueda (MISMAS CLASES/ESTILOS QUE CATÁLOGO) -->
<form method="get" class="searchbar" style="margin-bottom:12px">
  <input type="hidden" name="pagina" value="<?php echo (int)$paginaActual; ?>">
  <select class="input" name="categoria_id" style="max-width:260px">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $c): ?>
      <option value="<?php echo (int)$c['id']; ?>" <?php if ($categoriaSeleccionada === (int)$c['id']) echo 'selected'; ?>>
        <?php echo htmlspecialchars($c['nombre']); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <div class="search-input">
    <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/>
    </svg>
    <input class="input" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>"
           placeholder="Buscar por nombre o descripción...">
  </div>

  <button class="search-btn" type="submit">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" style="fill:var(--green-600)">
      <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/>
    </svg>
    Buscar
  </button>

  <?php if ($categoriaSeleccionada > 0 || $q !== ''): ?>
    <a class="btn" href="<?php echo BASE_URL; ?>/public/macetas.php">Limpiar</a>
  <?php endif; ?>
</form>

<?php if (!$productos): ?>
  <p>No se encontraron productos.</p>
<?php else: ?>
<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
  <?php foreach($productos as $prod): ?>
    <div class="card" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff">
      <?php $src = url_imagen($prod['imagen_url'] ?? null); ?>
      <div style="width:100%;height:160px;background:#eef1ef">
        <?php if ($src): ?>
          <img src="<?php echo htmlspecialchars($src); ?>"
               alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
               style="display:block;width:100%;height:160px;object-fit:cover">
        <?php endif; ?>
      </div>
      <div class="p" style="padding:12px">
        <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
        <p><?php echo htmlspecialchars($prod['categoria'] ?? 'Sin categoría'); ?></p>
        <?php if ($prod['precio'] !== null): ?>
          <p><strong>Q <?php echo number_format((float)$prod['precio'], 2); ?></strong></p>
        <?php endif; ?>
        <a class="btn"
           href="<?php
             $paramsDetalle = $baseParams + ['pagina'=>$paginaActual];
             echo BASE_URL . '/public/producto.php?id=' . (int)$prod['id'] . '&' . http_build_query($paramsDetalle);
           ?>">Ver</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Paginación (misma UI) -->
<div class="paginacion" style="margin-top:12px">
  <span class="muted">Mostrando <?php echo $desde; ?>–<?php echo $hasta; ?> de <?php echo $totalProductos; ?></span>

  <?php if ($mostrarTodos): ?>
    <a class="page" href="<?php echo url_paginar_catalogo(); ?>">Volver a paginar</a>
  <?php else: ?>
    <?php if ($paginaActual > 1): ?>
      <a class="prevnext" href="<?php echo url_pagina_catalogo($paginaActual - 1); ?>">&laquo; Anterior</a>
    <?php else: ?><span class="prevnext disabled">&laquo; Anterior</span><?php endif; ?>

    <?php
      $ini = max(1, $paginaActual - 3);
      $fin = min($totalPaginas, $paginaActual + 3);
      for ($i = $ini; $i <= $fin; $i++):
    ?>
      <?php if ($i === $paginaActual): ?>
        <span class="page current"><?php echo $i; ?></span>
      <?php else: ?>
        <a class="page" href="<?php echo url_pagina_catalogo($i); ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($paginaActual < $totalPaginas): ?>
      <a class="prevnext" href="<?php echo url_pagina_catalogo($paginaActual + 1); ?>">Siguiente &raquo;</a>
    <?php else: ?><span class="prevnext disabled">Siguiente &raquo;</span><?php endif; ?>

    <a class="page" href="<?php echo url_todos_catalogo(); ?>">Mostrar todos</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
