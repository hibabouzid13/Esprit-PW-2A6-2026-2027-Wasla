<?php
/**
 * Vue « Ajouter / modifier un fournisseur » — Back Office (super admin) — Module 4 (Bechir).
 * Variables reçues : $fournisseur (?UserModel, null = ajout), $old, $errors, $gouvernorats.
 *
 * Le rôle est écrit en dur (fournisseur). Le mot de passe n'est demandé qu'à la création.
 * Statut et vérification ne sont pas dans ce formulaire : boutons Suspendre / Vérifier de la liste ou de la fiche.
 */
$isNew = $fournisseur === null;
$rules = FournisseurController::rules($isNew ? 'admin-ajout' : 'admin-modif');

$adminError = fn (string $name): string => '<p class="admin-field__error" data-error-for="' . e($name) . '">' . e($errors[$name] ?? '') . '</p>';
$hasError = fn (string $name): string => isset($errors[$name]) ? ' has-error' : '';
$input = function (string $name, string $label, bool $required, string $extra = '') use ($old, $rules, $adminError, $hasError): string {
    return '<div class="admin-field' . $hasError($name) . '">'
        . '<label for="c-' . e($name) . '">' . e($label) . ($required ? ' <span class="req">*</span>' : '') . '</label>'
        . '<input class="admin-input" id="c-' . e($name) . '" name="' . e($name) . '" value="' . e($old[$name]) . '" data-rules="' . e($rules[$name]) . '"' . $extra . '>'
        . $adminError($name) . '</div>';
};
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="<?= $isNew ? 'plus' : 'pencil' ?>"></span>
            <div>
              <h1><?= $isNew ? 'Ajouter un fournisseur' : 'Modifier le fournisseur #' . $fournisseur->getIdUtilisateur() ?></h1>
              <p><a href="<?= url('admin-fournisseurs') ?>">Comptes fournisseurs</a> › <?= $isNew ? 'Nouveau compte' : e($fournisseur->getEmail()) ?></p>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
<?php if (isset($errors['form'])): ?>
              <p class="admin-flash admin-flash--error"><?= e($errors['form']) ?></p>
<?php endif; ?>
              <form class="admin-form" method="post" action="<?= url('admin-fournisseur-form', $isNew ? [] : ['id' => $fournisseur->getIdUtilisateur()]) ?>" data-validate>
                <?= csrfField() ?>
                <?= $input('nom_organisation', 'Nom commercial', true, ' placeholder="ex. Trabelsi Plomberie"') ?>
                <div class="admin-field<?= $hasError('specialite') ?>">
                  <label for="c-specialite">Spécialité <span class="req">*</span></label>
                  <select class="admin-select" id="c-specialite" name="specialite" data-rules="<?= e($rules['specialite']) ?>">
                    <option value="">Choisir…</option>
<?php foreach (UserModel::SPECIALITES as $spec): ?>
                    <option value="<?= e($spec) ?>"<?= $old['specialite'] === $spec ? ' selected' : '' ?>><?= e(specialiteLabel($spec)) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= $adminError('specialite') ?>
                </div>
                <?= $input('prenom', 'Prénom', true) ?>
                <?= $input('nom', 'Nom', true) ?>
                <?= $input('email', 'Adresse e-mail', true, ' autocomplete="off"') ?>
                <?= $input('telephone', 'Téléphone', true, ' inputmode="tel" placeholder="22 123 456"') ?>
                <div class="admin-field<?= $hasError('id_zone') ?>">
                  <label for="c-id_zone">Gouvernorat <span class="req">*</span></label>
                  <select class="admin-select" id="c-id_zone" name="id_zone" data-rules="<?= e($rules['id_zone']) ?>">
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                    <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= $adminError('id_zone') ?>
                </div>
                <?= $input('adresse', 'Adresse', false) ?>
                <div class="admin-field admin-field--full<?= $hasError('description') ?>">
                  <label for="c-description">Description des services</label>
                  <textarea class="admin-textarea" id="c-description" name="description" data-rules="<?= e($rules['description']) ?>"><?= e($old['description']) ?></textarea>
                  <span class="admin-field__hint">500 caractères maximum.</span>
                  <?= $adminError('description') ?>
                </div>
<?php if ($isNew): ?>
                <div class="admin-field<?= $hasError('password') ?>">
                  <label for="c-pass">Mot de passe <span class="req">*</span></label>
                  <input class="admin-input" id="c-pass" name="password" type="password" autocomplete="new-password" data-rules="<?= e($rules['password']) ?>">
                  <span class="admin-field__hint">Au moins 8 caractères, dont une lettre et un chiffre.</span>
                  <?= $adminError('password') ?>
                </div>
                <div class="admin-field<?= $hasError('password_confirm') ?>">
                  <label for="c-pass2">Confirmer le mot de passe <span class="req">*</span></label>
                  <input class="admin-input" id="c-pass2" name="password_confirm" type="password" autocomplete="new-password" data-rules="<?= e($rules['password_confirm']) ?>">
                  <?= $adminError('password_confirm') ?>
                </div>
<?php endif; ?>
                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= url($isNew ? 'admin-fournisseurs' : 'admin-fournisseur-voir', $isNew ? [] : ['id' => $fournisseur->getIdUtilisateur()]) ?>">Annuler</a>
                  <button class="admin-btn admin-btn--primary" type="submit" data-loading="Enregistrement…"><span data-icon="check"></span> <?= $isNew ? 'Créer le compte' : 'Enregistrer' ?></button>
                </div>
              </form>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>Rôle</dt><dd>Fournisseur (fixe, ne change jamais).</dd></div>
                <div><dt>Vérification</dt><dd>Un nouveau compte n'est pas vérifié. Cliquez « Vérifier » dans la liste ou la fiche pour le publier dans l'annuaire.</dd></div>
                <div><dt>Mot de passe</dt><dd>Choisi à la création, enregistré haché. Ensuite, seul le fournisseur peut le changer, depuis son profil.</dd></div>
                <div><dt>Suppression</dt><dd>Impossible si le compte a des données (demandes, réponses…) : suspendez-le.</dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($isNew ? 'Nouveau fournisseur' : 'Modifier un fournisseur') . ' — WASLA Admin';
$currentPage = 'admin-fournisseur-form';
require __DIR__ . '/../../layout.php';
