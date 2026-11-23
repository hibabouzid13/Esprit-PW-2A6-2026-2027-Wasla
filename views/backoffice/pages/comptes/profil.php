<?php
/**
 * Vue : Back Office : « Mon profil » (super admin, STEG, SONEDE) — Module 1 (Houssem).
 * Variables reçues :
 *   $moi           UserModel             compte connecté (id pris dans la session)
 *   $gouvernorats  ZoneModel[]
 *   $errors        array<string,string>  erreurs du formulaire de profil
 *   $old           array<string,string>  saisie précédente du profil
 *   $pwdErrors     array<string,string>  erreurs du changement de mot de passe (admin-mot-de-passe)
 * Le rôle et le statut sont affichés, jamais modifiables ici.
 */
$val = static function (string $field) use ($old, $moi): string {
    if (array_key_exists($field, $old)) {
        return $old[$field];
    }
    return match ($field) {
        'prenom'    => $moi->getPrenom(),
        'nom'       => $moi->getNom(),
        'email'     => $moi->getEmail(),
        'telephone' => $moi->getTelephone() ?? '',
        'id_zone'   => $moi->getIdZone() !== null ? (string) $moi->getIdZone() : '',
        default     => '',
    };
};
$err     = static fn (array $list, string $f): string => isset($list[$f]) ? ' has-error' : '';
$errText = static fn (array $list, string $f): string => e($list[$f] ?? '');
$msg     = static fn (string $f): string => CompteController::messageAttrs($f);

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Mon profil</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="user"></span>
              <div>
                <h1>Mon profil</h1>
                <p><?= e(roleLabel($moi->getRole())) ?> · <?= e($moi->getEmail()) ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="user"></span> Mes informations</h2>
            </div>
            <div class="admin-card__body">
              <form class="admin-form" method="post" action="<?= url('admin-profil') ?>" data-validate>
                <?= csrfField() ?>
                <div class="admin-field<?= $err($errors, 'prenom') ?>">
                  <label for="prenom">Prénom <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="prenom" name="prenom" value="<?= e($val('prenom')) ?>" data-rules="required|name"<?= $msg('prenom') ?>>
                  <p class="admin-field__error"><?= $errText($errors, 'prenom') ?></p>
                </div>
                <div class="admin-field<?= $err($errors, 'nom') ?>">
                  <label for="nom">Nom <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="nom" name="nom" value="<?= e($val('nom')) ?>" data-rules="required|name"<?= $msg('nom') ?>>
                  <p class="admin-field__error"><?= $errText($errors, 'nom') ?></p>
                </div>
                <div class="admin-field<?= $err($errors, 'email') ?>">
                  <label for="email">E-mail <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="email" name="email" value="<?= e($val('email')) ?>" data-rules="required|email"<?= $msg('email') ?>>
                  <p class="admin-field__error"><?= $errText($errors, 'email') ?></p>
                </div>
                <div class="admin-field<?= $err($errors, 'telephone') ?>">
                  <label for="telephone">Téléphone <small>(facultatif)</small></label>
                  <input class="admin-input" type="text" id="telephone" name="telephone" value="<?= e($val('telephone')) ?>" inputmode="tel" data-rules="phone">
                  <p class="admin-field__error"><?= $errText($errors, 'telephone') ?></p>
                </div>
                <div class="admin-field admin-field--full<?= $err($errors, 'id_zone') ?>">
                  <label for="id_zone">Gouvernorat <span class="req">*</span></label>
                  <select class="admin-select" id="id_zone" name="id_zone" data-rules="required|int"<?= $msg('id_zone') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                    <option value="<?= $g->getIdZone() ?>"<?= $val('id_zone') === (string) $g->getIdZone() ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <p class="admin-field__error"><?= $errText($errors, 'id_zone') ?></p>
                </div>
                <div class="admin-form__actions">
                  <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="save"></span> Enregistrer</button>
                </div>
              </form>
            </div>
          </section>

          <section class="admin-card" id="mot-de-passe">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="lock"></span> Changer mon mot de passe</h2>
            </div>
            <div class="admin-card__body">
              <form class="admin-form" method="post" action="<?= url('admin-mot-de-passe') ?>" data-validate>
                <?= csrfField() ?>
                <div class="admin-field admin-field--full<?= $err($pwdErrors, 'mot_de_passe_actuel') ?>">
                  <label for="mot_de_passe_actuel">Mot de passe actuel <span class="req">*</span></label>
                  <input class="admin-input" type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" autocomplete="current-password" data-rules="required"<?= $msg('mot_de_passe_actuel') ?>>
                  <p class="admin-field__error"><?= $errText($pwdErrors, 'mot_de_passe_actuel') ?></p>
                </div>
                <div class="admin-field admin-field--full<?= $err($pwdErrors, 'nouveau_mot_de_passe') ?>">
                  <label for="nouveau_mot_de_passe">Nouveau mot de passe <span class="req">*</span></label>
                  <input class="admin-input" type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" autocomplete="new-password" data-rules="required|password"<?= $msg('nouveau_mot_de_passe') ?>>
                  <p class="admin-field__hint">Au moins 8 caractères, dont une lettre et un chiffre.</p>
                  <p class="admin-field__error"><?= $errText($pwdErrors, 'nouveau_mot_de_passe') ?></p>
                </div>
                <div class="admin-field admin-field--full<?= $err($pwdErrors, 'confirmation') ?>">
                  <label for="confirmation">Confirmer le nouveau mot de passe <span class="req">*</span></label>
                  <input class="admin-input" type="password" id="confirmation" name="confirmation" autocomplete="new-password" data-rules="required|match:nouveau_mot_de_passe"<?= $msg('confirmation') ?>>
                  <p class="admin-field__error"><?= $errText($pwdErrors, 'confirmation') ?></p>
                </div>
                <div class="admin-form__actions">
                  <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="lock"></span> Changer le mot de passe</button>
                </div>
              </form>
            </div>
          </section>
        </div>
<?php
$content     = ob_get_clean();
$title       = 'Mon profil — WASLA Admin';
$currentPage = 'admin-profil';
require __DIR__ . '/../../layout.php';
