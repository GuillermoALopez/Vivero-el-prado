<?php
// /public/index.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$bd = obtenerConexion();
$latest = $bd->query("SELECT id, nombre, imagen_url, precio FROM productos ORDER BY creado_en DESC LIMIT 6")
             ->fetchAll(PDO::FETCH_ASSOC);

function url_imagen(?string $img): ?string {
  if (!$img) return null;
  $img = str_replace('\\','/',$img);
  if (preg_match('#^https?://#i', $img)) return $img;
  if ($img[0] !== '/') $img = '/' . $img;
  if (str_starts_with($img, '/public/')) return BASE_URL . $img;
  return BASE_URL . '/public' . $img;
}
?>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy">
      <h1 class="hero-title">
        Plantas y accesorios <span class="hero-accent">sin complicación</span>.
      </h1>
      <p class="hero-sub">
        Explora nuestro catálogo de plantas, macetas, abonos y tierra. Entrega local y asesoría básica.
      </p>

      <div class="hero-ctas">
        <a class="btn primary hero-btn" href="<?php echo BASE_URL; ?>/public/catalogo.php">Ver catálogo</a>
        <a class="btn hero-btn" href="<?php echo BASE_URL; ?>/auth/registro.php">Crear cuenta</a>
      </div>

      <div class="hero-bullets">
        <span>🚚 Entrega local</span>
        <span>🌱 Selección de vivero</span>
        
      </div>
    </div>

    <aside class="hero-device">
      <div class="device-frame">
        <div class="device-grid">
          <?php foreach ($latest as $p): $src = url_imagen($p['imagen_url'] ?? null); ?>
            <a class="device-card" href="<?php echo BASE_URL; ?>/public/producto.php?id=<?php echo (int)$p['id']; ?>">
              <?php if ($src): ?>
                <img src="<?php echo htmlspecialchars($src); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
              <?php else: ?>
                <div class="ph"></div>
              <?php endif; ?>
              <div class="device-name"><?php echo htmlspecialchars($p['nombre']); ?></div>
              <div class="device-price">Q <?php echo number_format((float)$p['precio'], 2); ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="features">
  <div class="features-grid">
    <div class="feat">  
      <div class="feat-ico">🌿</div>
      <h3>Selección de vivero</h3>
      <p>Cuidamos la calidad de cada planta.</p>
    </div>
    <div class="feat">
      <div class="feat-ico">📦</div>
      <h3>Empaque seguro</h3>
      <p>Para que lleguen perfectas a casa.</p>
    </div>
    <div class="feat">
      <div class="feat-ico">🤝</div>
      <h3>Soporte</h3>
      <p>Consejos de riego y trasplante.</p>
    </div>
  </div>
</section>

<section class="cta-strip">
  <div class="cta-inner">
    <div>
      <h3 class="cta-title">¿Listo para empezar?</h3>
      <p class="cta-sub">Explora el catálogo y arma tu jardín hoy.</p>
    </div>
    <div>
      <a class="btn primary" href="<?php echo BASE_URL; ?>/admin/reporte_powerbi.php">Ver gráficos</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
