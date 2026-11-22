<?php
/**
 * Vue : « Mon profil » du citoyen — Front Office — Module 2 (Hiba).
 * Deux formulaires : informations (action mon-profil) et mot de passe (action mon-mot-de-passe).
 * Variables reçues : $old (valeurs), $errors (profil), $errorsMdp (mot de passe), $gouvernorats (ZoneModel[]).
 * Validation : validation.js (règles fournies par CitoyenController) puis CitoyenController en PHP.
 */
ob_start();
// Zone d'erreur reliée à son champ (mêmes classes que fieldError(), avec un id pour aria-describedby)
$msg = fn (array $erreurs, string $k): string => '<p class="field__error' . (isset($erreurs[$k]) ? ' is-visible' : '') . '" id="profil-' . $k . '-erreur" data-error-for="' . $k . '" aria-live="polite">' . e($erreurs[$k] ?? '') . '</p>';
// Attributs communs d'un champ : règles, messages, accessibilité
$attr = fn (array $erreurs, string $k, string $aide = ''): string => CitoyenController::attributs($k)
    . ' aria-describedby="' . ($aide !== '' ? $aide . ' ' : '') . 'profil-' . $k . '-erreur"'
    . (in_array($k, ['telephone', 'adresse'], true) ? '' : ' aria-required="true"')
    . (isset($erreurs[$k]) ? ' aria-invalid="true"' : '');
?>
  <main id="main" class="bill-ui">
    <div class="container">
      <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="<?= url('mon-compte') ?>">Mon espace</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <span aria-current="page">Mon profil</span>
      </nav>

      <header class="bill-head">
        <div>
          <p class="bill-eyebrow">Compte citoyen</p>
          <h1>Mon profil</h1>
          <p>Gardez vos informations à jour et choisissez votre mot de passe.</p>
        </div>
      </header>

      <form class="bill-wizard bill-wizard--full bill-glass" method="post" action="<?= url('mon-profil') ?>" data-validate data-citoyen-form>
        <?= csrfField() ?>
<?php if ($errors !== []): ?>
        <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
        <fieldset class="bill-step">
          <legend>Mes informations</legend>
          <p class="bill-step__lead">Votre adresse e-mail sert à vous connecter.</p>
          <div class="bill-step__fields">
            <div class="form-row">
              <div class="field">
                <label for="profil-prenom">Prénom</label>
                <input class="input<?= invalidClass($errors, 'prenom') ?>" id="profil-prenom" name="prenom" type="text" autocomplete="given-name" value="<?= e($old['prenom']) ?>" <?= $attr($errors, 'prenom') ?>>
                <?= $msg($errors, 'prenom') ?>
              </div>
              <div class="field">
                <label for="profil-nom">Nom</label>
                <input class="input<?= invalidClass($errors, 'nom') ?>" id="profil-nom" name="nom" type="text" autocomplete="family-name" value="<?= e($old['nom']) ?>" <?= $attr($errors, 'nom') ?>>
                <?= $msg($errors, 'nom') ?>
              </div>
            </div>
            <div class="field">
              <label for="profil-email">Adresse e-mail</label>
              <input class="input<?= invalidClass($errors, 'email') ?>" id="profil-email" name="email" type="text" inputmode="email" autocomplete="email" value="<?= e($old['email']) ?>" <?= $attr($errors, 'email') ?>>
              <?= $msg($errors, 'email') ?>
            </div>
            <div class="form-row">
              <div class="field">
                <label for="profil-telephone">Téléphone <span class="bill-optional">— facultatif</span></label>
                <input class="input<?= invalidClass($errors, 'telephone') ?>" id="profil-telephone" name="telephone" type="text" inputmode="tel" autocomplete="tel" placeholder="22 123 456" value="<?= e($old['telephone']) ?>" <?= $attr($errors, 'telephone') ?>>
                <?= $msg($errors, 'telephone') ?>
              </div>
              <div class="field">
                <label for="profil-zone">Gouvernorat</label>
                <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="profil-zone" name="id_zone" <?= $attr($errors, 'id_zone') ?>>
                  <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                  <option value="<?= $zone->getIdZone() ?>"<?= $old['id_zone'] === (string) $zone->getIdZone() ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                </select>
                <?= $msg($errors, 'id_zone') ?>
              </div>
            </div>
            <div class="field">
              <label for="profil-adresse">Adresse <span class="bill-optional">— facultatif</span></label>
              <input class="input<?= invalidClass($errors, 'adresse') ?>" id="profil-adresse" name="adresse" type="text" autocomplete="street-address" value="<?= e($old['adresse']) ?>" <?= $attr($errors, 'adresse') ?>>
              <?= $msg($errors, 'adresse') ?>
            </div>
          </div>
        </fieldset>
        <div class="bill-wizard__foot">
          <a class="bill-btn bill-btn--glass" href="<?= url('mon-compte') ?>">Annuler</a>
          <button class="bill-btn bill-btn--glow" type="submit" data-loading="Enregistrement…"><span data-icon="check"></span>Enregistrer mon profil</button>
        </div>
      </form>

      <form class="bill-wizard bill-wizard--full bill-glass" method="post" action="<?= url('mon-mot-de-passe') ?>" data-validate data-citoyen-form>
        <?= csrfField() ?>
<?php if ($errorsMdp !== []): ?>
        <div class="form-alert form-alert--error" role="alert"><?= e($errorsMdp['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
        <fieldset class="bill-step">
          <legend>Mot de passe</legend>
          <p class="bill-step__lead">Votre mot de passe actuel est demandé pour le changer.</p>
          <div class="bill-step__fields">
            <div class="field">
              <label for="profil-password_actuel">Mot de passe actuel</label>
              <input class="input<?= invalidClass($errorsMdp, 'password_actuel') ?>" id="profil-password_actuel" name="password_actuel" type="password" autocomplete="current-password" <?= $attr($errorsMdp, 'password_actuel') ?>>
              <?= $msg($errorsMdp, 'password_actuel') ?>
            </div>
            <div class="form-row">
              <div class="field">
                <label for="profil-password">Nouveau mot de passe</label>
                <input class="input<?= invalidClass($errorsMdp, 'password') ?>" id="profil-password" name="password" type="password" autocomplete="new-password" <?= $attr($errorsMdp, 'password', 'profil-password-aide') ?>>
                <p class="field__hint" id="profil-password-aide">8 caractères au moins, une lettre et un chiffre.</p>
                <?= $msg($errorsMdp, 'password') ?>
              </div>
              <div class="field">
                <label for="profil-password_confirm">Confirmation</label>
                <input class="input<?= invalidClass($errorsMdp, 'password_confirm') ?>" id="profil-password_confirm" name="password_confirm" type="password" autocomplete="new-password" <?= $attr($errorsMdp, 'password_confirm') ?>>
                <?= $msg($errorsMdp, 'password_confirm') ?>
              </div>
            </div>
          </div>
        </fieldset>
        <div class="bill-wizard__foot">
          <span class="bill-wizard__end">
            <button class="bill-btn bill-btn--glow" type="submit" data-loading="Enregistrement…"><span data-icon="lock"></span>Changer mon mot de passe</button>
          </span>
        </div>
      </form>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mon profil — WASLA';
$description = 'Modifier votre profil et votre mot de passe WASLA.';
$currentPage = 'mon-compte';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/factures-crud.js', 'js/citoyens-crud.js'];
require __DIR__ . '/../../layout.php';
