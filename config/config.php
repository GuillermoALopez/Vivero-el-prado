<?php
// /config/config.php - CORRECTO para Railway y Local ✅

// Cargar todas las dependencias de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Comprobar si estamos en el entorno de Railway
if (getenv('RAILWAY_ENVIRONMENT')) {
    // --- ESTAMOS EN LA NUBE (RAILWAY) ---

    // Configuración de la base de datos con variables de entorno
    define('DB_HOST', getenv('MYSQLHOST'));
    define('DB_NAME', getenv('MYSQLDATABASE'));
    define('DB_USER', getenv('MYSQLUSER'));
    define('DB_PASS', getenv('MYSQLPASSWORD'));

    // Configuración de email con variables de entorno
    define('SMTP_HOST',   getenv('SMTP_HOST'));
    define('SMTP_USER',   getenv('SMTP_USER'));
    define('SMTP_PASS',   getenv('SMTP_PASS'));
    define('SMTP_PORT',   getenv('SMTP_PORT'));
    define('SMTP_SECURE', getenv('SMTP_SECURE'));

    // URL base del proyecto en Railway
    $baseUrl = 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN');
    define('BASE_URL', rtrim($baseUrl, '/'));

} else {
    // --- ESTAMOS EN LOCAL (XAMPP) ---

    // Configuración de la base de datos local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'vivero_el_prado'); // Asegúrate que el nombre sea correcto
    define('DB_USER', 'root');
    define('DB_PASS', ''); // O la contraseña que uses en XAMPP

    // Configuración de email local
    define('SMTP_HOST',   'smtp.gmail.com');
    define('SMTP_USER',   'madelynlotzoj2@gmail.com');
    define('SMTP_PASS',   'wfsbxomlytwukiav'); // Tu contraseña de aplicación de Gmail
    define('SMTP_PORT',   587);
    define('SMTP_SECURE', 'tls');

    // URL base del proyecto en local
    define('BASE_URL', '/vivero_el_prado'); // Asegúrate que la ruta sea correcta
}

// Constantes de email que no cambian
define('MAIL_FROM_EMAIL', 'madelynlotzoj2@gmail.com');
define('MAIL_FROM_NAME',  'Vivero El Prado');
define('ADMIN_EMAIL',     'madelynlotzoj2@gmail.com');
