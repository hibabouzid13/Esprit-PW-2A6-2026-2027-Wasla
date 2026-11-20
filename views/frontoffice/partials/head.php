<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <meta name="theme-color" content="#003B5C">
  <script src="<?= asset('js/prefs.js') ?>"></script>
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preload" href="<?= asset('fonts/inter-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
<?= $extraHead ?>
<?php foreach (array_merge(['tokens', 'base', 'components'], $pageCss, ['motion']) as $css): ?>
  <link rel="stylesheet" href="<?= asset('css/' . $css . '.css') ?>">
<?php endforeach; ?>
</head>
<?php $headUser = AuthController::currentUser(); ?>
<body data-page="<?= e($currentPage) ?>"<?php if ($headUser): ?> data-user-name="<?= e($headUser->getPrenom()) ?>"<?= AuthController::isBackOffice($headUser) ? ' data-user-admin' : '' ?><?php endif; ?>>
