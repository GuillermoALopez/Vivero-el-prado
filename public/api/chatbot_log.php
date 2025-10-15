<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$bd = obtenerConexion();
$usr = $_SESSION['usuario'] ?? null;
$userId = $usr['id'] ?? null;

$data = json_decode(file_get_contents('php://input'), true);
$role = ($data['role'] ?? '') === 'bot' ? 'bot' : 'user';
$msg  = trim($data['message'] ?? '');

if ($msg !== '') {
  $st = $bd->prepare("INSERT INTO chatbot_logs (user_id, role, message) VALUES (?,?,?)");
  $st->execute([$userId, $role, $msg]);
}

header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
