<?php
/**
 * Vue « Mon profil fournisseur » — Front Office (rôle fournisseur) — Module 4 (Bechir).
 * Trois formulaires séparés : profil, mot de passe, suppression du compte.
 * Variables reçues : $user, $errors, $old, $pwdErrors, $delErrors, $gouvernorats.
 */
$rules = FournisseurController::rules('profil');
$pwdRules = FournisseurController::passwordRules();
$delRules = FournisseurController::deleteRules();
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Mon profil</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Espace fournisseur</p>
            <h1>Mon profil professionnel</h1>
            <p>
<?php if ($user->isCompteVerifie()): ?>
              <span class="svc-tag svc-tag--green"><span data-icon="shield-check"></span>Compte vérifié</span> Vous apparaissez dans l'annuaire et pouvez répondre aux demandes.
<?php else: ?>
              <span class="svc-tag">En attente de validation</span> Vous n'apparaissez pas encore dans l'annuaire et ne pouvez pas répondre aux demandes.
<?php endif; ?>
            </p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('fournisseurs', [], 'demandes-recues') ?>"><span data-icon="wrench"></span> Demandes reçues</a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container svc-stack" style="max-width:820px;">
        <form class="card card__body form-grid" method="post" action="<?= url('profil-fournisseur') ?>" data-validate>
          <?= csrfField() ?>
          <h2 class="card__title">Mes informations</h2>
<?php if (isset($errors['form'])): ?>
          <p class="flash flash--error"><?= e($errors['form']) ?></p>
<?php endif; ?>
<?php require __DIR__ . '/_champs.php'; ?>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--primary btn--sm" type="submit" data-loading="Enregistrement…"><span data-icon="check"></span>Enregistrer</button>
          </div>
        </form>

        <form class="card card__body form-grid" method="post" action="<?= url('mot-de-passe-fournisseur') ?>" data-validate id="mot-de-passe">
          <?= csrfField() ?>
          <h2 class="card__title">Changer mon mot de passe</h2>
<?php if (isset($pwdErrors['form'])): ?>
          <p class="flash flash--error"><?= e($pwdErrors['form']) ?></p>
<?php endif; ?>
          <div class="field">
            <label for="p-current">Mot de passe actuel *</label>
            <input class="input<?= invalidClass($pwdErrors, 'current_password') ?>" id="p-current" name="current_password" type="password" autocomplete="current-password" data-rules="<?= e($pwdRules['current_password']) ?>">
            <?= fieldError($pwdErrors, 'current_password') ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="p-new">Nouveau mot de passe *</label>
              <input class="input<?= invalidClass($pwdErrors, 'password') ?>" id="p-new" name="password" type="password" autocomplete="new-password" data-rules="<?= e($pwdRules['password']) ?>">
              <p class="field__hint">8 caractères min., une lettre et un chiffre.</p>
              <?= fieldError($pwdErrors, 'password') ?>
            </div>
            <div class="field">
              <label for="p-new2">Confirmation *</label>
              <input class="input<?= invalidClass($pwdErrors, 'password_confirm') ?>" id="p-new2" name="password_confirm" type="password" autocomplete="new-password" data-rules="<?= e($pwdRules['password_confirm']) ?>">
              <?= fieldError($pwdErrors, 'password_confirm') ?>
            </div>
          </div>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--secondary btn--sm" type="submit"><span data-icon="lock"></span>Changer le mot de passe</button>
          </div>
        </form>

        <form class="card card__body form-grid" method="post" action="<?= url('supprimer-compte-fournisseur') ?>" data-validate data-confirm="Supprimer votre compte fournisseur ? S'il a des demandes, il sera désactivé." id="supprimer">
          <?= csrfField() ?>
          <h2 class="card__title">Supprimer mon compte</h2>
          <p class="asst-muted">Sans aucune donnée, le compte est supprimé. S'il a reçu ou envoyé des demandes, il est désactivé : vous ne pourrez plus vous connecter et les données sont conservées.</p>
<?php if (isset($delErrors['form'])): ?>
          <p class="flash flash--error"><?= e($delErrors['form']) ?></p>
<?php endif; ?>
          <div class="field">
            <label for="d-current">Mot de passe *</label>
            <input class="input<?= invalidClass($delErrors, 'current_password') ?>" id="d-current" name="current_password" type="password" autocomplete="current-password" data-rules="<?= e($delRules['current_password']) ?>">
            <?= fieldError($delErrors, 'current_password') ?>
          </div>
          <label class="checkbox"><input type="checkbox" name="confirmation" value="1" data-rules="<?= e($delRules['confirmation']) ?>"> Je comprends que cette action est définitive.</label>
          <?= fieldError($delErrors, 'confirmation') ?>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="submit"><span data-icon="trash"></span>Supprimer mon compte</button>
          </div>
        </form>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mon profil fournisseur — WASLA';
$currentPage = 'mon-compte';
$pageCss     = ['pages', 'services', 'assistance'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js'];
require __DIR__ . '/../../layout.php';
