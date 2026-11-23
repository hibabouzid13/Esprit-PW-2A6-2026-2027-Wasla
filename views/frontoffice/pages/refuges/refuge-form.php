<?php
/**
 * Vue « Ajouter / modifier un refuge » — Front Office — espace ENTREPRISE — Module 3 (Louay).
 * Variables reçues : $refuge (?RefugeZoneModel, null = ajout), $errors, $old, $gouvernorats (ZoneModel[]).
 * Formulaire POST : data-validate + data-rules, mêmes règles dans RefugeZoneController::validateRefuge().
 */
$isEdit = $refuge !== null;
$serviceLabels = [
    'climatisation'  => 'Climatisation',
    'eau_potable'    => 'Eau potable',
    'recharge'       => 'Recharge électrique',
    'wifi'           => 'Wi-Fi',
    'sanitaires'     => 'Sanitaires',
    'espace_enfants' => 'Espace enfants',
];
/** Places déjà réservées : la capacité ne peut pas descendre en dessous (vérifié aussi dans le navigateur). */
$occupees = $isEdit ? $refuge->getPlacesOccupees() : 0;
$action = $isEdit ? url('refuge-modifier', ['id' => $refuge->getIdRefuge()]) : url('refuge-ajouter');
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mes-refuges') ?>">Mes refuges</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page"><?= $isEdit ? 'Modifier' : 'Ajouter' ?></span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Espace entreprise</p>
            <h1><?= $isEdit ? 'Modifier « ' . e($refuge->getNom()) . ' »' : 'Ajouter un refuge' ?></h1>
            <p><?= $isEdit
                ? 'Les places déjà réservées restent occupées : les places disponibles sont recalculées à partir de la nouvelle capacité.'
                : 'Votre refuge sera visible sur la carte après validation par l\'administrateur WASLA.' ?></p>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container" style="max-width:860px;">
        <article class="card">
          <div class="card__body">
<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert" style="margin-top:0;"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
            <form class="form-grid" method="post" action="<?= $action ?>" data-validate style="margin-top:14px;">
              <?= csrfField() ?>

              <div class="field">
                <label for="rf-nom">Nom du lieu *</label>
                <input class="input<?= invalidClass($errors, 'nom') ?>" id="rf-nom" name="nom" type="text" placeholder="ex. Espace climatisé Le Belvédère"
                       value="<?= e($old['nom']) ?>" data-rules="required|min:3|max:100"<?= RefugeZoneController::messageAttrs('nom') ?>>
                <?= fieldError($errors, 'nom') ?>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="rf-adresse">Adresse *</label>
                  <input class="input<?= invalidClass($errors, 'adresse') ?>" id="rf-adresse" name="adresse" type="text" placeholder="ex. Avenue Habib Bourguiba, Tunis"
                         value="<?= e($old['adresse']) ?>" data-rules="required|min:5|max:255"<?= RefugeZoneController::messageAttrs('adresse') ?>>
                  <?= fieldError($errors, 'adresse') ?>
                </div>
                <div class="field">
                  <label for="rf-zone">Gouvernorat *</label>
                  <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="rf-zone" name="id_zone"
                          data-rules="required|int"<?= RefugeZoneController::messageAttrs('id_zone') ?>>
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
                  <label for="rf-lat">Latitude <span class="field__optional">(facultatif)</span></label>
                  <input class="input<?= invalidClass($errors, 'latitude') ?>" id="rf-lat" name="latitude" type="text" inputmode="decimal" placeholder="ex. 36.800"
                         value="<?= e($old['latitude']) ?>" data-rules="decimal"<?= RefugeZoneController::messageAttrs('latitude') ?>>
                  <?= fieldError($errors, 'latitude') ?>
                </div>
                <div class="field">
                  <label for="rf-lon">Longitude <span class="field__optional">(facultatif)</span></label>
                  <input class="input<?= invalidClass($errors, 'longitude') ?>" id="rf-lon" name="longitude" type="text" inputmode="decimal" placeholder="ex. 10.180"
                         value="<?= e($old['longitude']) ?>" data-rules="decimal"<?= RefugeZoneController::messageAttrs('longitude') ?>>
                  <?= fieldError($errors, 'longitude') ?>
                </div>
              </div>
              <p class="field__hint" style="margin-top:-8px;">Sans coordonnées, le lieu est placé approximativement dans son gouvernorat sur la carte.</p>

              <div class="form-row">
                <div class="field">
                  <label for="rf-capacite">Capacité d'accueil * <span class="field__optional">(personnes)</span></label>
                  <input class="input<?= invalidClass($errors, 'capacite') ?>" id="rf-capacite" name="capacite" type="text" inputmode="numeric" placeholder="ex. 30"
                         value="<?= e($old['capacite']) ?>" data-rules="required|int|positive"
                         data-extra-rules="max-value:<?= RefugeZoneController::CAPACITE_MAX ?><?= $occupees > 0 ? '|min-value:' . $occupees : '' ?>"<?= RefugeZoneController::messageAttrs('capacite', ['{max}' => RefugeZoneController::CAPACITE_MAX, '{n}' => $occupees]) ?>>
                  <?= fieldError($errors, 'capacite') ?>
                </div>
                <div class="field">
                  <label for="rf-horaires">Horaires <span class="field__optional">(facultatif)</span></label>
                  <input class="input<?= invalidClass($errors, 'horaires') ?>" id="rf-horaires" name="horaires" type="text" placeholder="ex. 08:00 – 22:00"
                         value="<?= e($old['horaires']) ?>" data-rules="max:100">
                  <?= fieldError($errors, 'horaires') ?>
                </div>
              </div>

              <div class="field">
                <span style="font-weight:600;">Services proposés *</span>
                <div class="svc-check-grid" data-check-group="services[]" data-error-name="services"<?= RefugeZoneController::messageAttrs('services') ?>>
<?php foreach ($serviceLabels as $value => $label): ?>
                  <label class="checkbox"><input type="checkbox" name="services[]" value="<?= e($value) ?>"<?= in_array($value, (array) $old['services'], true) ? ' checked' : '' ?>> <?= e($label) ?></label>
<?php endforeach; ?>
                </div>
                <?= fieldError($errors, 'services') ?>
              </div>

              <div class="field">
                <label class="checkbox"><input type="checkbox" name="accessible_pmr" value="1"<?= $old['accessible_pmr'] !== '' ? ' checked' : '' ?>> Lieu accessible aux personnes à mobilité réduite</label>
                <?= fieldError($errors, 'accessible_pmr') ?>
              </div>

<?php if ($isEdit && $refuge->getStatut() !== 'en_attente'): ?>
              <div class="field">
                <label for="rf-statut">Disponibilité *</label>
                <select class="select<?= invalidClass($errors, 'statut') ?>" id="rf-statut" name="statut" data-rules="required|in:ouvert,ferme"<?= RefugeZoneController::messageAttrs('statut') ?>>
                  <option value="ouvert"<?= ($old['statut'] ?? '') === 'ouvert' ? ' selected' : '' ?>>Ouvert (passe automatiquement à « complet » sans place libre)</option>
                  <option value="ferme"<?= ($old['statut'] ?? '') === 'ferme' ? ' selected' : '' ?>>Fermé temporairement</option>
                </select>
                <?= fieldError($errors, 'statut') ?>
              </div>
<?php elseif ($isEdit): ?>
              <p class="svc-notice"><span data-icon="clock"></span><span>Ce refuge attend la validation de l'administrateur.</span></p>
<?php endif; ?>

              <div class="svc-actions" style="justify-content:flex-end;">
                <a class="btn btn--outline btn--sm" href="<?= url('mes-refuges') ?>">Annuler</a>
                <button class="btn btn--primary btn--sm" type="submit" data-loading="Enregistrement…"><?= $isEdit ? 'Enregistrer' : 'Ajouter le refuge' ?></button>
              </div>
            </form>
          </div>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = ($isEdit ? 'Modifier un refuge' : 'Ajouter un refuge') . ' — WASLA';
$description = 'Proposez un lieu d\'accueil pendant les coupures.';
$currentPage = 'refuges';
$pageCss     = ['pages', 'services'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/services/refuge-forms.js'];
require __DIR__ . '/../../layout.php';
