<?php foreach ($pageScripts as $src): ?>
  <script src="<?= asset($src) ?>"></script>
<?php endforeach; ?>
  <script src="<?= asset('js/icons.js') ?>"></script>
  <script src="<?= asset('js/validation.js') ?>"></script>
  <script src="<?= asset('js/admin.js', 'backoffice') ?>"></script>
</body>
</html>
