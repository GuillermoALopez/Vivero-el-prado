<?php
// /config/cart.php
require_once __DIR__ . '/db.php';

const CART_KEY = 'cart';

function cart_start(): void {
  iniciarSesion();
  if (!isset($_SESSION[CART_KEY]) || !is_array($_SESSION[CART_KEY])) {
    $_SESSION[CART_KEY] = [];
  }
}

/** Devuelve array [producto_id => cantidad] */
function cart_get(): array {
  cart_start();
  return $_SESSION[CART_KEY];
}

/** Cantidad total de unidades en el carrito */
function cart_count(): int {
  $c = 0;
  foreach (cart_get() as $qty) $c += (int)$qty;
  return $c;
}

/** Agrega o incrementa cantidad. Respeta límites 1..99 (y stock si se valida). */
function cart_add(int $productId, int $qty = 1): void {
  cart_start();
  $qty = max(1, min(99, $qty));
  if (isset($_SESSION[CART_KEY][$productId])) {
    $_SESSION[CART_KEY][$productId] += $qty;
  } else {
    $_SESSION[CART_KEY][$productId] = $qty;
  }
  // Límite superior duro por si acaso:
  $_SESSION[CART_KEY][$productId] = min(99, $_SESSION[CART_KEY][$productId]);
}

/** Fija cantidad exacta; si es 0 o menor, elimina. */
function cart_set(int $productId, int $qty): void {
  cart_start();
  $qty = (int)$qty;
  if ($qty <= 0) unset($_SESSION[CART_KEY][$productId]);
  else $_SESSION[CART_KEY][$productId] = min(99, $qty);
}

/** Elimina un producto del carrito */
function cart_remove(int $productId): void {
  cart_start();
  unset($_SESSION[CART_KEY][$productId]);
}

/** Vacía el carrito */
function cart_clear(): void {
  cart_start();
  $_SESSION[CART_KEY] = [];
}

/**
 * Retorna los ítems del carrito enriquecidos con datos del producto:
 * [
 *   ['id'=>..,'nombre'=>..,'precio'=>..,'stock'=>..,'imagen_url'=>..,'cantidad'=>..,'subtotal'=>..],
 *   ...
 * ]
 */
function cart_items_with_products(PDO $bd): array {
  cart_start();
  $items = cart_get();
  if (!$items) return [];

  $ids = array_map('intval', array_keys($items));
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $st = $bd->prepare("
    SELECT id, nombre, precio, stock, imagen_url
    FROM productos
    WHERE id IN ($placeholders)
  ");
  $st->execute($ids);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Indexar por id para unir cantidades
  $map = [];
  foreach ($rows as $r) $map[$r['id']] = $r;

  $out = [];
  foreach ($items as $pid => $qty) {
    if (!isset($map[$pid])) continue; // por si algún producto fue eliminado
    $p = $map[$pid];
    $cantidad = max(1, min(99, (int)$qty));
    // (Opcional) respetar stock: $cantidad = min($cantidad, (int)$p['stock']);
    $subtotal = (float)$p['precio'] * $cantidad;

    $out[] = [
      'id'         => (int)$p['id'],
      'nombre'     => $p['nombre'],
      'precio'     => (float)$p['precio'],
      'stock'      => (int)$p['stock'],
      'imagen_url' => $p['imagen_url'],
      'cantidad'   => $cantidad,
      'subtotal'   => $subtotal,
    ];
  }
  return $out;
}

/** Total $ (suma de subtotales) */
function cart_total(PDO $bd): float {
  $sum = 0.0;
  foreach (cart_items_with_products($bd) as $it) {
    $sum += $it['subtotal'];
  }
  return $sum;
}
