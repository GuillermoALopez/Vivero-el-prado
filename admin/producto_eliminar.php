<?php



require_once __DIR__ . '/../config/db.php';
requiereAdmin();
$bd = obtenerConexion();

$id = (int)($_GET['id'] ?? 0);
$stmt = $bd->prepare("DELETE FROM productos WHERE id = ?");
$stmt->execute([$id]);

header('Location: ' . BASE_URL . '/admin/productos.php');
exit;
