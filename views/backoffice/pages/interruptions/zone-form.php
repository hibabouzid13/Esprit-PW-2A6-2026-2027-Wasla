<?php
/**
 * Vue : Back Office : ajout / modification d'une zone géographique — Module 1 (Houssem). Super admin uniquement.
 * Variables reçues :
 *   $zone          ?ZoneModel            null = ajout, sinon modification
 *   $gouvernorats  ZoneModel[]           parents possibles d'une délégation
 *   $errors        array<string,string>  erreurs du contrôle serveur (ZoneAdminController::check)
 *   $old           array                 saisie précédente (réaffichée après une erreur)
 * Contrôle de saisie : data-validate + data-rules (validation.js), mêmes règles en PHP. Aucune validation HTML5.
 */
$isEdit = $zone !== null;

/** Valeur à afficher : saisie précédente, sinon valeur en base. */
$val = static function (string $field) use ($old, $zone): string {
    if (array_key_exists($field, $old)) {
        return is_string($old[$field]) ? trim($old[$field]) : '';
    }
    if ($zone === null) {
        return $field === 'type_zone' ? 'delegation' : '';
    }
    return match ($field) {
        'nom_zone'       => $zone->getNomZone(),
        'type_zone'      => $zone->getTypeZone(),
        'id_zone_parent' => $zone->getIdZoneParent() !== null ? (string) $zone->getIdZoneParent() : '',
        default          => '',
    };
};
$err     = static fn (string $f): string => isset($errors[$f]) ? ' has-error' : '';
$errText = static fn (string $f): string => e($errors[$f] ?? '');

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-zones') ?>">Zones géographiques</a><span>/</span><span><?= $isEdit ? 'Modifier' : 'Ajouter' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="map-pin"></span>
              <div>
                <h1><?= $isEdit ? 'Modifier la zone' : 'Ajouter une zone' ?></h1>
                <p>Gouvernorat ou délégation rattachée à un gouvernorat</p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
              <form class="admin-form" method="post" action="<?= url('admin-zone-form', $isEdit ? ['id' => $zone->getIdZone()] : []) ?>" data-validate>
                <?= csrfField() ?>
<?php if ($isEdit): ?>
                <input type="hidden" name="id_zone" value="<?= $zone->getIdZone() ?>">
<?php endif; ?>

                <div class="admin-field admin-field--full<?= $err('nom_zone') ?>">
                  <label for="nom_zone">Nom de la zone <span class="req">*</span></label>
                  <input class="admin-input" type="text" id="nom_zone" name="nom_zone" value="<?= e($val('nom_zone')) ?>"
                         placeholder="Ex. Sfax Nord, La Soukra…" data-rules="required|min:2|max:100"<?= ZoneAdminController::messageAttrs('nom_zone') ?>>
                  <p class="admin-field__hint">2 à 100 caractères.</p>
                  <p class="admin-field__error"><?= $errText('nom_zone') ?></p>
                </div>

                <fieldset class="admin-field admin-field--full<?= $err('type_zone') ?>">
                  <legend>Type <span class="req">*</span></legend>
                  <div class="admin-choices">
                    <label class="admin-choice">
                      <input type="radio" name="type_zone" value="gouvernorat"<?= $val('type_zone') === 'gouvernorat' ? ' checked' : '' ?> data-rules="required|in:gouvernorat,delegation"<?= ZoneAdminController::messageAttrs('type_zone') ?>>
                      <span><span data-icon="landmark"></span> Gouvernorat</span>
                    </label>
                    <label class="admin-choice">
                      <input type="radio" name="type_zone" value="delegation"<?= $val('type_zone') === 'delegation' ? ' checked' : '' ?> data-rules="required|in:gouvernorat,delegation"<?= ZoneAdminController::messageAttrs('type_zone') ?>>
                      <span><span data-icon="map-pin"></span> Délégation</span>
                    </label>
                  </div>
                  <p class="admin-field__error"><?= $errText('type_zone') ?></p>
                </fieldset>

                <div class="admin-field admin-field--full<?= $err('id_zone_parent') ?>">
                  <label for="id_zone_parent">Gouvernorat parent <small>(délégations uniquement)</small></label>
                  <select class="admin-select" id="id_zone_parent" name="id_zone_parent" data-rules="int">
                    <option value="">— Aucun (gouvernorat) —</option>
<?php foreach ($gouvernorats as $g): if ($isEdit && $g->getIdZone() === $zone->getIdZone()) continue; ?>
                    <option value="<?= $g->getIdZone() ?>"<?= $val('id_zone_parent') === (string) $g->getIdZone() ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <p class="admin-field__error"><?= $errText('id_zone_parent') ?></p>
                </div>

                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= url('admin-zones') ?>">Annuler</a>
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
                <div><dt>Gouvernorat</dt><dd>Zone principale (24 en Tunisie), sans parent. Elle apparaît sur la carte des réseaux.</dd></div>
                <div><dt>Délégation</dt><dd>Subdivision d'un gouvernorat : choisissez obligatoirement son gouvernorat parent.</dd></div>
                <div><dt>Suppression</dt><dd>Refusée tant que la zone est utilisée (interruptions, signalements, comptes, refuges…).</dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($isEdit ? 'Modifier' : 'Ajouter') . ' une zone — WASLA Admin';
$currentPage = 'admin-zone-form';
require __DIR__ . '/../../layout.php';
