<?php
/**
 * Vue « Connexion » — Front Office.
 * Variables reçues : $errors (champ => message), $old (valeurs saisies).
 * Aucune validation HTML5 : les contrôles sont faits par validation.js puis par PHP.
 */
ob_start();
$brandTitle = 'Votre réseau, <span>en confiance.</span>';
$brandText  = 'Connectez-vous pour suivre vos factures, recevoir des alertes personnalisées et accéder aux services prioritaires.';
?>
  <main id="main" class="login-page">
    <div class="container">
      <div class="login-shell">
<?php require __DIR__ . '/../../partials/auth-brand.php'; ?>

        <section class="login-form-panel" aria-labelledby="login-title">
          <div class="login-form-wrap">
            <p class="eyebrow">Espace WASLA</p>
            <h1 id="login-title">Connexion</h1>
            <p>Accédez à votre espace personnel et à vos alertes.</p>

<?php if (!empty($errors['form'])): ?>
            <div class="form-alert form-alert--error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

            <form class="login-form" method="post" action="<?= url('connexion') ?>" data-validate>
              <?= csrfField() ?>
              <div class="field">
                <label for="login-email">Adresse e-mail</label>
                <input class="input<?= invalidClass($errors, 'email') ?>" id="login-email" name="email" type="text" inputmode="email"
                       autocomplete="email" placeholder="vous@exemple.tn" value="<?= e($old['email'] ?? '') ?>"
                       data-rules="required|email" data-message-required="Veuillez saisir votre adresse e-mail.">
                <?= fieldError($errors, 'email') ?>
              </div>

              <div class="field">
                <label for="login-password">Mot de passe</label>
                <div class="password-wrap">
                  <input class="input<?= invalidClass($errors, 'password') ?>" id="login-password" name="password" type="password"
                         autocomplete="current-password" placeholder="Votre mot de passe"
                         data-rules="required" data-message-required="Veuillez saisir votre mot de passe.">
                  <button class="icon-btn" type="button" data-toggle-password aria-label="Afficher le mot de passe">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <?= fieldError($errors, 'password') ?>
              </div>

              <button class="btn btn--primary btn--block btn--lg" type="submit" data-loading="Connexion…">Se connecter</button>
            </form>

            <p class="login-alt">Pas encore de compte ? <a href="<?= url('inscription') ?>">Créer un compte</a></p>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Connexion — WASLA';
$description = 'Connectez-vous à votre espace WASLA.';
$currentPage = 'connexion';
require __DIR__ . '/../../layout.php';
