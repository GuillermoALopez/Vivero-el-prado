<?php
require_once __DIR__ . '/config.php';

// 🔌 Conectar a la base de datos con PDO
function obtenerConexion() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('❌ Error de conexión: ' . $e->getMessage());
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
