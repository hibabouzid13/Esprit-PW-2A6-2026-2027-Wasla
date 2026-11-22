<?php
/**
 * Vue : ajout / modification d'un citoyen — Back Office (super admin) — Module 2 (Hiba).
 * Variables reçues : $citoyen (UserModel|null : null = ajout), $old (valeurs), $errors,
 *                    $gouvernorats (ZoneModel[]).
 * Validation : validation.js (règles fournies par CitoyenController) puis CitoyenController en PHP.
 */
ob_start();
$edition = $citoyen !== null;
$action = $edition ? url('admin-citoyen-form', ['id' => $citoyen->getIdUtilisateur()]) : url('admin-citoyen-form');
$retour = $edition ? url('admin-citoyen-voir', ['id' => $citoyen->getIdUtilisateur()]) : url('admin-citoyens');
$err = fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
$msg = fn (string $k): string => '<p class="admin-field__error" id="' . $k . '-erreur" aria-live="polite">' . e($errors[$k] ?? '') . '</p>';
// Attributs communs d'un champ : règles, messages, accessibilité
$attr = fn (string $k, string $aide = ''): string => CitoyenController::attributs($k)
    . ' aria-describedby="' . ($aide !== '' ? $aide . ' ' : '') . $k . '-erreur"'
    . (in_array($k, ['telephone', 'adresse'], true) ? '' : ' aria-required="true"')
    . (isset($errors[$k]) ? ' aria-invalid="true"' : '');
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/citoyens-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head bill-admin-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-citoyens') ?>">Comptes citoyens</a><span>/</span><span><?= $edition ? 'Modifier' : 'Ajouter' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="user"></span>
              <div>
                <h1><?= $edition ? 'Modifier un citoyen' : 'Ajouter un citoyen' ?></h1>
                <p><?= $edition ? e($citoyen->getPrenom() . ' ' . $citoyen->getNom()) : 'Créer un compte de rôle citoyen' ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
<?php if ($errors !== []): ?>
              <p class="admin-flash admin-flash--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></p>
<?php endif; ?>
              <form class="admin-form" method="post" action="<?= e($action) ?>" data-validate>
                <?= csrfField() ?>

                <div class="admin-field<?= $err('prenom') ?>">
                  <label for="prenom">Prénom <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="prenom" name="prenom" type="text" autocomplete="off" value="<?= e($old['prenom']) ?>" <?= $attr('prenom') ?>>
                  <?= $msg('prenom') ?>
                </div>

                <div class="admin-field<?= $err('nom') ?>">
                  <label for="nom">Nom <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="nom" name="nom" type="text" autocomplete="off" value="<?= e($old['nom']) ?>" <?= $attr('nom') ?>>
                  <?= $msg('nom') ?>
                </div>

                <div class="admin-field<?= $err('email') ?>">
                  <label for="email">Adresse e-mail <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="email" name="email" type="text" inputmode="email" autocomplete="off" placeholder="nom@exemple.tn" value="<?= e($old['email']) ?>" <?= $attr('email') ?>>
                  <?= $msg('email') ?>
                </div>

                <div class="admin-field<?= $err('telephone') ?>">
                  <label for="telephone">Téléphone <span class="admin-field__optional">(facultatif)</span></label>
                  <input class="admin-input" id="telephone" name="telephone" type="text" inputmode="tel" autocomplete="off" placeholder="22 123 456" value="<?= e($old['telephone']) ?>" <?= $attr('telephone') ?>>
                  <?= $msg('telephone') ?>
                </div>

                <div class="admin-field<?= $err('id_zone') ?>">
                  <label for="id_zone">Gouvernorat <span class="req" aria-hidden="true">*</span></label>
                  <select class="admin-select" id="id_zone" name="id_zone" <?= $attr('id_zone') ?>>
                    <option value="">— Choisir —</option>
<?php foreach ($gouvernorats as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= $old['id_zone'] === (string) $zone->getIdZone() ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= $msg('id_zone') ?>
                </div>

                <div class="admin-field<?= $err('adresse') ?>">
                  <label for="adresse">Adresse <span class="admin-field__optional">(facultatif)</span></label>
                  <input class="admin-input" id="adresse" name="adresse" type="text" autocomplete="off" value="<?= e($old['adresse']) ?>" <?= $attr('adresse') ?>>
                  <?= $msg('adresse') ?>
                </div>

<?php if (!$edition): ?>
                <div class="admin-field<?= $err('password') ?>">
                  <label for="password">Mot de passe <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="password" name="password" type="password" autocomplete="new-password" <?= $attr('password', 'password-aide') ?>>
                  <p class="admin-field__hint" id="password-aide">8 caractères au moins, une lettre et un chiffre.</p>
                  <?= $msg('password') ?>
                </div>

                <div class="admin-field<?= $err('password_confirm') ?>">
                  <label for="password_confirm">Confirmation <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" <?= $attr('password_confirm') ?>>
                  <?= $msg('password_confirm') ?>
                </div>
<?php endif; ?>

                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= e($retour) ?>">Annuler</a>
                  <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="save"></span> Enregistrer</button>
                </div>
              </form>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="info"></span> À savoir</h2>
            </div>
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>Rôle</dt><dd>Toujours citoyen : cette page ne change jamais un rôle.</dd></div>
                <div><dt>Adresse e-mail</dt><dd>Elle sert à se connecter et ne peut appartenir qu'à un seul compte.</dd></div>
                <div><dt>Mot de passe</dt><dd><?= $edition ? 'Seul le citoyen peut le changer, avec son mot de passe actuel.' : 'Communiquez-le au citoyen : il pourra le changer dans son profil.' ?></dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($edition ? 'Modifier un citoyen' : 'Ajouter un citoyen') . ' — WASLA Admin';
$currentPage = 'admin-citoyen-form';
$pageScripts = ['js/factures-crud.js'];   // icônes du module
require __DIR__ . '/../../layout.php';
