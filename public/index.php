<?php

// Desactivar cualquier almacenamiento en búfer para ver la salida inmediatamente
ob_implicit_flush(true);

echo "<h1>Diagnóstico de Variables de Entorno</h1>";
echo "<pre>"; // Usamos <pre> para que el formato sea más legible

// La variable más importante: el host de la base de datos
$db_host = getenv('MYSQLHOST');

echo "Intentando leer la variable MYSQLHOST...\n\n";

echo "El tipo de dato es: " . gettype($db_host) . "\n";
echo "El valor es: ";
var_dump($db_host);

echo "\n\n";

if ($db_host) {
    echo "✅ ¡ÉXITO! PHP está leyendo las variables de entorno correctamente.";
} else {
    echo "❌ ¡FALLO! PHP NO está leyendo las variables de entorno. Revisa que el nombre de la variable en Railway sea exactamente 'MYSQLHOST'.";
}

echo "</pre>";

// Detenemos la ejecución aquí para no hacer nada más
exit;

?>
