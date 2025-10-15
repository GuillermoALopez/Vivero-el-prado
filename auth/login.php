<?php
require_once __DIR__ . '/../config/db.php';
$bd = obtenerConexion();
if (function_exists('iniciarSesion')) { iniciarSesion(); } 
else { if (session_status() === PHP_SESSION_NONE) session_start(); }

$errores = [];
$correoPrefill = $_COOKIE['remember_email'] ?? '';

/* --- PROCESAR LOGIN ANTES DE IMPRIMIR HTML --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo      = trim($_POST['correo'] ?? '');
    $contrasena  = (string)($_POST['contrasena'] ?? '');
    $recordarme  = isset($_POST['recordarme']);

    if ($correo === '' || $contrasena === '') {
        $errores[] = 'Ingresa correo y contraseña.';
    } else {
        $stmt = $bd->prepare('
            SELECT u.id, u.nombre, u.correo, u.contrasena, u.rol_id, r.nombre AS rol
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            WHERE u.correo = ?
            LIMIT 1
        ');
        $stmt->execute([$correo]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($contrasena, $u['contrasena'])) {
            $errores[] = 'Credenciales inválidas.';
        } else {
            // Sesión
            $_SESSION['usuario'] = [
                'id'      => (int)$u['id'],
                'nombre'  => $u['nombre'] ?? $u['correo'],
                'correo'  => $u['correo'],
                'rol_id'  => $u['rol_id'] ?? null,
                'rol'     => $u['rol'] ?? ((($u['rol_id'] ?? 0) == 1) ? 'administrador' : 'cliente'),
            ];
            $_SESSION['flash'] = '¡Bienvenido, ' . $_SESSION['usuario']['nombre'] . '!';

            // Recordarme (solo correo)
            if ($recordarme) {
                setcookie('remember_email', $correo, time()+60*60*24*30, "/");
            } else {
                setcookie('remember_email', '', time()-3600, "/");
            }

            // Redirección por rol
            $rolNorm  = strtolower(trim($_SESSION['usuario']['rol'] ?? ''));
            $isAdmin  = (($_SESSION['usuario']['rol_id'] ?? null) == 1) || $rolNorm === 'administrador' || $rolNorm === 'admin';
            $destino  = $isAdmin ? BASE_URL . '/admin/productos.php'
                                 : BASE_URL . '/public/index.php';

            header('Location: ' . $destino);
            exit;
        }
    }

    // Mantén lo que escribió
    $correoPrefill = $correo;
}

/* --- VISTA --- */
require_once __DIR__ . '/../includes/header.php';
$rutaIcono = BASE_URL . '/public/uploads/iconouser.png'; // tu avatar
?>
<div class="auth-center">
  <div class="login-card">
    <div class="login-header">
      <div class="login-avatar">
        <img src="<?php echo htmlspecialchars($rutaIcono); ?>" alt="Usuario">
      </div>
      <h1 class="login-title">Iniciar sesión</h1>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="alert-danger">
        <?php foreach ($errores as $e): ?>
          <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <label>Correo</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/>
        </svg>
        <input class="input has-icon" type="email" name="correo"
               value="<?php echo htmlspecialchars($correoPrefill); ?>"
               placeholder="tucorreo@ejemplo.com" required>
      </div>

      <label>Contraseña</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V6a2 2 0 0 1 4 0v2Zm2 10a2 2 0 1 1 2-2 2 2 0 0 1-2 2Z"/>
        </svg>
        <input class="input has-icon" id="pwd" type="password" name="contrasena"
               placeholder="********" required>
        <button class="eye-btn" type="button" id="togglePwd" aria-label="Mostrar u ocultar contraseña">Mostrar</button>
      </div>

      <div class="login-meta">
        <label style="display:inline-flex;align-items:center;gap:8px">
          <input type="checkbox" name="recordarme" value="1" <?php echo $correoPrefill ? 'checked' : ''; ?>> Recordarme
        </label>
        <a href="#" title="Recuperar contraseña">¿Olvidaste tu contraseña?</a>
      </div>

      <button class="btn primary" type="submit">Ingresar</button>
    </form>
  </div>
</div>

<script>
(function(){
  const pwd = document.getElementById('pwd');
  const btn = document.getElementById('togglePwd');
  if (pwd && btn){
    btn.addEventListener('click', () => {
      const show = pwd.type === 'password';
      pwd.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Ocultar' : 'Mostrar';
      pwd.focus();
    });
  }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
