<?php
/**
 * Vue « Inscription fournisseur » — Front Office (visiteur) — Module 4 (Bechir).
 * Variables reçues : $errors, $old, $gouvernorats.
 * Le compte créé a TOUJOURS role = 'fournisseur', statut actif et compte_verifie = 0 (écrits dans FournisseurController).
 */
$rules = FournisseurController::rules('inscription');
ob_start();
$brandTitle = 'Professionnels, <span>rejoignez WASLA.</span>';
$brandText  = "Électriciens, plombiers, installateurs solaires : recevez les demandes d'intervention des citoyens de votre gouvernorat pendant les coupures.";
?>
  <main id="main" class="login-page">
    <div class="container">
      <div class="login-shell">
<?php require __DIR__ . '/../../partials/auth-brand.php'; ?>

        <section class="login-form-panel" aria-labelledby="register-title">
          <div class="login-form-wrap login-form-wrap--wide">
            <p class="eyebrow">Espace fournisseur</p>
            <h1 id="register-title">Créer un compte professionnel</h1>
            <p>Votre compte est actif dès l'inscription. Il apparaît dans l'annuaire après vérification par l'administrateur.</p>

<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>

            <form class="login-form" method="post" action="<?= url('inscription-fournisseur') ?>" data-validate>
              <?= csrfField() ?>
<?php require __DIR__ . '/_champs.php'; ?>
              <div class="form-row">
                <div class="field">
                  <label for="f-password">Mot de passe *</label>
                  <input class="input<?= invalidClass($errors, 'password') ?>" id="f-password" name="password" type="password" autocomplete="new-password" data-rules="<?= e($rules['password']) ?>">
                  <p class="field__hint">8 caractères min., une lettre et un chiffre.</p>
                  <?= fieldError($errors, 'password') ?>
                </div>
                <div class="field">
                  <label for="f-password2">Confirmation *</label>
                  <input class="input<?= invalidClass($errors, 'password_confirm') ?>" id="f-password2" name="password_confirm" type="password" autocomplete="new-password" data-rules="<?= e($rules['password_confirm']) ?>">
                  <?= fieldError($errors, 'password_confirm') ?>
                </div>
              </div>
              <div class="field">
                <label class="checkbox"><input type="checkbox" name="terms" value="1"<?= $old['terms'] !== '' ? ' checked' : '' ?> data-rules="<?= e($rules['terms']) ?>"> J'accepte les conditions d'utilisation et que mes coordonnées professionnelles soient affichées dans l'annuaire après vérification.</label>
                <?= fieldError($errors, 'terms') ?>
              </div>
              <button class="btn btn--primary btn--block btn--lg" type="submit" data-loading="Création du compte…">Créer mon compte fournisseur</button>
            </form>

            <p class="login-alt">Déjà inscrit ? <a href="<?= url('connexion') ?>">Se connecter</a> · Particulier ? <a href="<?= url('inscription') ?>">Compte citoyen</a></p>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Inscription fournisseur — WASLA';
$description = 'Créez votre compte professionnel WASLA : électricien, plombier, installateur solaire.';
$currentPage = 'inscription';
require __DIR__ . '/../../layout.php';
