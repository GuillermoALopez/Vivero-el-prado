<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';
iniciarSesion();

/* -------- Sesión y roles -------- */
$usr     = $_SESSION['usuario'] ?? null;
$nombre = null;
if ($usr) {
    $base   = trim($usr['nombre'] ?? ($usr['correo'] ?? ''));
    $nombre = $base !== '' ? explode(' ', $base)[0] : null;
}
$rolId   = $usr['rol_id'] ?? null;
$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($rolId == 1) || ($rolNom === 'administrador') || ($rolNom === 'admin');

/* -------- Carrito (solo clientes) -------- */
$cartCount = $esAdmin ? 0 : cart_count();

/* -------- CSS y Título -------- */
// Se construye la URL del CSS usando la BASE_URL correcta.
$cssUrl = BASE_URL . '/assets/style.css';
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? ($pageTitle . ' · Vivero El Prado') : 'Vivero El Prado';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2c7a7b">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>">

    <style>
        /* (Tu CSS interno va aquí, no es necesario cambiarlo) */
        .navbar{background:#2c7a7b;color:#fff;position:sticky;top:0;z-index:1100}
        .navbar .container{display:flex;align-items:center;gap:16px}
        .brand{color:#fff;text-decoration:none;font-weight:700}
        .nav-left,.nav-right{display:flex;align-items:center;gap:14px}
        .nav-right{margin-left:auto}
        .navbar a{color:#fff;text-decoration:none}
        .greet{font-weight:600;opacity:.95}
        .container{max-width:1180px;margin:0 auto;padding:0 28px}
        .cart-link{position:relative;display:inline-flex;align-items:center;gap:6px}
        .cart-badge{position:absolute;top:-7px;right:-10px;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#22c55e;color:#083344;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;border:1px solid #bbf7d0}
        .hamburger{background:transparent;border:0;width:36px;height:36px;display:inline-flex;flex-direction:column;justify-content:center;gap:5px;cursor:pointer}
        .hamburger span{display:block;height:2px;background:#fff;width:22px;border-radius:2px}
        .drawer{position:fixed;left:0;top:0;bottom:0;width:280px;background:#fff;border-right:1px solid #bbf7d0;transform:translateX(-100%);transition:transform .25s ease;z-index:1000;box-shadow:1px 0 8px rgba(0,0,0,.08)}
        .drawer.open{transform:translateX(0)}
        .drawer-header{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#f0fdf4;border-bottom:1px solid #bbf7d0}
        .drawer-close{border:1px solid #bbf7d0;background:#fff;color:#14532d;border-radius:8px;width:28px;height:28px;line-height:26px;text-align:center;cursor:pointer}
        .drawer-nav{display:flex;flex-direction:column;gap:6px;padding:12px}
        .drawer-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#14532d;text-decoration:none;border:1px solid transparent}
        .drawer-nav a:hover{background:#f0fdf4;border-color:#bbf7d0}
        .drawer-overlay{position:fixed;inset:0;background:rgba(2,6,23,.35);opacity:0;pointer-events:none;transition:opacity .2s;z-index:999}
        .drawer-overlay.show{opacity:1;pointer-events:auto}
        .no-scroll{overflow:hidden}
    </style>
</head>
<body>

<header class="navbar">
    <div class="container nav-row">
        <button id="hamburger" type="button" class="hamburger" aria-label="Abrir menú" aria-expanded="false" aria-controls="drawerMenu">
            <span></span><span></span><span></span>
        </button>

        <a class="brand" href="<?= BASE_URL ?>/index.php">Vivero El Prado</a>

        <nav class="nav-right">
            <?php if (!$esAdmin): ?>
                <a class="cart-link" href="<?= BASE_URL ?>/carrito.php" title="Ver carrito">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 .001 3.999A2 2 0 0 0 17 18ZM6.2 5l-.2-1a1 1 0 0 0-1-.8H3a1 1 0 1 0 0 2h1l2.1 9.2A2 2 0 0 0 8 16h8a2 2 0 0 0 1.9-1.4l2-6A1 1 0 0 0 19 7H7.4l-.3-2Z"/></svg>
                    <span style="margin-left:2px">Carrito</span>
                    <?php if ($cartCount > 0): ?><span class="cart-badge"><?= (int)$cartCount ?></span><?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if ($usr): ?>
                <span class="greet">Hola, <?= htmlspecialchars($nombre ?? '') ?></span>
                <a href="<?= BASE_URL ?>/auth/logout.php">Salir</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php">Ingresar</a>
                <a href="<?= BASE_URL ?>/auth/registro.php">Registro</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div id="drawerOverlay" class="drawer-overlay" hidden></div>
<aside id="drawerMenu" class="drawer" aria-hidden="true">
    <div class="drawer-header">
        <strong>Vivero El Prado</strong>
        <button id="drawerClose" type="button" class="drawer-close" aria-label="Cerrar">×</button>
    </div>
    <nav class="drawer-nav">
        <a href="<?= BASE_URL ?>/plantas.php">🌿 <span>Plantas</span></a>
        <a href="<?= BASE_URL ?>/macetas.php">🪴 <span>Macetas</span></a>
        <a href="<?= BASE_URL ?>/abono.php">🧪 <span>Abono</span></a>
        <a href="<?= BASE_URL ?>/tierra.php">🧱 <span>Tierra</span></a>

        <?php if (!$esAdmin): ?>
            <a href="<?= BASE_URL ?>/carrito.php">🛒 <span>Carrito <?= $cartCount>0 ? "($cartCount)" : "" ?></span></a>
        <?php endif; ?>

        <?php if ($usr && !$esAdmin): ?>
            <a href="<?= BASE_URL ?>/mis_pedidos.php">📦 <span>Mis pedidos</span></a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <a href="<?= BASE_URL ?>/admin/pedidos.php">🧾 <span>Pedidos (admin)</span></a>
            <a href="<?= BASE_URL ?>/admin/productos.php">🛠️ <span>Admin productos</span></a>
            <a href="<?= BASE_URL ?>/admin/chatbot_faqs.php">🤖 <span>Chatbot · FAQs</span></a>
        <?php endif; ?>

        <?php if ($usr): ?>
            <a href="<?= BASE_URL ?>/auth/logout.php">🚪 <span>Salir</span></a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">🔐 <span>Ingresar</span></a>
            <a href="<?= BASE_URL ?>/auth/registro.php">✍️ <span>Registro</span></a>
        <?php endif; ?>
    </nav>
</aside>

<script>
(function(){
    function initDrawer(){
        const body   = document.body;
        const btn    = document.getElementById('hamburger');
        const drawer = document.getElementById('drawerMenu');
        const closeB = document.getElementById('drawerClose');
        const ov     = document.getElementById('drawerOverlay');
        if (!btn || !drawer || !ov) return;

        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';

        const open = (e)=>{ e && e.preventDefault();
            drawer.classList.add('open');
            ov.classList.add('show');
            ov.hidden = false;
            body.classList.add('no-scroll');
            btn.setAttribute('aria-expanded','true');
            drawer.setAttribute('aria-hidden','false');
        };
        const close = (e)=>{ e && e.preventDefault();
            drawer.classList.remove('open');
            ov.classList.remove('show');
            ov.hidden = true;
            body.classList.remove('no-scroll');
            btn.setAttribute('aria-expanded','false');
            drawer.setAttribute('aria-hidden','true');
        };

        btn.addEventListener('click', open);
        ov.addEventListener('click', close);
        if (closeB) closeB.addEventListener('click', close);
        document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') close(); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDrawer, { once:true });
    } else {
        initDrawer();
    }
})();
</script>

<main class="page">
    <div class="container"></div>