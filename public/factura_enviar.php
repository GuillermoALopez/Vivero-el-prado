<?php
// public/factura_enviar.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/mail_factura.php';

iniciarSesion();
$bd   = obtenerConexion();
$usr  = $_SESSION['usuario'] ?? null;

$pedidoId = (int)($_POST['pedido_id'] ?? 0);
$toEmail  = trim($_POST['email'] ?? '');

if (!$usr || $pedidoId <= 0 || $toEmail === '') {
  $_SESSION['flash'] = 'Pedido inválido.';
  header('Location: ' . BASE_URL . '/public/mis_pedidos.php'); exit;
}

// Dueño o admin
$st  = $bd->prepare("SELECT usuario_id FROM pedidos WHERE id=?");
$st->execute([$pedidoId]);
$uid = (int)$st->fetchColumn();

$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$rolId   = $usr['rol_id'] ?? null;
$esAdmin = ($rolId == 1) || ($rolNom === 'admin') || ($rolNom === 'administrador');

if (!$esAdmin && (int)$usr['id'] !== $uid) {
  $_SESSION['flash'] = 'No puedes enviar la factura de este pedido.';
  header('Location: ' . BASE_URL . '/public/mis_pedidos.php'); exit;
}

$adminCopy = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
$res = enviar_factura_por_correo($bd, $pedidoId, $toEmail, $usr['nombre'] ?? '', $adminCopy);

$_SESSION['flash'] = $res['ok']
  ? 'Factura enviada a tu correo.'
  : ('No se pudo enviar la factura: ' . ($res['error'] ?? 'Error desconocido'));

header('Location: ' . BASE_URL . '/public/pedido_exito.php?id=' . $pedidoId);
$return = trim($_POST['return'] ?? '');
if ($return !== '') { header('Location: ' . $return); exit; }

exit;
