<?php
// public/pedido_pdf.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/factura_pdf.php';

iniciarSesion();
$bd   = obtenerConexion();
$usr  = $_SESSION['usuario'] ?? null;

$id = (int)($_GET['id'] ?? 0);
if (!$usr || $id <= 0) { http_response_code(404); exit; }

// Seguridad: dueño o admin
$st  = $bd->prepare("SELECT usuario_id FROM pedidos WHERE id=?");
$st->execute([$id]);
$uid = (int)$st->fetchColumn();

$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$rolId   = $usr['rol_id'] ?? null;
$esAdmin = ($rolId == 1) || ($rolNom === 'admin') || ($rolNom === 'administrador');

if (!$esAdmin && (int)$usr['id'] !== $uid) { http_response_code(403); exit; }

// stream/descarga (inline)
factura_pdf_stream($bd, $id, false);
