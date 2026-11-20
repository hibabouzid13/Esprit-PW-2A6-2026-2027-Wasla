<?php
/**
 * Vue d'erreur (403 accès refusé, 404 page introuvable).
 * Variables reçues : $errorCode, $errorTitle, $errorMessage.
 */
ob_start();
$u = AuthController::currentUser();
$homeAction = $u !== null ? AuthController::homeFor($u) : 'accueil';
$homeLabel  = $u !== null ? 'Retour à mon espace' : "Retour à l'accueil";
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <p class="eyebrow">Erreur <?= (int) $errorCode ?></p>
        <h1><?= e($errorTitle) ?></h1>
        <p><?= e($errorMessage) ?></p>
        <div class="page-hero__actions">
          <a class="btn btn--primary" href="<?= url($homeAction) ?>"><span data-icon="home"></span> <?= e($homeLabel) ?></a>
<?php if (!AuthController::isAuthenticated()): ?>
          <a class="btn btn--outline" href="<?= url('connexion') ?>">Se connecter</a>
<?php endif; ?>
        </div>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = $errorTitle . ' — WASLA';
$currentPage = '';
require __DIR__ . '/../../layout.php';
