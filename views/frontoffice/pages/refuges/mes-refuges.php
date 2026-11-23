<?php
/**
 * Vue « Mes refuges » — Front Office — espace ENTREPRISE — Module 3 (Louay).
 * Variables reçues : $user (UserModel, rôle entreprise), $refuges (RefugeZoneModel[]),
 *                    $reservations (ReservationRefugeModel[] reçues sur ces refuges).
 */
$refugeStatuts = [
    'en_attente' => ['En attente de validation', 'watch'],
    'ouvert'     => ['Ouvert', 'normal'],
    'complet'    => ['Complet', 'outage'],
    'ferme'      => ['Fermé', 'nodata'],
];
$reservationStatuts = [
    'en_attente' => ['En attente', 'watch'],
    'confirmee'  => ['Confirmée', 'normal'],
    'annulee'    => ['Annulée', 'nodata'],
    'terminee'   => ['Terminée', 'nodata'],
];
$besoinsLabels = ['pmr' => 'Fauteuil roulant', 'medical' => 'Appareil médical', 'bebe' => 'Nourrisson', 'senior' => 'Personne âgée'];
$serviceLabels = [
    'climatisation' => 'Climatisation', 'eau_potable' => 'Eau potable', 'recharge' => 'Recharge',
    'wifi' => 'Wi-Fi', 'sanitaires' => 'Sanitaires', 'espace_enfants' => 'Espace enfants',
];
$verifie = $user->isCompteVerifie();
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Mes refuges</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Espace entreprise · <?= e($user->getNomOrganisation() ?? '') ?></p>
            <h1>Mes refuge zones</h1>
            <p>Proposez vos locaux comme lieu d'accueil pendant les coupures et gérez les réservations des citoyens.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('profil-entreprise') ?>"><span data-icon="settings"></span> Mon profil</a>
            <a class="btn btn--outline" href="<?= url('refuges') ?>"><span data-icon="map"></span> Voir la carte</a>
<?php if ($verifie): ?>
            <a class="btn btn--primary" href="<?= url('refuge-ajouter') ?>"><span data-icon="plus"></span> Ajouter un refuge</a>
<?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
<?php if (!$verifie): ?>
        <p class="svc-notice"><span data-icon="clock"></span><span><strong>Compte en attente de validation.</strong> L'administrateur WASLA vérifie votre matricule fiscal ; vous pourrez publier des refuges dès que votre compte sera validé. En attendant, vos refuges sont masqués de la carte, mais vous pouvez toujours traiter les réservations déjà reçues.</span></p>
<?php endif; ?>

        <article class="card">
          <div class="card__header">
            <h2 class="card__title">Mes refuges (<?= count($refuges) ?>)</h2>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Refuge</th><th>Gouvernorat</th><th>Services</th><th>Places</th><th>Statut</th><th><span class="sr-only">Actions</span></th></tr>
              </thead>
              <tbody>
<?php if ($refuges === []): ?>
                <tr class="data-table__empty"><td colspan="6">Aucun refuge pour le moment.<?php if ($verifie): ?> <a href="<?= url('refuge-ajouter') ?>">Ajouter votre premier refuge</a><?php endif; ?></td></tr>
<?php endif; ?>
<?php foreach ($refuges as $r): [$label, $badge] = $refugeStatuts[$r->getStatut()] ?? [$r->getStatut(), 'nodata']; ?>
                <tr>
                  <td><strong><?= e($r->getNom()) ?></strong><br><small><?= e($r->getAdresse()) ?><?= $r->getHoraires() ? ' · ' . e($r->getHoraires()) : '' ?></small></td>
                  <td><?= e($r->getNomZone()) ?></td>
                  <td><small><?= e(implode(', ', array_map(fn ($s) => $serviceLabels[$s] ?? $s, $r->getServices()))) ?><?= $r->isAccessiblePmr() ? ', accès PMR' : '' ?></small></td>
                  <td><?= $r->getPlacesDisponibles() ?> / <?= $r->getCapacite() ?></td>
                  <td><span class="status-badge status-badge--<?= $badge ?>"><?= e($label) ?></span></td>
                  <td>
                    <div class="svc-actions" style="flex-wrap:nowrap;">
<?php if ($verifie): ?>
                      <a class="btn btn--outline btn--sm" href="<?= url('refuge-modifier', ['id' => $r->getIdRefuge()]) ?>">Modifier</a>
<?php endif; ?>
                      <form method="post" action="<?= url('refuge-supprimer') ?>" data-confirm="Supprimer ce refuge ? S'il a déjà des réservations, il sera fermé au lieu d'être supprimé.">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $r->getIdRefuge() ?>">
                        <button class="btn btn--ghost btn--sm" type="submit">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>

        <article class="card" id="reservations" style="margin-top:20px;">
          <div class="card__header">
            <h2 class="card__title">Réservations reçues (<?= count($reservations) ?>)</h2>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Refuge</th><th>Contact</th><th>Personnes</th><th>Besoins</th><th>Date</th><th>Statut</th><th><span class="sr-only">Actions</span></th></tr>
              </thead>
              <tbody>
<?php if ($reservations === []): ?>
                <tr class="data-table__empty"><td colspan="7">Aucune réservation reçue.</td></tr>
<?php endif; ?>
<?php foreach ($reservations as $x): [$label, $badge] = $reservationStatuts[$x->getStatut()] ?? [$x->getStatut(), 'nodata']; ?>
                <tr>
                  <td><strong><?= e($x->getNomRefuge()) ?></strong></td>
                  <td><?= e($x->getNomContact()) ?><br><small><?= e($x->getTelephone()) ?></small></td>
                  <td><?= $x->getNombrePersonnes() ?></td>
                  <td><?= e(implode(', ', array_map(fn ($b) => $besoinsLabels[$b] ?? $b, $x->getBesoins())) ?: '—') ?></td>
                  <td><?= e(dateFr($x->getDateReservation(), true)) ?></td>
                  <td><span class="status-badge status-badge--<?= $badge ?>"><?= e($label) ?></span></td>
                  <td>
<?php if ($x->isActive()): ?>
                    <div class="svc-actions" style="flex-wrap:nowrap;">
<?php if ($x->getStatut() === 'en_attente'): ?>
                      <form method="post" action="<?= url('reservation-statut') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $x->getIdReservation() ?>">
                        <input type="hidden" name="statut" value="confirmee">
                        <button class="btn btn--primary btn--sm" type="submit">Confirmer</button>
                      </form>
                      <form method="post" action="<?= url('reservation-statut') ?>" data-confirm="Refuser cette réservation ? Les places seront libérées.">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $x->getIdReservation() ?>">
                        <input type="hidden" name="statut" value="annulee">
                        <button class="btn btn--ghost btn--sm" type="submit">Refuser</button>
                      </form>
<?php else: ?>
                      <form method="post" action="<?= url('reservation-statut') ?>" data-confirm="Marquer cette réservation comme terminée ? Les places seront libérées.">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $x->getIdReservation() ?>">
                        <input type="hidden" name="statut" value="terminee">
                        <button class="btn btn--outline btn--sm" type="submit">Terminer</button>
                      </form>
<?php endif; ?>
                    </div>
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
$title       = 'Mes refuges — WASLA';
$description = 'Gérez vos refuge zones et les réservations reçues.';
$currentPage = 'refuges';
$pageCss     = ['pages', 'services'];
require __DIR__ . '/../../layout.php';
