<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$bd = obtenerConexion();
$productos = $bd->query("SELECT id, nombre, descripcion, precio, imagen_url FROM productos ORDER BY creado_en DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($productos);