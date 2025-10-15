<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$bd = obtenerConexion();

/* -------------------------------------------------
   PARÁMETROS
------------------------------------------------- */
$tipoSlug     = isset($_GET['tipo']) ? trim($_GET['tipo']) : null; // plantas | macetas | abono | tierra | null
$categoria    = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? (int)$_GET['categoria_id'] : null;
$q            = trim($_GET['q'] ?? '');
$pagina       = max(1, (int)($_GET['page'] ?? $_GET['pagina'] ?? 1)); // acepta ?page= o ?pagina=
$mostrarTodos = isset($_GET['todos']) && $_GET['todos'] === '1';
$porPagina    = 12;
$offset       = ($pagina - 1) * $porPagina;

/* -------------------------------------------------
   RESOLVER TIPO (si viene ?tipo=)
------------------------------------------------- */
$tipo = null; $tipoId = null; $titulo = 'Catálogo';
if ($tipoSlug) {
  $st = $bd->prepare("SELECT id, nombre, slug FROM tipos_producto WHERE slug = ? LIMIT 1");
  $st->execute([$tipoSlug]);
  $tipo = $st->fetch(PDO::FETCH_ASSOC);
  if ($tipo) { $tipoId = (int)$tipo['id']; $titulo = $tipo['nombre']; }
}

/* -------------------------------------------------
   CARGAR CATEGORÍAS DEL TIPO (si hay tipo)
------------------------------------------------- */
$categorias = [];
if ($tipoId) {
  $cs = $bd->prepare("SELECT id, nombre FROM categorias WHERE tipo_id = ? ORDER BY nombre");
  $cs->execute([$tipoId]);
  $categorias = $cs->fetchAll(PDO::FETCH_ASSOC);
}

/* -------------------------------------------------
   WHERE DINÁMICO
------------------------------------------------- */
$where  = [];
$params = [];

if ($tipoId) {
  $where[] = "c.tipo_id = :tid";
  $params[':tid'] = $tipoId;
}
if ($categoria) {
  $where[] = "p.categoria_id = :cat";
  $params[':cat'] = $categoria;
}
if ($q !== '') {
  $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q)";
  $params[':q'] = "%{$q}%";
}
$W = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* -------------------------------------------------
   TOTAL Y PAGINACIÓN
------------------------------------------------- */
$sqlCount = "SELECT COUNT(*)
             FROM productos p
             INNER JOIN categorias c ON c.id = p.categoria_id
             $W";
$sc = $bd->prepare($sqlCount);
$sc->execute($params);

$total   = (int)$sc->fetchColumn();
$paginas = max(1, (int)ceil($total / $porPagina));

if (!$mostrarTodos && $pagina > $paginas) {
  $pagina = $paginas;
  $offset = ($pagina - 1) * $porPagina;
}

/* -------------------------------------------------
   TRAER PRODUCTOS (con/sin LIMIT)
------------------------------------------------- */
$sqlBase = "SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            $W
            ORDER BY p.creado_en DESC, p.id DESC";

if ($mostrarTodos) {
  $sp = $bd->prepare($sqlBase);
  foreach ($params as $k => $v) $sp->bindValue($k, $v);
} else {
  $sql = $sqlBase . " LIMIT :lim OFFSET :off";
  $sp  = $bd->prepare($sql);
  foreach ($params as $k => $v) $sp->bindValue($k, $v);
  $sp->bindValue(':lim', $porPagina, PDO::PARAM_INT);
  $sp->bindValue(':off', $offset, PDO::PARAM_INT);
}
$sp->execute();
$productos = $sp->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------------
   RANGO “Mostrando A–B de N”
------------------------------------------------- */
if ($mostrarTodos) {
  $desde = $total ? 1 : 0;
  $hasta = $total;
} else {
  $desde = $total ? ($offset + 1) : 0;
  $hasta = min($offset + $porPagina, $total);
}

/* -------------------------------------------------
   HELPERS
------------------------------------------------- */
function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}

function url_keep(array $extra = [], string $base = 'catalogo.php'): string {
  $keep = [];
  foreach (['tipo','categoria_id','q'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $keep[$k] = $_GET[$k];
  }
  $params = array_merge($keep, $extra);
  $qs = $params ? ('?' . http_build_query($params)) : '';
  return BASE_URL . '/public/' . $base . $qs;
}

function url_pagina_catalogo(int $p): string { return url_keep(['page' => $p]); }
function url_todos_catalogo(): string       { return url_keep(['todos' => 1]); }
function url_paginar_catalogo(): string     { return url_keep([]); } // limpia “todos”

/* -------------------------------------------------
   FLASH
------------------------------------------------- */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<h1 style="margin-top:14px"><?php echo htmlspecialchars($titulo); ?></h1>

<?php if ($flash): ?>
  <p class="flash-success"><?php echo htmlspecialchars($flash); ?></p>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" action="<?php echo BASE_URL; ?>/public/catalogo.php" class="filtros" style="display:flex;gap:12px;align-items:center;margin:12px 0;">
  <?php if ($tipoId): ?>
    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipoSlug); ?>">
    <select class="input" name="categoria_id" style="max-width:260px">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $cat): $cid=(int)$cat['id']; ?>
        <option value="<?php echo $cid; ?>" <?php echo ($categoria !== null && (int)$categoria === $cid) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($cat['nombre']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <select class="input" name="tipo" style="max-width:260px">
      <option value="">Todos los tipos</option>
      <option value="plantas" <?php echo ($tipoSlug==='plantas')?'selected':''; ?>>Plantas</option>
      <option value="macetas" <?php echo ($tipoSlug==='macetas')?'selected':''; ?>>Macetas</option>
      <option value="abono"   <?php echo ($tipoSlug==='abono')  ?'selected':''; ?>>Abono</option>
      <option value="tierra"  <?php echo ($tipoSlug==='tierra') ?'selected':''; ?>>Tierra</option>
    </select>
  <?php endif; ?>

  <div class="search-input" style="max-width:360px;flex:1 1 260px">
    <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/></svg>
    <input class="input" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por nombre o descripción...">
  </div>

  <button class="search-btn" type="submit">Buscar</button>
  <a class="btn" href="<?php echo url_keep([], 'catalogo.php'); ?>">Limpiar</a>
</form>

<!-- Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;">
  <?php if (!$productos): ?>
    <div>No hay productos para esta búsqueda.</div>
  <?php else: foreach ($productos as $p): $src = url_imagen($p['imagen_url'] ?? null); ?>
    <div style="border-radius:12px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;">
      <?php if ($src): ?>
        <img src="<?php echo htmlspecialchars($src); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" style="width:100%;height:180px;object-fit:cover;">
      <?php else: ?>
        <div style="width:100%;height:180px;background:#eee;"></div>
      <?php endif; ?>

      <div style="padding:14px;">
        <h3 style="margin:0 0 6px;"><?php echo htmlspecialchars($p['nombre']); ?></h3>
        <div style="font-size:.9rem;color:#666;margin-bottom:6px;"><?php echo htmlspecialchars($p['categoria_nombre']); ?></div>
        <?php if ($p['precio'] !== null): ?>
          <div style="font-weight:600;margin-bottom:10px;">Q <?php echo number_format((float)$p['precio'], 2); ?></div>
        <?php endif; ?>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn" href="<?php echo BASE_URL; ?>/public/producto.php?id=<?php echo (int)$p['id']; ?>">Ver</a>

          <!-- Añadir al carrito -->
          <form method="post" action="<?php echo BASE_URL; ?>/public/carrito_agregar.php" style="display:inline">
            <input type="hidden" name="id"  value="<?php echo (int)$p['id']; ?>">
            <input type="hidden" name="qty" value="1">
            <input type="hidden" name="back" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <button class="btn" type="submit">Añadir</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<!-- Paginación -->
<?php if ($paginas > 1 || $mostrarTodos): ?>
  <div class="paginacion" style="margin-top:12px">
    <span class="muted">Mostrando <?php echo $desde; ?>–<?php echo $hasta; ?> de <?php echo $total; ?></span>

    <?php if ($mostrarTodos): ?>
      <a class="page" href="<?php echo url_paginar_catalogo(); ?>">Volver a paginar</a>
    <?php else: ?>
      <?php if ($pagina > 1): ?>
        <a class="prevnext" href="<?php echo url_pagina_catalogo($pagina - 1); ?>">&laquo; Anterior</a>
      <?php else: ?>
        <span class="prevnext disabled">&laquo; Anterior</span>
      <?php endif; ?>

      <?php
        $ini = max(1, $pagina - 3);
        $fin = min($paginas, $pagina + 3);
        for ($i = $ini; $i <= $fin; $i++):
      ?>
        <?php if ($i === $pagina): ?>
          <span class="page current"><?php echo $i; ?></span>
        <?php else: ?>
          <a class="page" href="<?php echo url_pagina_catalogo($i); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($pagina < $paginas): ?>
        <a class="prevnext" href="<?php echo url_pagina_catalogo($pagina + 1); ?>">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="prevnext disabled">Siguiente &raquo;</span>
      <?php endif; ?>

      <a class="page" href="<?php echo url_todos_catalogo(); ?>">Mostrar todos</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
