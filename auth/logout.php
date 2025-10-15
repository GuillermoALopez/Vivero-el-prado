<?php
require_once __DIR__ . '/../config/db.php'; // <-- importante: aquí está iniciarSesion()
iniciarSesion();                            // inicia/asegura la sesión
session_destroy();                          // cierra la sesión
header('Location: ' . BASE_URL . '/public/index.php');
exit;

