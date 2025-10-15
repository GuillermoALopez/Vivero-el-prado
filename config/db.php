<?php
require_once __DIR__ . '/config.php';

// 🔌 Conectar a la base de datos con PDO
// Conectar a la base de datos con PDO usando DATABASE_URL
function obtenerConexion() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Lee la variable de entorno principal de la base de datos de Railway
            $dbUrl = getenv('MYSQL_URL');

            if ($dbUrl === false) {
                // Si no estamos en Railway, usamos las constantes locales
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                // Estamos en Railway, "desarmamos" la URL de conexión
                $urlInfo = parse_url($dbUrl);
                
                $host = $urlInfo['host'];
                $port = $urlInfo['port'];
                $user = $urlInfo['user'];
                $pass = $urlInfo['pass'];
                $dbName = ltrim($urlInfo['path'], '/'); // Quita la barra inicial del nombre

                $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
                
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
        } catch (PDOException $e) {
            error_log('Error de conexión a la BD: ' . $e->getMessage());
            die('Error: No se pudo conectar con la base de datos.');
        }
    }
    return $pdo;
}

// 🔒 Iniciar sesión segura
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 🧑 Requiere usuario logueado
function requiereLogin() {
    iniciarSesion();
    if (!isset($_SESSION['usuario'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// 👨‍💼 Requiere ser administrador
function requiereAdmin() {
    iniciarSesion();
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
        header('Location: ' . BASE_URL . '/public/index.php');
        exit;
    }
}
