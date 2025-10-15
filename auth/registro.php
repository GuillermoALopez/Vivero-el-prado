
<?php
require_once __DIR__ . '/../config/db.php';
$bd = obtenerConexion();
iniciarSesion();

$errores = [];
$exito = $_SESSION['flash'] ?? null; // por si vienes de otro flujo
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    // Validaciones básicas
    if ($nombre === '' || $correo === '' || $contrasena === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    }
    if (strlen($contrasena) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    // ¿Correo duplicado?
    if (!$errores) {
        $stmt = $bd->prepare('SELECT id FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = 'Ese correo ya está registrado.';
        }
    }

    // Insertar si todo bien (rol cliente por defecto = 2)
    if (!$errores) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $bd->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol_id) VALUES (?,?,?,2)');
        $stmt->execute([$nombre, $correo, $hash]);

        $_SESSION['flash'] = 'Registro exitoso. Ahora inicia sesión.';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Registro</h1>

<?php if ($exito): ?>
  <p style="color:green"><?php echo htmlspecialchars($exito); ?></p>
<?php endif; ?>

<?php foreach($errores as $e): ?>
  <p style="color:#e11d48"><?php echo htmlspecialchars($e); ?></p>
<?php endforeach; ?>

<form method="post" autocomplete="off">
  <label>Nombre</label>
  <input class="input" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">

  <label>Correo</label>
  <input class="input" name="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">

  <label>Contraseña</label>
  <input class="input" type="password" name="contrasena">

  <button class="btn primary" type="submit">Crear cuenta</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
