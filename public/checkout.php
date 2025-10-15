<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';

iniciarSesion();

$usr = $_SESSION['usuario'] ?? null;
if (!$usr) {
  $_SESSION['flash'] = 'Inicia sesión para finalizar la compra.';
  // si tienes un login con redirect, úsalo:
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}

$bd = obtenerConexion();

$items = cart_items_with_products($bd);
if (!$items) {
  $_SESSION['flash'] = 'Tu carrito está vacío.';
  header('Location: ' . BASE_URL . '/public/carrito.php');
  exit;
}

$total = cart_total($bd);

try {
  $bd->beginTransaction();

  // 1) Crear pedido
  $insPedido = $bd->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)');
  $insPedido->execute([(int)$usr['id'], $total]);
  $pedidoId = (int)$bd->lastInsertId();

  // 2) Insertar detalles y descontar stock
  $insDet   = $bd->prepare('
    INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio)
    VALUES (?, ?, ?, ?)
  ');
  $updStock = $bd->prepare('
    UPDATE productos
       SET stock = stock - ?
     WHERE id = ? AND stock >= ?
  ');

  foreach ($items as $it) {
    // valida stock
    if ((int)$it['stock'] < (int)$it['cantidad']) {
      throw new Exception('Stock insuficiente para: ' . $it['nombre']);
    }

    $insDet->execute([$pedidoId, (int)$it['id'], (int)$it['cantidad'], (float)$it['precio']]);

    $updStock->execute([(int)$it['cantidad'], (int)$it['id'], (int)$it['cantidad']]);
    if ($updStock->rowCount() === 0) {
      throw new Exception('No se pudo descontar stock para: ' . $it['nombre']);
    }
  }

  $bd->commit();
  cart_clear();

  header('Location: ' . BASE_URL . '/public/pedido_exito.php?id=' . $pedidoId);
  exit;

} catch (Exception $e) {
  if ($bd->inTransaction()) $bd->rollBack();
  $_SESSION['flash'] = 'No se pudo completar el pedido: ' . $e->getMessage();
  header('Location: ' . BASE_URL . '/public/carrito.php');
  exit;
}
