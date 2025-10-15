<?php


require_once __DIR__ . '/../config/db.php';
requiereAdmin();
$bd = obtenerConexion();

/* ------------------------------- PAGINACIÓN ------------------------------- */
$porPagina     = 10;
$paginaActual  = max(1, (int)($_GET['pagina'] ?? 1));
$mostrarTodos  = isset($_GET['todos']) && $_GET['todos'] === '1';

/* Total de productos (para calcular páginas) */
$totalProductos = (int)$bd->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$totalPaginas   = max(1, (int)ceil($totalProductos / $porPagina));

/* Ajusta página si se pasó del total */
if (!$mostrarTodos && $paginaActual > $totalPaginas) {
  $paginaActual = $totalPaginas;
}

/* Offset para LIMIT */
$offset = ($paginaActual - 1) * $porPagina;

/* Helper para URLs de paginación (conserva otros parámetros si agregas filtros después) */
function url_pagina(int $p): string {
  return BASE_URL . '/admin/productos.php?pagina=' . $p;
}
function url_todos(): string {
  return BASE_URL . '/admin/productos.php?todos=1';
}
function url_paginar(): string {
  return BASE_URL . '/admin/productos.php';
}

/* ------------------------------ CONSULTA LISTA ---------------------------- */
if ($mostrarTodos) {
  $sql = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    ORDER BY p.id DESC
  ";
} else {
  /* Embebemos enteros ya saneados para evitar problemas con LIMIT/OFFSET */
  $limit = (int)$porPagina;
  $off   = max(0, (int)$offset);
  $sql = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $off
  ";
}
$productos = $bd->query($sql)->fetchAll();

/* Rango mostrado (para el texto “Mostrando X–Y de N”) */
if ($mostrarTodos) {
  $desde = $totalProductos ? 1 : 0;
  $hasta = $totalProductos;
} else {
  $desde = $totalProductos ? ($offset + 1) : 0;
  $hasta = min($offset + $porPagina, $totalProductos);
}

require_once __DIR__ . '/../includes/header.php';

/* Mensaje flash (opcional) */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* Helper URL imagen miniatura */
function url_imagen_admin(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (str_starts_with($img, 'http')) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  return BASE_URL . '/public' . $img;
}
?>
<h1>Productos (Administración)</h1>

<?php if ($flash): ?>
  <p class="flash-success"><?php echo htmlspecialchars($flash); ?></p>
<?php endif; ?>

<a class="btn primary" href="<?php echo BASE_URL; ?>/admin/producto_crear.php">Nuevo producto</a>

<?php if (!$productos): ?>
  <p style="margin-top:12px">Aún no hay productos. Crea el primero con el botón <strong>“Nuevo producto”</strong>.</p>
<?php else: ?>

<div class="table-card" style="margin-top:12px;">
  <table class="tabla-admin">
    <colgroup>
      <col style="width:60px"><!-- ID -->
      <col style="width:72px"><!-- Imagen -->
      <col><!-- Nombre -->
      <col style="width:260px"><!-- Categoría -->
      <col style="width:120px"><!-- Precio -->
      <col style="width:100px"><!-- Stock -->
      <col style="width:200px"><!-- Acciones -->
    </colgroup>
    <thead>
      <tr>
        <th>ID</th>
        <th>Imagen</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Precio</th>
        <th>Stock</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p): ?>
        <tr>
          <td><?php echo (int)$p['id']; ?></td>
          <td>
            <?php $src = url_imagen_admin($p['imagen_url'] ?? null); ?>
            <?php if ($src): ?>
              <img class="thumb" src="<?php echo htmlspecialchars($src); ?>" alt="">
            <?php else: ?>
              <span style="color:#64748b">—</span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($p['nombre']); ?></td>
          <td><?php echo htmlspecialchars($p['categoria'] ?? ''); ?></td>
          <td>Q <?php echo number_format((float)$p['precio'], 2); ?></td>
          <td><?php echo (int)$p['stock']; ?></td>
          <td style="white-space:nowrap">
            <a class="btn" href="<?php echo BASE_URL; ?>/admin/producto_editar.php?id=<?php echo (int)$p['id']; ?>">Editar</a>
            <a class="btn danger" href="<?php echo BASE_URL; ?>/admin/producto_eliminar.php?id=<?php echo (int)$p['id']; ?>" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Barra de paginación -->
<div class="paginacion">
  <span class="muted">Mostrando <?php echo $desde; ?>–<?php echo $hasta; ?> de <?php echo $totalProductos; ?></span>

  <?php if ($mostrarTodos): ?>
    <a class="page" href="<?php echo url_paginar(); ?>">Volver a paginar</a>
  <?php else: ?>
    <!-- Anterior -->
    <?php if ($paginaActual > 1): ?>
      <a class="prevnext" href="<?php echo url_pagina($paginaActual - 1); ?>">&laquo; Anterior</a>
    <?php else: ?>
      <span class="prevnext disabled">&laquo; Anterior</span>
    <?php endif; ?>

    <!-- Números (máximo 7 visibles: 3 antes, actual, 3 después) -->
    <?php
      $ini = max(1, $paginaActual - 3);
      $fin = min($totalPaginas, $paginaActual + 3);
      for ($i = $ini; $i <= $fin; $i++):
    ?>
      <?php if ($i === $paginaActual): ?>
        <span class="page current"><?php echo $i; ?></span>
      <?php else: ?>
        <a class="page" href="<?php echo url_pagina($i); ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <!-- Siguiente -->
    <?php if ($paginaActual < $totalPaginas): ?>
      <a class="prevnext" href="<?php echo url_pagina($paginaActual + 1); ?>">Siguiente &raquo;</a>
    <?php else: ?>
      <span class="prevnext disabled">Siguiente &raquo;</span>
    <?php endif; ?>

    <!-- Mostrar todos -->
    <a class="page" href="<?php echo url_todos(); ?>">Mostrar todos</a>
  <?php endif; ?>
</div>

<?php endif; // endif productos ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
