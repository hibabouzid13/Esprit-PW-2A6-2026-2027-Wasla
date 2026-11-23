<?php
/**
 * Vue « Créer un compte » — Front Office.
 * Variables reçues : $errors (champ => message), $old (valeurs saisies), $gouvernorats (ZoneModel[]).
 * Le compte créé est TOUJOURS un compte citoyen (rôle forcé dans AuthController::register()).
 */
ob_start();
$brandTitle = 'Rejoignez <span>WASLA.</span>';
$brandText  = 'Créez votre compte en une minute pour recevoir les alertes de votre gouvernorat, suivre vos factures et accéder aux services prioritaires.';
?>
  <main id="main" class="login-page">
    <div class="container">
      <div class="login-shell">
<?php require __DIR__ . '/../../partials/auth-brand.php'; ?>

        <section class="login-form-panel" aria-labelledby="register-title">
          <div class="login-form-wrap login-form-wrap--wide">
            <p class="eyebrow">Espace citoyen</p>
            <h1 id="register-title">Créer un compte</h1>
            <p>Renseignez vos informations, puis connectez-vous.</p>

<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>

            <form class="login-form" method="post" action="<?= url('inscription') ?>" data-validate>
              <?= csrfField() ?>
              <div class="form-row">
                <div class="field">
                  <label for="reg-prenom">Prénom</label>
                  <input class="input<?= invalidClass($errors, 'prenom') ?>" id="reg-prenom" name="prenom" type="text" autocomplete="given-name"
                         value="<?= e($old['prenom']) ?>" data-rules="required|name">
                  <?= fieldError($errors, 'prenom') ?>
                </div>
                <div class="field">
                  <label for="reg-nom">Nom</label>
                  <input class="input<?= invalidClass($errors, 'nom') ?>" id="reg-nom" name="nom" type="text" autocomplete="family-name"
                         value="<?= e($old['nom']) ?>" data-rules="required|name">
                  <?= fieldError($errors, 'nom') ?>
                </div>
              </div>

              <div class="field">
                <label for="reg-email">Adresse e-mail</label>
                <input class="input<?= invalidClass($errors, 'email') ?>" id="reg-email" name="email" type="text" inputmode="email" autocomplete="email"
                       placeholder="vous@exemple.tn" value="<?= e($old['email']) ?>" data-rules="required|email">
                <?= fieldError($errors, 'email') ?>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="reg-telephone">Téléphone <span class="field__optional">(facultatif)</span></label>
                  <input class="input<?= invalidClass($errors, 'telephone') ?>" id="reg-telephone" name="telephone" type="text" inputmode="tel"
                         autocomplete="tel" placeholder="22 123 456" value="<?= e($old['telephone']) ?>" data-rules="phone">
                  <?= fieldError($errors, 'telephone') ?>
                </div>
                <div class="field">
                  <label for="reg-zone">Gouvernorat</label>
                  <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="reg-zone" name="id_zone"
                          data-rules="required" data-message-required="Veuillez choisir votre gouvernorat.">
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= (string) $zone->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'id_zone') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="reg-password">Mot de passe</label>
                  <input class="input<?= invalidClass($errors, 'password') ?>" id="reg-password" name="password" type="password"
                         autocomplete="new-password" data-rules="required|password">
                  <p class="field__hint">8 caractères min., une lettre et un chiffre.</p>
                  <?= fieldError($errors, 'password') ?>
                </div>
                <div class="field">
                  <label for="reg-password-confirm">Confirmation</label>
                  <input class="input<?= invalidClass($errors, 'password_confirm') ?>" id="reg-password-confirm" name="password_confirm" type="password"
                         autocomplete="new-password" data-rules="required|match:password" data-message-match="Les mots de passe ne correspondent pas.">
                  <?= fieldError($errors, 'password_confirm') ?>
                </div>
              </div>

              <div class="field">
                <label class="checkbox"><input type="checkbox" name="terms" value="1"<?= $old['terms'] !== '' ? ' checked' : '' ?>
                       data-rules="accepted" data-message-accepted="Veuillez accepter les conditions d'utilisation."> J'accepte les conditions d'utilisation et la politique de confidentialité de WASLA.</label>
                <?= fieldError($errors, 'terms') ?>
              </div>

              <button class="btn btn--primary btn--block btn--lg" type="submit" data-loading="Création du compte…">Créer mon compte</button>
            </form>

<?php foreach (['inscription-entreprise' => 'une entreprise', 'inscription-fournisseur' => 'un fournisseur'] as $actionPro => $libellePro): ?>
<?php if (routeExists($actionPro)): ?>
            <p class="login-alt">Vous êtes <?= e($libellePro) ?> ? <a href="<?= url($actionPro) ?>">Créer votre compte</a></p>
<?php endif; ?>
<?php endforeach; ?>
            <p class="login-alt">Déjà inscrit ? <a href="<?= url('connexion') ?>">Se connecter</a></p>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Créer un compte — WASLA';
$description = 'Créez votre compte citoyen WASLA.';
$currentPage = 'inscription';
require __DIR__ . '/../../layout.php';
