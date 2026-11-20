<?php
/**
 * Layout du Back Office : assemble les blocs communs autour du contenu de la page.
 *
 * Variables définies par la vue :
 *   $title         titre de l'onglet
 *   $currentPage   clé de la page courante (élément actif de la sidebar)
 *   $pageScripts   scripts partagés à charger depuis frontoffice/assets (ex. js/charts.js)
 *   $content       HTML de la page (déjà rendu)
 *
 * Comme au Front Office : la vue fait ob_start(), met son HTML dans $content,
 * définit $title / $currentPage / $pageScripts puis inclut ce layout.
 */
$title       = $title ?? 'WASLA Admin';
$currentPage = $currentPage ?? 'dashboard';
$pageScripts = $pageScripts ?? [];

require __DIR__ . '/partials/head.php';
?>
  <div class="admin">
<?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="admin-main">
<?php require __DIR__ . '/partials/header.php'; ?>

      <main class="admin-content" id="main">
<?php foreach (AuthController::takeFlashes() as $flash): ?>
        <p class="admin-flash admin-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endforeach; ?>
<?= $content ?>
      </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
    </div>
  </div>
<?php require __DIR__ . '/partials/scripts.php'; ?>
