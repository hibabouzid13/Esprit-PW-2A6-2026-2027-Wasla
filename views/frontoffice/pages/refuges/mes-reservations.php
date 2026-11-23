<?php
/**
 * Vue « Mes réservations » — Front Office — Module 3 (Louay).
 * Variable reçue : $reservations (ReservationRefugeModel[]).
 * Annulation : formulaire POST avec data-confirm + jeton CSRF (jamais un simple lien).
 */
$statutLabels = [
    'en_attente' => ['En attente', 'watch'],
    'confirmee'  => ['Confirmée', 'normal'],
    'annulee'    => ['Annulée', 'nodata'],
    'terminee'   => ['Terminée', 'nodata'],
];
$besoinsLabels = ['pmr' => 'Fauteuil roulant', 'medical' => 'Appareil médical', 'bebe' => 'Nourrisson', 'senior' => 'Personne âgée'];
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
          <span aria-current="page">Mes réservations</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Zones refuge</p>
            <h1>Mes réservations</h1>
            <p>Suivez vos demandes de place dans les lieux d'accueil et annulez-les si vous n'en avez plus besoin.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--primary" href="<?= url('refuges') ?>"><span data-icon="map-pin"></span> Trouver un refuge</a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <article class="card">
          <div class="card__header">
            <h2 class="card__title">Historique (<?= count($reservations) ?>)</h2>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Lieu</th><th>Date</th><th>Personnes</th><th>Besoins</th><th>Statut</th><th><span class="sr-only">Actions</span></th></tr>
              </thead>
              <tbody>
<?php if ($reservations === []): ?>
                <tr class="data-table__empty"><td colspan="6">Aucune réservation pour le moment. <a href="<?= url('refuges') ?>">Trouver un lieu d'accueil</a></td></tr>
<?php endif; ?>
<?php foreach ($reservations as $r): [$label, $badge] = $statutLabels[$r->getStatut()] ?? [$r->getStatut(), 'nodata']; ?>
                <tr>
                  <td><strong><?= e($r->getNomRefuge()) ?></strong><br><small><?= e($r->getAdresseRefuge()) ?>, <?= e($r->getNomZone()) ?></small></td>
                  <td><?= e(dateFr($r->getDateReservation(), true)) ?></td>
                  <td><?= $r->getNombrePersonnes() ?></td>
                  <td><?= e(implode(', ', array_map(fn ($b) => $besoinsLabels[$b] ?? $b, $r->getBesoins())) ?: '—') ?></td>
                  <td><span class="status-badge status-badge--<?= $badge ?>"><?= e($label) ?></span></td>
                  <td>
<?php if ($r->isActive()): ?>
                    <form method="post" action="<?= url('reservation-annuler') ?>" data-confirm="Annuler cette réservation ? Les places seront libérées.">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $r->getIdReservation() ?>">
                      <button class="btn btn--ghost btn--sm" type="submit">Annuler</button>
                    </form>
<?php endif; ?>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mes réservations — WASLA';
$description = 'Vos réservations dans les zones refuge WASLA.';
$currentPage = 'refuges';
$pageCss     = ['pages', 'services'];
require __DIR__ . '/../../layout.php';
