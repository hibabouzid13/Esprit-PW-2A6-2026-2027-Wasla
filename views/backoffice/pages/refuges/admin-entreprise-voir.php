<?php
/**
 * Vue « Fiche entreprise » — Back Office (admin) — Module 3 (Louay).
 * Variables reçues : $entreprise (UserModel de rôle entreprise), $refuges (RefugeZoneModel[]),
 *                    $reservations (ReservationRefugeModel[] reçues), $donnees (libellé => nombre de lignes liées).
 */
$id = $entreprise->getIdUtilisateur();
$refugeStatuts = [
    'en_attente' => ['À valider', 'warn'],
    'ouvert'     => ['Ouvert', 'ok'],
    'complet'    => ['Complet', 'danger'],
    'ferme'      => ['Fermé', 'info'],
];
$reservationStatuts = ['en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'annulee' => 'Annulée', 'terminee' => 'Terminée'];
$actives = count(array_filter($reservations, fn ($x) => $x->isActive()));

/** Petit formulaire POST d'action, qui revient sur cette fiche. */
$actionForm = static function (string $op, string $icon, string $label, string $confirm = '', bool $danger = false) use ($id): string {
    return '<form method="post" action="' . url('admin-entreprise-action') . '"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . csrfField()
        . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="op" value="' . e($op) . '"><input type="hidden" name="retour" value="fiche">'
        . '<button class="admin-btn ' . ($danger ? 'admin-btn--danger' : 'admin-btn--outline') . '" type="submit"><span data-icon="' . e($icon) . '"></span> ' . e($label) . '</button>'
        . '</form>';
};
$row = static fn (string $label, string $value): string => '<tr><th scope="row">' . e($label) . '</th><td>' . ($value === '' ? '—' : e($value)) . '</td></tr>';
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="building-2"></span>
            <div>
              <h1><?= e($entreprise->getNomOrganisation() ?? 'Entreprise') ?></h1>
              <p>
                <span class="admin-badge admin-badge--<?= $entreprise->isCompteVerifie() ? 'ok' : 'warn' ?>"><?= $entreprise->isCompteVerifie() ? 'Validée' : 'À valider' ?></span>
                <span class="admin-badge admin-badge--<?= $entreprise->getStatutCompte() === 'actif' ? 'info' : 'danger' ?>"><?= $entreprise->getStatutCompte() === 'actif' ? 'Actif' : 'Suspendu' ?></span>
              </p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-entreprises') ?>">Retour à la liste</a>
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-entreprise-form', ['id' => $id]) ?>"><span data-icon="settings"></span> Modifier</a>
          </div>
        </div>

        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="file-text"></span> Informations</h2>
          </div>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <tbody>
                <?= $row('Raison sociale', (string) $entreprise->getNomOrganisation()) ?>
                <?= $row('Matricule fiscal', (string) $entreprise->getMatriculeFiscal()) ?>
                <?= $row("Secteur d'activité", (string) $entreprise->getSecteurActivite()) ?>
                <?= $row('Responsable', $entreprise->getPrenom() . ' ' . $entreprise->getNom()) ?>
                <?= $row('E-mail', $entreprise->getEmail()) ?>
                <?= $row('Téléphone', (string) $entreprise->getTelephone()) ?>
                <?= $row('Gouvernorat', (string) $entreprise->getNomZone()) ?>
                <?= $row('Adresse', (string) $entreprise->getAdresse()) ?>
                <?= $row('Présentation', (string) $entreprise->getDescription()) ?>
                <?= $row('Inscrite le', $entreprise->getDateInscription() ? dateFr($entreprise->getDateInscription(), true) : '') ?>
                <?= $row('Dernière connexion', $entreprise->getDerniereConnexion() ? dateFr($entreprise->getDerniereConnexion(), true) : 'jamais') ?>
              </tbody>
            </table>
          </div>
          <div class="admin-card__body">
            <div class="admin-actions" style="gap:8px;flex-wrap:wrap;">
              <?= $entreprise->isCompteVerifie()
                  ? $actionForm('devalider', 'shield-check', 'Retirer la validation', 'Retirer la validation ? Ses refuges seront masqués de la carte.')
                  : $actionForm('valider', 'check', 'Valider le compte') ?>
              <?= $entreprise->getStatutCompte() === 'actif'
                  ? $actionForm('suspendre', 'lock', 'Suspendre', 'Suspendre ce compte ? Il ne pourra plus se connecter et ses réservations en cours seront annulées.')
                  : $actionForm('reactiver', 'refresh', 'Réactiver') ?>
<?php if ($donnees === []): ?>
              <?= $actionForm('supprimer', 'x', 'Supprimer', 'Supprimer définitivement ce compte entreprise ?', true) ?>
<?php endif; ?>
            </div>
          </div>
        </section>

        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="layers"></span> Données liées</h2>
          </div>
          <div class="admin-card__body">
<?php if ($donnees === []): ?>
            <p>Aucune donnée liée : ce compte peut être supprimé.</p>
<?php else: ?>
            <p>Ce compte ne peut pas être supprimé (ses données seraient effacées dans les autres modules) : suspendez-le si nécessaire.</p>
            <ul>
<?php foreach ($donnees as $label => $n): ?>
              <li><?= e($label) ?> : <strong><?= $n ?></strong></li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
          </div>
        </section>

        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="home"></span> Refuges (<?= count($refuges) ?>) · réservations en cours : <?= $actives ?> / <?= count($reservations) ?></h2>
          </div>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <thead><tr><th>ID</th><th>Refuge</th><th>Gouvernorat</th><th>Places</th><th>Statut</th></tr></thead>
              <tbody>
<?php if ($refuges === []): ?>
                <tr><td colspan="5" class="admin-empty">Aucun refuge.</td></tr>
<?php endif; ?>
<?php foreach ($refuges as $r): [$label, $badge] = $refugeStatuts[$r->getStatut()] ?? [$r->getStatut(), 'info']; ?>
                <tr>
                  <td class="admin-table__id">#<?= $r->getIdRefuge() ?></td>
                  <td><span class="admin-table__main"><?= e($r->getNom()) ?></span><br><small><?= e($r->getAdresse()) ?></small></td>
                  <td><?= e($r->getNomZone() ?? '—') ?></td>
                  <td><?= $r->getPlacesDisponibles() ?> / <?= $r->getCapacite() ?></td>
                  <td><span class="admin-badge admin-badge--<?= $badge ?>"><?= e($label) ?></span></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
<?php if ($reservations !== []): ?>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <thead><tr><th>Refuge</th><th>Contact</th><th>Personnes</th><th>Date</th><th>Statut</th></tr></thead>
              <tbody>
<?php foreach ($reservations as $x): ?>
                <tr>
                  <td><?= e($x->getNomRefuge()) ?></td>
                  <td><?= e($x->getNomContact()) ?></td>
                  <td><?= $x->getNombrePersonnes() ?></td>
                  <td><?= e(dateFr($x->getDateReservation(), true)) ?></td>
                  <td><span class="admin-badge admin-badge--<?= $x->isActive() ? 'ok' : 'info' ?>"><?= e($reservationStatuts[$x->getStatut()] ?? $x->getStatut()) ?></span></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
<?php endif; ?>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Fiche entreprise — WASLA Admin';
$currentPage = 'admin-entreprise-voir';
require __DIR__ . '/../../layout.php';
