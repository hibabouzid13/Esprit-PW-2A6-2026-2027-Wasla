<?php
/**
 * Vue : Back Office : ajout / modification d'un sous-admin STEG ou SONEDE — Module 1 (Houssem). Super admin uniquement.
 * Variables reçues :
 *   $compte        ?UserModel            null = ajout, sinon modification
 *   $gouvernorats  ZoneModel[]
 *   $errors        array<string,string>  erreurs du contrôle serveur (CompteController)
 *   $old           array<string,string>  saisie précédente (jamais le mot de passe)
 * Le rôle est choisi à l'AJOUT seulement (STEG ou SONEDE) et ne change plus ensuite.
 * Le mot de passe est saisi à l'ajout seulement : ensuite, seul le titulaire le change (admin-profil).
 * Contrôle de saisie : data-validate + data-rules (validation.js), mêmes règles et mêmes messages en PHP.
 */
$isEdit = $compte !== null;

$val = static function (string $field) use ($old, $compte): string {
    if (array_key_exists($field, $old)) {
        return $old[$field];
    }
    if ($compte === null) {
        return '';
    }
    return match ($field) {
        'prenom'           => $compte->getPrenom(),
        'nom'              => $compte->getNom(),
        'email'            => $compte->getEmail(),
        'telephone'        => $compte->getTelephone() ?? '',
        'id_zone'          => $compte->getIdZone() !== null ? (string) $compte->getIdZone() : '',
        'nom_organisation' => $compte->getNomOrganisation() ?? '',
        default            => '',
    };
};
$err     = static fn (string $f): string => isset($errors[$f]) ? ' has-error' : '';
$errText = static fn (string $f): string => e($errors[$f] ?? '');
$msg     = static fn (string $f): string => CompteController::messageAttrs($f);

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-sous-admins') ?>">Comptes sous-admins</a><span>/</span><span><?= $isEdit ? 'Modifier' : 'Ajouter' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="<?= $isEdit ? 'pencil' : 'user-plus' ?>"></span>
              <div>
                <h1><?= $isEdit ? 'Modifier le sous-admin' : 'Ajouter un sous-admin' ?></h1>
                <p><?= $isEdit ? e(roleLabel($compte->getRole())) . ' · le rôle ne peut plus être changé' : 'Agent STEG (électricité) ou SONEDE (eau)' ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
              <form class="admin-form" method="post" action="<?= url('admin-sous-admin-form', $isEdit ? ['id' => $compte->getIdUtilisateur()] : []) ?>" data-validate>
                <?= csrfField() ?>
<?php if ($isEdit): ?>
                <input type="hidden" name="id_utilisateur" value="<?= $compte->getIdUtilisateur() ?>">
<?php else: ?>
                <fieldset class="admin-field admin-field--full<?= $err('role') ?>">
                  <legend>Rôle <span class="req">*</span></legend>
                  <div class="admin-choices">
<?php foreach (['steg' => ['STEG — électricité', 'zap'], 'sonede' => ['SONEDE — eau', 'droplet']] as $r => [$label, $icon]): ?>
                    <label class="admin-choice">
                      <input type="radio" name="role" value="<?= $r ?>"<?= $val('role') === $r ? ' checked' : '' ?> data-rules="required|in:steg,sonede"<?= $msg('role') ?>>
                      <span><span data-icon="<?= $icon ?>"></span> <?= $label ?></span>
                    </label>
<?php endforeach; ?>
                  </div>
                  <p class="admin-field__hint">Choisi une fois pour toutes : il ne pourra plus être modifié.</p>
                  <p class="admin-field__error"><?= $errText('role') ?></p>
                </fieldset>
<?php endif; ?>

                <div class="admin-field<?= $err('prenom') ?>">
                  <label for="prenom">Prénom <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="prenom" name="prenom" value="<?= e($val('prenom')) ?>" autocomplete="off" data-rules="required|name"<?= $msg('prenom') ?>>
                  <p class="admin-field__error"><?= $errText('prenom') ?></p>
                </div>
                <div class="admin-field<?= $err('nom') ?>">
                  <label for="nom">Nom <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="nom" name="nom" value="<?= e($val('nom')) ?>" autocomplete="off" data-rules="required|name"<?= $msg('nom') ?>>
                  <p class="admin-field__error"><?= $errText('nom') ?></p>
                </div>

                <div class="admin-field<?= $err('email') ?>">
                  <label for="email">E-mail professionnel <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="email" name="email" value="<?= e($val('email')) ?>" autocomplete="off" placeholder="prenom.nom@steg.com.tn" data-rules="required|email"<?= $msg('email') ?>>
                  <p class="admin-field__error"><?= $errText('email') ?></p>
                </div>
                <div class="admin-field<?= $err('telephone') ?>">
                  <label for="telephone">Téléphone <small>(facultatif)</small></label>
                  <input class="admin-input" type="text" id="telephone" name="telephone" value="<?= e($val('telephone')) ?>" inputmode="tel" placeholder="71 123 456" data-rules="phone">
                  <p class="admin-field__error"><?= $errText('telephone') ?></p>
                </div>

                <div class="admin-field<?= $err('id_zone') ?>">
                  <label for="id_zone">Gouvernorat <span class="req">*</span></label>
                  <select class="admin-select" id="id_zone" name="id_zone" data-rules="required|int"<?= $msg('id_zone') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                    <option value="<?= $g->getIdZone() ?>"<?= $val('id_zone') === (string) $g->getIdZone() ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <p class="admin-field__error"><?= $errText('id_zone') ?></p>
                </div>
                <div class="admin-field<?= $err('nom_organisation') ?>">
                  <label for="nom_organisation">Service / district <small>(facultatif)</small></label>
                  <input class="admin-input" type="text" id="nom_organisation" name="nom_organisation" value="<?= e($val('nom_organisation')) ?>" placeholder="Ex. STEG — District Sfax" data-rules="max:100">
                  <p class="admin-field__error"><?= $errText('nom_organisation') ?></p>
                </div>

<?php if (!$isEdit): ?>
                <div class="admin-field<?= $err('mot_de_passe') ?>">
                  <label for="mot_de_passe">Mot de passe initial <span class="req">*</span></label>
                  <input class="admin-input" type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password" data-rules="required|password"<?= $msg('mot_de_passe') ?>>
                  <p class="admin-field__hint">Au moins 8 caractères, dont une lettre et un chiffre. L'agent le changera depuis son profil.</p>
                  <p class="admin-field__error"><?= $errText('mot_de_passe') ?></p>
                </div>
                <div class="admin-field<?= $err('confirmation') ?>">
                  <label for="confirmation">Confirmer le mot de passe <span class="req">*</span></label>
                  <input class="admin-input" type="password" id="confirmation" name="confirmation" autocomplete="new-password" data-rules="required|match:mot_de_passe"<?= $msg('confirmation') ?>>
                  <p class="admin-field__error"><?= $errText('confirmation') ?></p>
                </div>
<?php endif; ?>

                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= url('admin-sous-admins') ?>">Annuler</a>
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
                <div><dt>Droits</dt><dd>Un agent STEG ne gère que les coupures d'électricité, un agent SONEDE que celles d'eau.</dd></div>
                <div><dt>Rôle</dt><dd>Fixé à la création. Pour un autre rôle, créez un autre compte.</dd></div>
                <div><dt>Mot de passe</dt><dd>Saisi à la création uniquement. Personne ne remplace ensuite le mot de passe d'un autre.</dd></div>
                <div><dt>Suppression</dt><dd>Refusée si le compte a publié des interruptions ou des alertes : suspendez-le.</dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($isEdit ? 'Modifier' : 'Ajouter') . ' un sous-admin — WASLA Admin';
$currentPage = 'admin-sous-admin-form';
require __DIR__ . '/../../layout.php';
