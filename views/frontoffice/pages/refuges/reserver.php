<?php
/**
 * Vue « Réserver une place » — Front Office — Module 3 (Louay).
 * Variables reçues : $refuge (RefugeZoneModel), $errors (champ => message), $old (valeurs saisies).
 * Formulaire POST : data-validate + data-rules (validation.js), mêmes règles revérifiées
 * par ReservationRefugeController::reserve(), jeton CSRF.
 */
$besoinsLabels = [
    'pmr'     => 'Accès fauteuil roulant',
    'medical' => 'Appareil médical à brancher',
    'bebe'    => 'Nourrisson',
    'senior'  => 'Personne âgée',
];
$maxPersonnes = min(ReservationRefugeController::PERSONNES_MAX, $refuge->getPlacesDisponibles());
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('refuges') ?>">Zones refuge</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Réserver</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Zones refuge · Réservation</p>
            <h1>Réserver une place</h1>
            <p><?= e($refuge->getNom()) ?> — <?= e($refuge->getAdresse()) ?>, <?= e($refuge->getNomZone()) ?></p>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container" style="max-width:760px;">
        <article class="card">
          <div class="card__header">
            <div>
              <h2 class="card__title"><?= e($refuge->getNom()) ?></h2>
              <p class="svc-card-sub"><?= e($refuge->getNomEntreprise() ?? '') ?> · Horaires : <?= e($refuge->getHoraires() ?? 'non précisés') ?></p>
            </div>
            <span class="status-badge status-badge--<?= $refuge->getStatut() === 'ouvert' ? 'normal' : 'watch' ?>">
              <?= $refuge->getPlacesDisponibles() ?> place<?= $refuge->getPlacesDisponibles() > 1 ? 's' : '' ?> disponible<?= $refuge->getPlacesDisponibles() > 1 ? 's' : '' ?>
            </span>
          </div>
          <div class="card__body">
<?php if ($refuge->getStatut() !== 'ouvert'): ?>
            <div class="form-alert form-alert--error" role="alert">Ce lieu n'accepte pas de réservation pour le moment (<?= $refuge->getStatut() === 'complet' ? 'complet' : 'fermé' ?>).</div>
<?php else: ?>
<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert" style="margin-top:0;"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
            <form class="form-grid" method="post" action="<?= url('refuge-reserver', ['id' => $refuge->getIdRefuge()]) ?>" data-validate style="margin-top:14px;">
              <?= csrfField() ?>
              <div class="form-row">
                <div class="field">
                  <label for="res-nom">Nom du contact *</label>
                  <input class="input<?= invalidClass($errors, 'nom_contact') ?>" id="res-nom" name="nom_contact" type="text" autocomplete="name"
                         value="<?= e($old['nom_contact']) ?>" data-rules="required|min:3|max:100"<?= ReservationRefugeController::messageAttrs('nom_contact') ?>>
                  <?= fieldError($errors, 'nom_contact') ?>
                </div>
                <div class="field">
                  <label for="res-personnes">Nombre de personnes * <span class="field__optional">(1 à <?= $maxPersonnes ?>)</span></label>
                  <input class="input<?= invalidClass($errors, 'nombre_personnes') ?>" id="res-personnes" name="nombre_personnes" type="text" inputmode="numeric"
                         value="<?= e($old['nombre_personnes']) ?>" data-rules="required|int|positive"
                         data-extra-rules="max-value:<?= $maxPersonnes ?>"<?= ReservationRefugeController::messageAttrs('nombre_personnes', ['{max}' => $maxPersonnes]) ?>>
                  <?= fieldError($errors, 'nombre_personnes') ?>
                </div>
              </div>
              <div class="field">
                <label for="res-tel">Téléphone *</label>
                <input class="input<?= invalidClass($errors, 'telephone') ?>" id="res-tel" name="telephone" type="text" inputmode="tel" autocomplete="tel"
                       placeholder="22 123 456" value="<?= e($old['telephone']) ?>" data-rules="required|phone"<?= ReservationRefugeController::messageAttrs('telephone') ?>>
                <?= fieldError($errors, 'telephone') ?>
              </div>
              <div class="field">
                <span class="field__label" style="font-weight:600;">Besoins particuliers <span class="field__optional">(facultatif)</span></span>
                <div class="svc-check-grid">
<?php foreach ($besoinsLabels as $value => $label): ?>
                  <label class="checkbox"><input type="checkbox" name="besoins[]" value="<?= e($value) ?>"<?= in_array($value, (array) $old['besoins'], true) ? ' checked' : '' ?>> <?= e($label) ?></label>
<?php endforeach; ?>
                </div>
                <?= fieldError($errors, 'besoins') ?>
              </div>
              <p class="field__hint">L'établissement reçoit votre demande et la confirme. Vous pouvez l'annuler à tout moment depuis « Mes réservations ».</p>
              <div class="svc-actions" style="justify-content:flex-end;">
                <a class="btn btn--outline btn--sm" href="<?= url('refuges') ?>">Annuler</a>
                <button class="btn btn--primary btn--sm" type="submit" data-loading="Réservation…">Réserver</button>
              </div>
            </form>
<?php endif; ?>
          </div>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Réserver une place — WASLA';
$description = 'Réservez une place dans une zone refuge WASLA.';
$currentPage = 'refuges';
$pageCss     = ['pages', 'services'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/services/refuge-forms.js'];
require __DIR__ . '/../../layout.php';
