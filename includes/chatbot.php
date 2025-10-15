<?php
// includes/chatbot.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$usr    = $_SESSION['usuario'] ?? null;
$nombre = $usr ? trim(explode(' ', ($usr['nombre'] ?? $usr['correo'] ?? ''))[0]) : '👋';

$rolId   = $usr['rol_id'] ?? null;
$rolNom  = strtolower(trim($usr['rol'] ?? ''));
$esAdmin = ($rolId == 1) || ($rolNom === 'administrador') || ($rolNom === 'admin');

/* Si no quieres que el admin vea el chatbot, descomenta:
if ($esAdmin) return;
*/

$bd = obtenerConexion();

/* URLs dinámicas */
$pedidosUrl = $usr
  ? BASE_URL . '/public/mis_pedidos.php'
  : BASE_URL . '/auth/login.php?return_to=' . urlencode(BASE_URL . '/public/mis_pedidos.php');

/* URL de Google Maps para tu negocio */
$mapsUrl = 'https://www.google.com/maps?q=San+Martin+Jilotepeque,+Chimaltenango,+Guatemala';

/* FAQs activas */
$st = $bd->prepare("SELECT id, pattern, answer_html, sort_order FROM chatbot_faqs WHERE is_active=1 ORDER BY sort_order, id");
$st->execute();
$faqs = $st->fetchAll(PDO::FETCH_ASSOC);

/* Reemplazos placeholders */
foreach ($faqs as &$f) {
  $f['answer_html'] = str_replace(
    ['{BASE_URL}', '{PEDIDOS_URL}', '{MAPS_URL}'],
    [BASE_URL,      $pedidosUrl,     $mapsUrl],
    $f['answer_html']
  );
}
unset($f);
?>
<style>
  #vp-chatbot-btn{position:fixed; right:18px; bottom:18px; width:54px; height:54px; border-radius:50%; border:0; cursor:pointer; z-index:1200; background:#16a34a; color:#fff; box-shadow:0 8px 22px rgba(0,0,0,.25); display:flex; align-items:center; justify-content:center; font-size:22px;}
  #vp-chatbot-btn:hover{ filter:brightness(1.05); }
  #vp-chatbot{position:fixed; right:18px; bottom:84px; width:320px; max-height:70vh; background:#fff; border:1px solid #bbf7d0; border-radius:14px; box-shadow:0 12px 32px rgba(0,0,0,.25); display:none; flex-direction:column; overflow:hidden; z-index:1200; font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;}
  #vp-ch-hd{background:#2c7a7b; color:#fff; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;}
  #vp-ch-hd strong{font-size:14px}
  #vp-ch-hd button{background:transparent;border:0;color:#fff;font-size:18px;cursor:pointer}
  #vp-ch-log{padding:10px; overflow:auto; display:flex; flex-direction:column; gap:8px;}
  .vp-msg{max-width:86%; padding:8px 10px; border-radius:10px; font-size:13px; line-height:1.25}
  .vp-bot{background:#f0fdf4; border:1px solid #bbf7d0; color:#064e3b; align-self:flex-start;}
  .vp-user{background:#e2e8f0; border:1px solid #cbd5e1; color:#0f172a; align-self:flex-end;}
  #vp-ch-ft{display:flex; gap:6px; padding:10px; border-top:1px solid #e5e7eb; background:#fafafa}
  #vp-ch-tx{flex:1; border:1px solid #cbd5e1; border-radius:10px; padding:8px 10px; font-size:13px}
  #vp-ch-send{background:#16a34a; color:#fff; border:0; border-radius:10px; padding:8px 12px; cursor:pointer}
  #vp-ch-send:hover{filter:brightness(1.05)}
  .vp-sug{display:inline-block; background:#eef2ff; border:1px solid #c7d2fe; color:#1e293b; padding:5px 8px; border-radius:999px; font-size:12px; cursor:pointer; margin:3px 4px 0 0}
</style>

<button id="vp-chatbot-btn" aria-label="Abrir chat">💬</button>

<div id="vp-chatbot" role="dialog" aria-modal="true" aria-label="Asistente de Vivero El Prado">
  <div id="vp-ch-hd">
    <strong>Asistente · Vivero El Prado</strong>
    <button id="vp-ch-close" title="Cerrar">×</button>
  </div>
  <div id="vp-ch-log">
    <div class="vp-msg vp-bot">Hola <?= htmlspecialchars($nombre) ?>, ¿en qué puedo ayudarte?
      <?php if (!empty($faqs)): ?>
      <div style="margin-top:6px">
        <?php
        $sugs = array_slice($faqs, 0, 5);
        foreach ($sugs as $s) {
          $etq = preg_replace('/[^A-Za-zÁÉÍÓÚáéíóúñÑ]+/u', ' ', $s['pattern']);
          $etq = trim(explode(' ', $etq)[0] ?? 'ayuda');
          echo '<span class="vp-sug">'.htmlspecialchars(mb_strtolower($etq)).'</span>';
        }
        ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div id="vp-ch-ft">
    <input id="vp-ch-tx" type="text" placeholder="Escribe tu pregunta..." autocomplete="off">
    <button id="vp-ch-send">Enviar</button>
  </div>
</div>

<script>
(function(){
  const $btn   = document.getElementById('vp-chatbot-btn');
  const $chat  = document.getElementById('vp-chatbot');
  const $close = document.getElementById('vp-ch-close');
  const $log   = document.getElementById('vp-ch-log');
  const $tx    = document.getElementById('vp-ch-tx');
  const $send  = document.getElementById('vp-ch-send');

  function open(){ $chat.style.display='flex'; $tx.focus(); }
  function close(){ $chat.style.display='none'; }
  $btn.onclick=open; $close.onclick=close;

  // FAQs desde PHP → JS
  const FAQS = <?=
    json_encode(array_map(function($f){
      return [
        're'  => $f['pattern'],
        'ans' => $f['answer_html'],
      ];
    }, $faqs), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  ?>;

  /* ---------- utilidades: logging y DOM ---------- */
  function logMsg(role, message){
    try {
      fetch('<?= BASE_URL ?>/public/api/chatbot_log.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({role, message})
      });
    } catch(e){}
  }

  function addBot(html){
    const div = document.createElement('div');
    div.className='vp-msg vp-bot';
    div.innerHTML = html;
    $log.appendChild(div);
    $log.scrollTop = $log.scrollHeight;
    logMsg('bot', html.replace(/<[^>]+>/g,' ').trim());
  }

  function addUser(text){
    const div = document.createElement('div');
    div.className='vp-msg vp-user';
    div.textContent = text;
    $log.appendChild(div);
    $log.scrollTop = $log.scrollHeight;
    logMsg('user', text);
  }

  /* ---------- normalización para coincidencias flexibles ---------- */
  function normalizeText(t){
    return String(t || '')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')  // sin acentos
      .replace(/[^\p{L}\p{N}\s]/gu,' ')                 // quita signos
      .replace(/\s+/g,' ')                              // espacios únicos
      .replace(/(.)\1{2,}/g,'$1$1')                     // colapsa alargues: holaaaa -> holaa
      .trim();
  }

  function normalizePattern(p){
    return String(p || '')
      .replace(/\\\|/g,'|')     // "\|" -> "|"
      .replace(/\s*\|\s*/g,'|') // sin espacios alrededor del |
      .trim();
  }

  // tokens desde patrón: admite tanto "a|b|c" como "a,b,c"
  function tokensFromPattern(p){
    let s = normalizePattern(p);
    if (s.startsWith('(') && s.endsWith(')')) s = s.slice(1,-1);
    return s.split(/[|,]/).map(x => x.trim()).filter(Boolean);
  }

  // coincide por palabra/frase, con tolerancia simple
  function wordIncludes(haystackNorm, tokenRaw){
    const token = normalizeText(tokenRaw);
    if (!token) return false;

    // si el token tiene espacios, usamos includes directo
    if (/\s/.test(token)) return haystackNorm.includes(token);

    // palabra completa
    const re = new RegExp('(^|\\s)'+token+'(\\s|$)');
    if (re.test(haystackNorm)) return true;

    // tolerancia: si el token ≥3 letras, permitimos inclusión (para "holiss" ≈ "holi/hola")
    return token.length >= 3 && haystackNorm.includes(token);
  }

  /* ---------- motor de respuesta ---------- */
  function answer(rawQ){
    if (!Array.isArray(FAQS) || FAQS.length === 0) {
      return 'Por ahora no tengo respuestas configuradas. Intenta más tarde 🙏.';
    }

    // 1) regex directo
    for (const it of FAQS) {
      try {
        const re = new RegExp(it.re, 'i');
        if (re.test(rawQ)) return it.ans;
      } catch(e) {}
    }

    // 2) palabras clave (normalizadas)
    const nq = normalizeText(rawQ);
    for (const it of FAQS) {
      const toks = tokensFromPattern(it.re);
      if (toks.length && toks.some(tok => wordIncludes(nq, tok))) {
        return it.ans;
      }
    }

    return 'No estoy segura de eso 🤔. Pregúntame sobre <b>catálogo</b>, <b>entrega</b>, <b>horario</b>, <b>ubicación</b> o <b>pedido</b>.';
  }

  function send(){
    const q = $tx.value.trim();
    if (!q) return;
    addUser(q);
    $tx.value='';
    setTimeout(()=> addBot( answer(q) ), 200);
  }

  $send.onclick = send;
  $tx.addEventListener('keydown', e => { if (e.key === 'Enter') send(); });

  // Sugerencias clicables
  $log.addEventListener('click', e=>{
    if (e.target.classList.contains('vp-sug')) {
      $tx.value = e.target.textContent.trim();
      send();
    }
  });
})();
</script>
