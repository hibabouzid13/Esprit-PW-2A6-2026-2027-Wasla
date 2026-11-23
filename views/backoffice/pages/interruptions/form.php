<?php
/**
 * Vue : Back Office : formulaire de publication / modification d'une interruption — Module 1 (Houssem).
 * Variables reçues :
 *   $zones         ZoneModel[]
 *   $interruption  ?InterruptionModel  (null = publication, sinon modification)
 *   $allowedTypes  string[]            ressources que le rôle peut gérer
 *   $errors        array<string,string> erreurs du contrôle serveur (InterruptionController)
 *   $old           array               saisie précédente (réaffichée après une erreur)
 * Contrôle de saisie : validation.js (navigateur) + InterruptionController::check() (serveur).
 */
$isEdit = $interruption !== null;

/** Valeur à afficher : saisie précédente, sinon valeur en base, sinon $default. */
$val = static function (string $field, string $default = '') use ($old, $interruption): string {
    if (array_key_exists($field, $old)) {
        return is_string($old[$field]) ? $old[$field] : '';
    }
    if ($interruption === null) {
        return $default;
    }
    $toInput = static fn (string $d): string => ($t = strtotime($d)) ? date('Y-m-d\TH:i', $t) : '';
    return match ($field) {
        'type_ressource'  => $interruption->getTypeRessource(),
        'id_zone'         => (string) $interruption->getIdZone(),
        'statut'          => $interruption->getStatut(),
        'cause'           => $interruption->getCause(),
        'date_debut'      => $toInput($interruption->getDateDebut()),
        'date_fin_prevue' => $toInput($interruption->getDateFinPrevue()),
        default           => $default,
    };
};
$err      = static fn (string $f): string => isset($errors[$f]) ? ' has-error' : '';
$errText  = static fn (string $f): string => e($errors[$f] ?? '');
$typeInfo = ['electricite' => ['zap', 'Électricité (STEG)'], 'eau' => ['droplet', 'Eau (SONEDE)']];
$statuts  = ['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée'];
$defaultType = count($allowedTypes) === 1 ? $allowedTypes[0] : '';

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-interruptions') ?>">Interruptions</a><span>/</span><span><?= $isEdit ? 'Modifier' : 'Publier' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="zap"></span>
              <div>
                <h1><?= $isEdit ? 'Modifier l\'interruption INT-' . $interruption->getIdInterruption() : 'Publier une interruption' ?></h1>
                <p>Coupure planifiée ou constatée, visible par les citoyens de la zone</p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
              <!-- Les noms des champs reprennent les colonnes de la table « interruption » du dictionnaire -->
              <form class="admin-form" method="post" action="<?= url('admin-interruption-form', $isEdit ? ['id' => $interruption->getIdInterruption()] : []) ?>" data-validate novalidate>
                <?= csrfField() ?>
<?php if ($isEdit): ?>
                <input type="hidden" name="id_interruption" value="<?= $interruption->getIdInterruption() ?>">
<?php endif; ?>
                <fieldset class="admin-field admin-field--full<?= $err('type_ressource') ?>">
                  <legend>Ressource coupée <span class="req">*</span></legend>
                  <div class="admin-choices">
<?php foreach ($allowedTypes as $type): [$icon, $label] = $typeInfo[$type]; ?>
                    <label class="admin-choice">
                      <input type="radio" name="type_ressource" value="<?= e($type) ?>"<?= $val('type_ressource', $defaultType) === $type ? ' checked' : '' ?> data-rules="required"<?= InterruptionController::messageAttrs('type_ressource') ?>>
                      <span><span data-icon="<?= $icon ?>"></span> <?= e($label) ?></span>
                    </label>
<?php endforeach; ?>
                  </div>
                  <p class="admin-field__error"><?= $errText('type_ressource') ?></p>
                </fieldset>

                <div class="admin-field<?= $err('id_zone') ?>">
                  <label for="id_zone">Zone touchée <span class="req">*</span></label>
                  <select class="admin-select" id="id_zone" name="id_zone" data-rules="required"<?= InterruptionController::messageAttrs('id_zone') ?>>
                    <option value="">— Choisir une zone —</option>
<?php foreach ($zones as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= $val('id_zone') === (string) $zone->getIdZone() ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <p class="admin-field__error"><?= $errText('id_zone') ?></p>
                </div>

                <div class="admin-field<?= $err('statut') ?>">
                  <label for="statut">Statut <span class="req">*</span></label>
                  <select class="admin-select" id="statut" name="statut" data-rules="required"<?= InterruptionController::messageAttrs('statut') ?>>
                    <option value="">— Choisir un statut —</option>
<?php foreach ($statuts as $value => $label): ?>
                    <option value="<?= $value ?>"<?= $val('statut') === $value ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                  </select>
                  <p class="admin-field__error"><?= $errText('statut') ?></p>
                </div>

                <div class="admin-field<?= $err('date_debut') ?>">
                  <label for="date_debut">Début <span class="req">*</span></label>
                  <input class="admin-input" type="datetime-local" id="date_debut" name="date_debut" value="<?= e($val('date_debut')) ?>" data-rules="required"<?= InterruptionController::messageAttrs('date_debut') ?>>
                  <p class="admin-field__error"><?= $errText('date_debut') ?></p>
                </div>

                <div class="admin-field<?= $err('date_fin_prevue') ?>">
                  <label for="date_fin_prevue">Fin prévue <span class="req">*</span></label>
                  <input class="admin-input" type="datetime-local" id="date_fin_prevue" name="date_fin_prevue" value="<?= e($val('date_fin_prevue')) ?>" data-rules="required|after:date_debut"<?= InterruptionController::messageAttrs('date_fin_prevue') ?>>
                  <p class="admin-field__error"><?= $errText('date_fin_prevue') ?></p>
                </div>

                <div class="admin-field admin-field--full<?= $err('cause') ?>">
                  <label for="cause">Cause <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="cause" name="cause" value="<?= e($val('cause')) ?>" placeholder="Ex. travaux, panne, surcharge…" data-rules="required|min:4|max:255"<?= InterruptionController::messageAttrs('cause') ?>>
                  <p class="admin-field__hint">4 à 255 caractères.</p>
                  <p class="admin-field__error"><?= $errText('cause') ?></p>
                </div>

                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= url('admin-interruptions') ?>">Annuler</a>
                  <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="save"></span> <?= $isEdit ? 'Enregistrer les modifications' : 'Enregistrer' ?></button>
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
                <div><dt>Qui publie ?</dt><dd>Le sous-admin STEG (électricité) ou SONEDE (eau) ; le super admin peut publier les deux.</dd></div>
                <div><dt>Fin réelle</dt><dd>Renseignée automatiquement lors de la clôture (ou en passant le statut à « Terminée »).</dd></div>
                <div><dt>Alertes</dt><dd>Une interruption peut déclencher une alerte pour les habitants de la zone.</dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($isEdit ? 'Modifier' : 'Publier') . ' une interruption — WASLA Admin';
$currentPage = 'admin-interruption-form';
require __DIR__ . '/../../layout.php';
