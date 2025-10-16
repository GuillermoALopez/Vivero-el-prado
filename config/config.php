<?php
// /config/config.php - VERSIÓN FINAL Y CORRECTA ✅

// Cargar todas las dependencias de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Comprobar si estamos en el entorno de Railway
if (getenv('RAILWAY_ENVIRONMENT')) {
    // --- ESTAMOS EN LA NUBE (RAILWAY) ---
    // En Railway, la URL base es la raíz del dominio.
    define('BASE_URL', '');

} else {
    // --- ESTAMOS EN LOCAL (XAMPP) ---
    // En local, la URL base apunta a la carpeta public.
    define('BASE_URL', '/vivero_el_prado/public');
}
    // --- ESTAMOS EN LOCAL (XAMPP) ---

    // Configuración de la base de datos local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'vivero_el_prado');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // Configuración de email local
    define('SMTP_HOST',   'smtp.gmail.com');
    define('SMTP_USER',   'madelynlotzoj2@gmail.com');
    define('SMTP_PASS',   'wfsbxomlytwukiav');
    define('SMTP_PORT',   587);
    define('SMTP_SECURE', 'tls');

    // ¡LA LÍNEA CLAVE! La URL base en local incluye el nombre de tu carpeta.
    define('BASE_URL', '/vivero_el_prado/public');



define('MAIL_FROM_EMAIL', 'madelynlotzoj2@gmail.com');
define('MAIL_FROM_NAME',  'Vivero El Prado');
define('ADMIN_EMAIL',     'madelynlotzoj2@gmail.com');
