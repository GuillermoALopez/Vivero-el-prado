<?php
require_once __DIR__ . '/../config/db.php';

$db = obtenerConexion();

echo "<h1>✅ Conexión exitosa a la base de datos</h1>";

$roles = $db->query("SELECT * FROM roles")->fetchAll();

echo "<pre>";
print_r($roles);
echo "</pre>";
