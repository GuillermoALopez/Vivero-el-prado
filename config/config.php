<?php
// config/config.php
require_once __DIR__ . '/../vendor/autoload.php'; 


define('MAIL_FROM_EMAIL', 'madelynlotzoj2@gmail.com');
define('MAIL_FROM_NAME',  'Vivero El Prado');
define('ADMIN_EMAIL',     'madelynlotzoj2@gmail.com'); // copia a admin

define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_USER',   'madelynlotzoj2@gmail.com');
define('SMTP_PASS',   'wfsbxomlytwukiav'); // <-- la de 16 caracteres
define('SMTP_PORT',   587);
define('SMTP_SECURE', 'tls');


// ⚙️ Configuración de la base de datos
define('DB_HOST', 'localhost');         // Servidor MySQL
define('DB_NAME', 'vivero_el_prado');   // Nombre de la base de datos
define('DB_USER', 'root');              // Usuario (por defecto en XAMPP es root)
define('DB_PASS', '');                  // Contraseña (vacía en XAMPP por defecto)

// 🌐 URL base del proyecto
define('BASE_URL', '/vivero_el_prado');
// Construir DSN
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// Si ya existe, respeta; si no, crea el PDO y déjalo en $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $pdo = new PDO(
    $dsn,
    DB_USER,
    DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
}

// También expón un helper opcional
if (!function_exists('db')) {
  function db(): PDO { global $pdo; return $pdo; }
}

// Correo del administrador para copia de facturas
if (!defined('ADMIN_EMAIL')) {
  define('ADMIN_EMAIL', 'admin@vivero.com'); // cámbialo por el tuyo
}

