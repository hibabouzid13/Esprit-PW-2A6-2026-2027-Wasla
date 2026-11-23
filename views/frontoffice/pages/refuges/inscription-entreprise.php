<?php
/**
 * Vue « Créer un compte entreprise » — Front Office — Module 3 (Louay).
 * Variables reçues : $errors, $old, $gouvernorats (ZoneModel[]), $secteurs (string[]).
 * Le compte est créé avec le rôle « entreprise » et compte_verifie = 0 (EntrepriseController::register()) :
 * l'admin doit le valider avant que l'entreprise puisse publier des refuges.
 */
ob_start();
$brandTitle = 'Ouvrez vos portes avec <span>WASLA.</span>';
$brandText  = 'Café, hôtel, centre commercial… Proposez vos locaux climatisés, votre eau potable ou vos prises de recharge aux habitants pendant les coupures.';
?>
  <main id="main" class="login-page">
    <div class="container">
      <div class="login-shell">
<?php require __DIR__ . '/../../partials/auth-brand.php'; ?>

        <section class="login-form-panel" aria-labelledby="ent-title">
          <div class="login-form-wrap login-form-wrap--wide">
            <p class="eyebrow">Espace entreprise</p>
            <h1 id="ent-title">Créer un compte entreprise</h1>
            <p>Votre compte sera vérifié par l'administrateur WASLA avant la publication de vos refuges.</p>

<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>

            <form class="login-form" method="post" action="<?= url('inscription-entreprise') ?>" data-validate>
              <?= csrfField() ?>

              <div class="field">
                <label for="ent-org">Raison sociale</label>
                <input class="input<?= invalidClass($errors, 'nom_organisation') ?>" id="ent-org" name="nom_organisation" type="text" autocomplete="organization"
                       placeholder="ex. Café Le Belvédère" value="<?= e($old['nom_organisation']) ?>" data-rules="required|min:2|max:100"<?= EntrepriseController::messageAttrs('nom_organisation') ?>>
                <?= fieldError($errors, 'nom_organisation') ?>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="ent-mf">Matricule fiscal</label>
                  <input class="input<?= invalidClass($errors, 'matricule_fiscal') ?>" id="ent-mf" name="matricule_fiscal" type="text"
                         placeholder="1234567A" value="<?= e($old['matricule_fiscal']) ?>" data-rules="required|max:20" data-extra-rules="matricule"<?= EntrepriseController::messageAttrs('matricule_fiscal') ?>>
                  <p class="field__hint">7 chiffres et une lettre (ex. 1234567A).</p>
                  <?= fieldError($errors, 'matricule_fiscal') ?>
                </div>
                <div class="field">
                  <label for="ent-secteur">Secteur d'activité</label>
                  <select class="select<?= invalidClass($errors, 'secteur_activite') ?>" id="ent-secteur" name="secteur_activite"
                          data-rules="required|max:60"<?= EntrepriseController::messageAttrs('secteur_activite') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($secteurs as $secteur): ?>
                    <option value="<?= e($secteur) ?>"<?= $secteur === $old['secteur_activite'] ? ' selected' : '' ?>><?= e($secteur) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'secteur_activite') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="ent-prenom">Prénom du responsable</label>
                  <input class="input<?= invalidClass($errors, 'prenom') ?>" id="ent-prenom" name="prenom" type="text" autocomplete="given-name"
                         value="<?= e($old['prenom']) ?>" data-rules="required|name"<?= EntrepriseController::messageAttrs('prenom') ?>>
                  <?= fieldError($errors, 'prenom') ?>
                </div>
                <div class="field">
                  <label for="ent-nom">Nom du responsable</label>
                  <input class="input<?= invalidClass($errors, 'nom') ?>" id="ent-nom" name="nom" type="text" autocomplete="family-name"
                         value="<?= e($old['nom']) ?>" data-rules="required|name"<?= EntrepriseController::messageAttrs('nom') ?>>
                  <?= fieldError($errors, 'nom') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="ent-email">Adresse e-mail</label>
                  <input class="input<?= invalidClass($errors, 'email') ?>" id="ent-email" name="email" type="text" inputmode="email" autocomplete="email"
                         placeholder="contact@entreprise.tn" value="<?= e($old['email']) ?>" data-rules="required|email"<?= EntrepriseController::messageAttrs('email') ?>>
                  <?= fieldError($errors, 'email') ?>
                </div>
                <div class="field">
                  <label for="ent-tel">Téléphone</label>
                  <input class="input<?= invalidClass($errors, 'telephone') ?>" id="ent-tel" name="telephone" type="text" inputmode="tel" autocomplete="tel"
                         placeholder="71 234 567" value="<?= e($old['telephone']) ?>" data-rules="required|phone"<?= EntrepriseController::messageAttrs('telephone') ?>>
                  <?= fieldError($errors, 'telephone') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="ent-adresse">Adresse</label>
                  <input class="input<?= invalidClass($errors, 'adresse') ?>" id="ent-adresse" name="adresse" type="text" autocomplete="street-address"
                         value="<?= e($old['adresse']) ?>" data-rules="required|min:5|max:255"<?= EntrepriseController::messageAttrs('adresse') ?>>
                  <?= fieldError($errors, 'adresse') ?>
                </div>
                <div class="field">
                  <label for="ent-zone">Gouvernorat</label>
                  <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="ent-zone" name="id_zone"
                          data-rules="required|int"<?= EntrepriseController::messageAttrs('id_zone') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= (string) $zone->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'id_zone') ?>
                </div>
              </div>

              <div class="field">
                <label for="ent-desc">Présentation <span class="field__optional">(facultatif)</span></label>
                <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="ent-desc" name="description" rows="3"
                          data-rules="max:500"><?= e($old['description']) ?></textarea>
                <?= fieldError($errors, 'description') ?>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="ent-password">Mot de passe</label>
                  <input class="input<?= invalidClass($errors, 'password') ?>" id="ent-password" name="password" type="password"
                         autocomplete="new-password" data-rules="required|password"<?= EntrepriseController::messageAttrs('password') ?>>
                  <p class="field__hint">8 caractères min., une lettre et un chiffre.</p>
                  <?= fieldError($errors, 'password') ?>
                </div>
                <div class="field">
                  <label for="ent-password-confirm">Confirmation</label>
                  <input class="input<?= invalidClass($errors, 'password_confirm') ?>" id="ent-password-confirm" name="password_confirm" type="password"
                         autocomplete="new-password" data-rules="required|match:password"<?= EntrepriseController::messageAttrs('password_confirm') ?>>
                  <?= fieldError($errors, 'password_confirm') ?>
                </div>
              </div>

              <div class="field">
                <label class="checkbox"><input type="checkbox" name="terms" value="1"<?= $old['terms'] !== '' ? ' checked' : '' ?>
                       data-rules="accepted"<?= EntrepriseController::messageAttrs('terms') ?>> J'accepte les conditions d'utilisation de WASLA et je certifie l'exactitude des informations de mon entreprise.</label>
                <?= fieldError($errors, 'terms') ?>
              </div>

              <button class="btn btn--primary btn--block btn--lg" type="submit" data-loading="Création du compte…">Créer le compte entreprise</button>
            </form>

            <p class="login-alt">Déjà inscrit ? <a href="<?= url('connexion') ?>">Se connecter</a> · Particulier ? <a href="<?= url('inscription') ?>">Compte citoyen</a></p>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Compte entreprise — WASLA';
$description = 'Créez un compte entreprise WASLA pour proposer une zone refuge.';
$currentPage = 'inscription';
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/services/refuge-forms.js'];
require __DIR__ . '/../../layout.php';
