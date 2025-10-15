<?php
// admin/chatbot_faqs.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
iniciarSesion();

$usr = $_SESSION['usuario'] ?? null;
$rolId  = $usr['rol_id'] ?? null;
$rolNom = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($rolId == 1) || ($rolNom === 'administrador') || ($rolNom === 'admin');
if (!$esAdmin) { header('Location: ' . BASE_URL . '/public/index.php'); exit; }

$bd = obtenerConexion();

/* CSRF mínimo */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

/* URLs dinámicas para vista previa */
$pedidosUrl = $usr
  ? BASE_URL . '/public/mis_pedidos.php'
  : BASE_URL . '/auth/login.php?return_to=' . urlencode(BASE_URL . '/public/mis_pedidos.php');

/* Acciones */
$msg = '';
$action = $_POST['action'] ?? $_GET['action'] ?? null;

function sanitize_html($html) {
  // Permite solo etiquetas básicas (a, b, strong, em, br, small)
  return preg_replace('#<(?!\/?(a|b|strong|em|br|small)(\s+[^>]*)?>)#i', '&lt;$0', $html);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
  if ($action === 'save') {
    $id       = (int)($_POST['id'] ?? 0);
    $mode     = $_POST['mode'] ?? 'keywords'; // keywords | regex
    $keywords = trim($_POST['keywords'] ?? '');
    $pattern  = trim($_POST['pattern'] ?? '');
    $answer   = sanitize_html(trim($_POST['answer_html'] ?? ''));
    $order    = (int)($_POST['sort_order'] ?? 100);
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if ($mode === 'keywords') {
      // Convierte "pago, tarjeta, efectivo" -> "(pago|tarjeta|efectivo)"
      $parts = array_filter(array_map(function($s){
        $s = trim($s);
        // escapamos caracteres regex especiales en modo palabras
        return $s !== '' ? preg_quote($s, '/') : '';
      }, explode(',', $keywords)));
      $pattern = $parts ? '(' . implode('|', $parts) . ')' : '';
    }
    if ($pattern === '' || $answer === '') {
      $msg = 'Faltan datos: completa palabras/patrón y respuesta.';
    } else {
      if ($id > 0) {
        $st = $bd->prepare("UPDATE chatbot_faqs SET pattern=?, answer_html=?, sort_order=?, is_active=? WHERE id=?");
        $st->execute([$pattern, $answer, $order, $active, $id]);
        $msg = 'Entrada actualizada.';
      } else {
        $st = $bd->prepare("INSERT INTO chatbot_faqs (pattern, answer_html, sort_order, is_active) VALUES (?,?,?,?)");
        $st->execute([$pattern, $answer, $order, $active]);
        $msg = 'Entrada creada.';
      }
    }
  } elseif ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $bd->prepare("UPDATE chatbot_faqs SET is_active = NOT is_active WHERE id=?");
    $st->execute([$id]);
    $msg = 'Estado cambiado.';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $bd->prepare("DELETE FROM chatbot_faqs WHERE id=?");
    $st->execute([$id]);
    $msg = 'Entrada eliminada.';
  }
}

/* Edición */
$editId = (int)($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
  $st = $bd->prepare("SELECT * FROM chatbot_faqs WHERE id=?");
  $st->execute([$editId]);
  $edit = $st->fetch(PDO::FETCH_ASSOC);
}

/* Listado */
$rows = $bd->query("SELECT * FROM chatbot_faqs ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);

/* Page Title (para header.php) */
$pageTitle = 'Chatbot · FAQs';

require_once __DIR__ . '/../includes/header.php';
?>
<style>
  .admin-wrap{display:grid;grid-template-columns:1.1fr 1.3fr;gap:22px}
  .card{border:1px solid #e2e8f0;border-radius:14px;background:#fff}
  .card-hd{padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#f8fafc;font-weight:700}
  .card-bd{padding:16px}
  .row{display:grid;gap:8px;margin-bottom:12px}
  .label{font-size:13px;color:#334155}
  .input, .textarea, .select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px}
  .textarea{min-height:120px}
  .muted{font-size:12px;color:#64748b}
  .btn{padding:9px 14px;border-radius:10px;border:0;cursor:pointer}
  .btn-primary{background:#16a34a;color:#fff}
  .btn-ghost{background:#e2e8f0}
  .btn-danger{background:#ef4444;color:#fff}
  .btn-info{background:#0ea5e9;color:#fff}
  .inline{display:flex;gap:10px;align-items:center}
  table.faqs{width:100%;border-collapse:collapse}
  table.faqs th, table.faqs td{padding:8px 12px;border-top:1px solid #e2e8f0;text-align:left;vertical-align:top}
  table.faqs th{background:#f8fafc}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #cbd5e1;color:#334155;background:#f1f5f9}
  .preview-box{border:1px dashed #cbd5e1;border-radius:10px;padding:10px;background:#f8fafc}
</style>

<h1>Chatbot · FAQs</h1>
<?php if ($msg): ?><p class="flash-success"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

<div class="admin-wrap">
  <!-- Formulario asistido -->
  <section class="card">
    <div class="card-hd"><?= $edit ? 'Editar entrada' : 'Nueva entrada' ?></div>
    <div class="card-bd">
      <form method="post" id="faqForm" class="row">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <div class="row">
          <label class="label">Modo</label>
          <select class="select" id="mode" name="mode">
            <option value="keywords" <?= !$edit || !preg_match('/[|()]/', $edit['pattern'] ?? '') ? 'selected' : '' ?>>Palabras clave (simple)</option>
            <option value="regex"     <?= ($edit && preg_match('/[|()]/', $edit['pattern'])) ? 'selected' : '' ?>>Expresión regular (avanzado)</option>
          </select>
          <div class="muted">Usa <b>Palabras clave</b> para escribir términos separados por comas. El sistema genera el patrón.</div>
        </div>

        <div class="row" id="kwRow">
          <label class="label">Palabras clave (separa con coma)</label>
          <input class="input" id="keywords" name="keywords" placeholder="ej.: catalogo, productos, tienda">
          <div class="muted">Ej.: <code>pago, tarjeta, efectivo</code></div>
        </div>

        <div class="row" id="rxRow" style="display:none">
          <label class="label">Patrón (expresión regular)</label>
          <input class="input" id="pattern" name="pattern" placeholder="ej.: (cat[aá]logo|productos)">
          <div class="muted">Insensible a mayúsculas/minúsculas (se aplica la bandera <code>i</code>).</div>
        </div>

        <div class="row">
          <label class="label">Respuesta (HTML permitido)</label>
          <textarea class="textarea" id="answer_html" name="answer_html" placeholder="Texto de respuesta..."><?= htmlspecialchars($edit['answer_html'] ?? '') ?></textarea>
          <div class="muted">Puedes usar <code>{BASE_URL}</code> y <code>{PEDIDOS_URL}</code>. Etiquetas permitidas: <code>a, b, strong, em, small, br</code>.</div>
        </div>

        <div class="inline">
          <label class="label">Orden</label>
          <input class="input" style="max-width:140px" type="number" name="sort_order" id="sort_order" value="<?= (int)($edit['sort_order'] ?? 100) ?>">
          <label class="inline" style="gap:6px">
            <input type="checkbox" name="is_active" id="is_active" <?= !isset($edit['is_active']) || (int)($edit['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
            Activa
          </label>
          <button class="btn btn-info" type="button" id="btnTry">Probar patrón</button>
          <button class="btn btn-ghost" type="button" id="btnClear">Limpiar</button>
        </div>

        <div class="row">
          <label class="label">Pregunta de prueba</label>
          <input class="input" id="tryText" placeholder="Escribe una pregunta para probar...">
          <div class="preview-box" id="tryResult" style="margin-top:8px">
            <div><b>Patrón generado:</b> <span id="patPreview" class="pill">—</span></div>
            <div style="margin-top:8px"><b>¿Coincide?</b> <span id="matchPreview" class="pill">—</span></div>
            <div style="margin-top:8px"><b>Respuesta (preview):</b><br><span id="ansPreview" style="display:inline-block;margin-top:4px;color:#1f2937"></span></div>
          </div>
        </div>

        <div class="inline" style="margin-top:6px">
          <button class="btn btn-primary">Guardar</button>
          <?php if ($edit): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/admin/chatbot_faqs.php">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </section>

  <!-- Lista -->
  <section class="card">
    <div class="card-hd">Entradas</div>
    <div class="card-bd" style="padding:0">
      <table class="faqs">
        <thead>
          <tr>
            <th>Patrón</th>
            <th>Respuesta (preview)</th>
            <th>Orden</th>
            <th>Activa</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): 
            $preview = str_replace(
              ['{BASE_URL}','{PEDIDOS_URL}'],
              [BASE_URL, $pedidosUrl],
              $r['answer_html']
            );
          ?>
          <tr>
            <td style="font-family:monospace"><?= htmlspecialchars($r['pattern']) ?></td>
            <td><div style="max-height:48px;overflow:hidden;opacity:.9"><?= $preview ?></div></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td><?= (int)$r['is_active'] ? 'Sí' : 'No' ?></td>
            <td style="display:flex;gap:8px">
              <a class="btn btn-info" href="<?= BASE_URL ?>/admin/chatbot_faqs.php?id=<?= (int)$r['id'] ?>">Editar</a>
              <form method="post" onsubmit="return confirm('¿Cambiar estado?')">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn">Activar/Desactivar</button>
              </form>
              <form method="post" onsubmit="return confirm('¿Eliminar esta entrada?')">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
          <tr><td colspan="5" style="padding:12px">Sin registros.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script>
(function(){
  const modeSel = document.getElementById('mode');
  const kwRow   = document.getElementById('kwRow');
  const rxRow   = document.getElementById('rxRow');
  const kwIn    = document.getElementById('keywords');
  const rxIn    = document.getElementById('pattern');
  const ansIn   = document.getElementById('answer_html');
  const tryIn   = document.getElementById('tryText');
  const patPrev = document.getElementById('patPreview');
  const matchPrev = document.getElementById('matchPreview');
  const ansPrev = document.getElementById('ansPreview');
  const btnTry  = document.getElementById('btnTry');
  const btnClear= document.getElementById('btnClear');

  // precarga valores de edición al modo correcto
  function detectInitialMode(){
    const pat = rxIn.value.trim();
    if (pat) {
      // si patrón parece generado (tiene | o paréntesis) marcamos regex
      if (/\(|\|/.test(pat)) modeSel.value = 'regex';
    }
    toggleMode();
    // si llegamos con datos a editar y el modo quedó 'keywords', intenta convertir patrón a lista
    if (modeSel.value === 'keywords' && pat) {
      const m = pat.match(/^\((.+)\)$/);
      if (m) kwIn.value = m[1].replace(/\|/g, ', ');
    }
    refreshPreview();
  }

  function toggleMode(){
    const m = modeSel.value;
    kwRow.style.display = (m === 'keywords') ? '' : 'none';
    rxRow.style.display = (m === 'regex') ? '' : 'none';
  }

  function buildPattern(){
    if (modeSel.value === 'regex') {
      return rxIn.value.trim();
    }
    const parts = kwIn.value.split(',').map(s => s.trim()).filter(Boolean);
    // escapamos caracteres regex
    const esc = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return parts.length ? '(' + parts.map(esc).join('|') + ')' : '';
  }

  function tryMatch(){
    const pat = buildPattern();
    patPrev.textContent = pat || '—';
    let ok = false;
    try {
      if (pat) ok = new RegExp(pat, 'i').test(tryIn.value);
    } catch(e) { ok = false; }
    matchPrev.textContent = ok ? 'Sí' : 'No';
    matchPrev.style.background = ok ? '#dcfce7' : '#fee2e2';
    // preview de respuesta (sustituyendo placeholders)
    const ans = ansIn.value.replaceAll('{BASE_URL}', '<?= BASE_URL ?>')
                           .replaceAll('{PEDIDOS_URL}', '<?= $pedidosUrl ?>');
    ansPrev.innerHTML = ans || '<span class="muted">—</span>';
  }

  function refreshPreview(){
    tryMatch();
  }

  modeSel.addEventListener('change', ()=>{
    toggleMode();
    refreshPreview();
  });
  [kwIn, rxIn, ansIn, tryIn].forEach(el => el.addEventListener('input', refreshPreview));
  btnTry.addEventListener('click', refreshPreview);
  btnClear.addEventListener('click', ()=>{
    kwIn.value = ''; rxIn.value=''; ansIn.value=''; tryIn.value=''; refreshPreview();
  });

  // Carga valores iniciales (si vienes a editar)
  detectInitialMode();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
