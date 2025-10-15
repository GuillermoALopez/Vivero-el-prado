<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';

iniciarSesion();

$id   = (int)($_POST['id'] ?? 0);
$qty  = (int)($_POST['qty'] ?? 1);
$qty  = max(1, min(99, $qty)); // 1..99
$back = $_POST['back'] ?? (BASE_URL . '/public/index.php');

if ($id <= 0) {
  $_SESSION['flash'] = 'Producto inválido.';
  header('Location: ' . $back);
  exit;
}

$bd = obtenerConexion();
$st = $bd->prepare('SELECT id, stock FROM productos WHERE id = ? LIMIT 1');
$st->execute([$id]);
$prod = $st->fetch();

if (!$prod) {
  $_SESSION['flash'] = 'El producto no existe.';
  header('Location: ' . $back);
  exit;
}

// (Opcional) Respetar stock
$items = cart_get();             // [id => cantidad]
$ya   = (int)($items[$id] ?? 0);
$max  = max(0, (int)$prod['stock'] - $ya);

if ($max <= 0) {
  $_SESSION['flash'] = 'No hay más stock disponible para este producto.';
  header('Location: ' . $back);
  exit;
}

$agregar = min($qty, $max);
cart_add($id, $agregar);

$_SESSION['flash'] = $agregar < $qty
  ? "Se añadieron $agregar (límite por stock)."
  : 'Producto agregado al carrito.';

header('Location: ' . $back);
exit;
