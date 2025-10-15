</main>
<footer class="footer">
  <div class="container">
    <small>© <?php echo date('Y'); ?> Vivero El Prado</small>
  </div>
</footer>
<?php require_once __DIR__ . '/chatbot.php'; ?>

<script>
(function(){
  const btn      = document.getElementById('hamburger');
  const drawer   = document.getElementById('drawerMenu');
  const overlay  = document.getElementById('drawerOverlay');
  const btnClose = document.getElementById('drawerClose');

  function openDrawer(){
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    overlay.hidden = false;
    overlay.classList.add('show');
    btn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('no-scroll');
  }
  function closeDrawer(){
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('show');
    setTimeout(() => overlay.hidden = true, 200); // espera la animación
    btn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('no-scroll');
  }

  btn && btn.addEventListener('click', () => {
    const abierto = drawer.classList.contains('open');
    abierto ? closeDrawer() : openDrawer();
  });
  btnClose && btnClose.addEventListener('click', closeDrawer);
  overlay && overlay.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeDrawer(); });
})();
</script>

</body>
</html>

