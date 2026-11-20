<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>
  <meta name="theme-color" content="#003B5C">
  <meta name="robots" content="noindex">
  <script src="<?= asset('js/prefs.js') ?>"></script>
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preload" href="<?= asset('fonts/inter-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <!-- Charte commune (couleurs, polices, reset) reprise du Front Office -->
  <link rel="stylesheet" href="<?= asset('css/tokens.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/motion.css') ?>">
  <!-- Styles propres au Back Office -->
  <link rel="stylesheet" href="<?= asset('css/admin.css', 'backoffice') ?>">
</head>
<body class="admin-body" data-page="<?= e($currentPage) ?>">
  <!-- ======== Preloader ======== -->
  <div id="preloader" class="preloader is-visible" role="status" aria-live="polite">
    <div class="spinner__ring" aria-hidden="true"></div>
    <span class="sr-only">Chargement…</span>
  </div>
  <noscript><style>#preloader { display: none; }</style></noscript>
  <!-- ======== Preloader ======== -->
