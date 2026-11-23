<?php
/**
 * Vue « Ajouter / modifier une entreprise » — Back Office (admin) — Module 3 (Louay).
 * Variables reçues : $entreprise (?UserModel, null = ajout), $errors, $old, $gouvernorats (ZoneModel[]), $secteurs (string[]).
 * Formulaire POST : data-validate + data-rules, mêmes règles et mêmes messages (EntrepriseController::MESSAGES)
 * dans EntrepriseController::validateEntreprise(). Pas de mot de passe en modification.
 */
$isEdit = $entreprise !== null;
$action = $isEdit ? url('admin-entreprise-form', ['id' => $entreprise->getIdUtilisateur()]) : url('admin-entreprise-form');

/** Classe « has-error » du conteneur .admin-field quand PHP a trouvé une erreur. */
$fieldClass = static fn (string $name, string $extra = ''): string => trim('admin-field ' . $extra . (isset($errors[$name]) ? ' has-error' : ''));
/** Zone d'erreur au format Back Office (validation.js la remplit aussi). */
$adminError = static fn (string $name): string => '<p class="admin-field__error" data-error-for="' . e($name) . '">' . e($errors[$name] ?? '') . '</p>';
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="building-2"></span>
            <div>
              <h1><?= $isEdit ? 'Modifier « ' . e($entreprise->getNomOrganisation() ?? '') . ' »' : 'Ajouter une entreprise' ?></h1>
              <p>Compte utilisateur de rôle « entreprise » : informations professionnelles et responsable du compte.</p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-entreprises') ?>">Retour à la liste</a>
          </div>
        </div>

<?php if ($errors !== []): ?>
        <p class="admin-flash admin-flash--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></p>
<?php endif; ?>

        <section class="admin-card">
          <div class="admin-card__body">
            <form class="admin-form" method="post" action="<?= $action ?>" data-validate>
              <?= csrfField() ?>

              <div class="<?= $fieldClass('nom_organisation') ?>">
                <label for="e-org">Raison sociale <span class="req">*</span></label>
                <input class="admin-input" id="e-org" name="nom_organisation" type="text" value="<?= e($old['nom_organisation']) ?>" data-rules="required|min:2|max:100"<?= EntrepriseController::messageAttrs('nom_organisation') ?>>
                <?= $adminError('nom_organisation') ?>
              </div>
              <div class="<?= $fieldClass('matricule_fiscal') ?>">
                <label for="e-mf">Matricule fiscal <span class="req">*</span></label>
                <input class="admin-input" id="e-mf" name="matricule_fiscal" type="text" placeholder="1234567A" value="<?= e($old['matricule_fiscal']) ?>" data-rules="required|max:20" data-extra-rules="matricule"<?= EntrepriseController::messageAttrs('matricule_fiscal') ?>>
                <span class="admin-field__hint">7 chiffres et une lettre, éventuellement suivis de /A/M/000.</span>
                <?= $adminError('matricule_fiscal') ?>
              </div>

              <div class="<?= $fieldClass('secteur_activite') ?>">
                <label for="e-secteur">Secteur d'activité <span class="req">*</span></label>
                <select class="admin-select" id="e-secteur" name="secteur_activite" data-rules="required|max:60"<?= EntrepriseController::messageAttrs('secteur_activite') ?>>
                  <option value="">Choisir…</option>
<?php foreach ($secteurs as $secteur): ?>
                  <option value="<?= e($secteur) ?>"<?= $secteur === $old['secteur_activite'] ? ' selected' : '' ?>><?= e($secteur) ?></option>
<?php endforeach; ?>
                </select>
                <?= $adminError('secteur_activite') ?>
              </div>
              <div class="<?= $fieldClass('id_zone') ?>">
                <label for="e-zone">Gouvernorat <span class="req">*</span></label>
                <select class="admin-select" id="e-zone" name="id_zone" data-rules="required|int"<?= EntrepriseController::messageAttrs('id_zone') ?>>
                  <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                  <option value="<?= $zone->getIdZone() ?>"<?= (string) $zone->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                </select>
                <?= $adminError('id_zone') ?>
              </div>

              <div class="<?= $fieldClass('adresse', 'admin-field--full') ?>">
                <label for="e-adresse">Adresse <span class="req">*</span></label>
                <input class="admin-input" id="e-adresse" name="adresse" type="text" value="<?= e($old['adresse']) ?>" data-rules="required|min:5|max:255"<?= EntrepriseController::messageAttrs('adresse') ?>>
                <?= $adminError('adresse') ?>
              </div>

              <div class="<?= $fieldClass('prenom') ?>">
                <label for="e-prenom">Prénom du responsable <span class="req">*</span></label>
                <input class="admin-input" id="e-prenom" name="prenom" type="text" value="<?= e($old['prenom']) ?>" data-rules="required|name"<?= EntrepriseController::messageAttrs('prenom') ?>>
                <?= $adminError('prenom') ?>
              </div>
              <div class="<?= $fieldClass('nom') ?>">
                <label for="e-nom">Nom du responsable <span class="req">*</span></label>
                <input class="admin-input" id="e-nom" name="nom" type="text" value="<?= e($old['nom']) ?>" data-rules="required|name"<?= EntrepriseController::messageAttrs('nom') ?>>
                <?= $adminError('nom') ?>
              </div>

              <div class="<?= $fieldClass('email') ?>">
                <label for="e-email">E-mail (identifiant de connexion) <span class="req">*</span></label>
                <input class="admin-input" id="e-email" name="email" type="text" inputmode="email" value="<?= e($old['email']) ?>" data-rules="required|email"<?= EntrepriseController::messageAttrs('email') ?>>
                <?= $adminError('email') ?>
              </div>
              <div class="<?= $fieldClass('telephone') ?>">
                <label for="e-tel">Téléphone <span class="req">*</span></label>
                <input class="admin-input" id="e-tel" name="telephone" type="text" inputmode="tel" value="<?= e($old['telephone']) ?>" data-rules="required|phone"<?= EntrepriseController::messageAttrs('telephone') ?>>
                <?= $adminError('telephone') ?>
              </div>

<?php if (!$isEdit): /* Mot de passe uniquement à la création : l'admin ne remplace jamais celui d'une entreprise. */ ?>
              <div class="<?= $fieldClass('password') ?>">
                <label for="e-password">Mot de passe <span class="req">*</span></label>
                <input class="admin-input" id="e-password" name="password" type="password" autocomplete="new-password" data-rules="required|password"<?= EntrepriseController::messageAttrs('password') ?>>
                <span class="admin-field__hint">8 caractères min., une lettre et un chiffre. L'entreprise pourra le changer depuis son profil.</span>
                <?= $adminError('password') ?>
              </div>
              <div class="<?= $fieldClass('password_confirm') ?>">
                <label for="e-password-confirm">Confirmation <span class="req">*</span></label>
                <input class="admin-input" id="e-password-confirm" name="password_confirm" type="password" autocomplete="new-password"
                       data-rules="required|match:password"<?= EntrepriseController::messageAttrs('password_confirm') ?>>
                <?= $adminError('password_confirm') ?>
              </div>
<?php else: ?>
              <p class="admin-field admin-field--full admin-field__hint">Le mot de passe n'est pas modifiable ici : seule l'entreprise change le sien, depuis son profil, avec son mot de passe actuel.</p>
<?php endif; ?>

              <div class="<?= $fieldClass('description', 'admin-field--full') ?>">
                <label for="e-desc">Présentation</label>
                <textarea class="admin-textarea" id="e-desc" name="description" data-rules="max:500"><?= e($old['description']) ?></textarea>
                <?= $adminError('description') ?>
              </div>

              <div class="admin-field admin-field--full">
                <label class="admin-choice"><input type="checkbox" name="compte_verifie" value="1"<?= $old['compte_verifie'] !== '' ? ' checked' : '' ?>><span><span data-icon="shield-check"></span> Compte validé (peut publier des refuges)</span></label>
              </div>

              <div class="admin-form__actions">
                <a class="admin-btn admin-btn--outline" href="<?= url('admin-entreprises') ?>">Annuler</a>
                <button class="admin-btn admin-btn--primary" type="submit" data-loading="Enregistrement…"><?= $isEdit ? 'Enregistrer' : 'Créer le compte' ?></button>
              </div>
            </form>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = ($isEdit ? 'Modifier une entreprise' : 'Ajouter une entreprise') . ' — WASLA Admin';
$currentPage = 'admin-entreprise-form';
$pageScripts = ['js/services/refuge-forms.js'];
require __DIR__ . '/../../layout.php';
