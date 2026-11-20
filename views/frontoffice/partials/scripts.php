  <script src="<?= asset('js/i18n.js') ?>"></script>
<?php foreach ($pageScripts as $src): ?>
  <script src="<?= asset($src) ?>"></script>
<?php endforeach; ?>
  <script src="<?= asset('js/validation.js') ?>"></script>
  <script src="<?= asset('js/motion.js') ?>"></script>
</body>
</html>
