<?php
/**
 * Vue « Refuges à valider » — Back Office (admin) — Module 3 (Louay).
 * Variables reçues : $refuges (RefugeZoneModel[]), $counts (statut => nombre), $statut (filtre courant).
 */
$statutLabels = [
    'en_attente' => ['En attente', 'warn'],
    'ouvert'     => ['Ouvert', 'ok'],
    'complet'    => ['Complet', 'danger'],
    'ferme'      => ['Fermé', 'muted'],
];
$filtres = ['' => 'Tous (' . array_sum($counts) . ')'];
foreach ($statutLabels as $key => [$label]) {
    $filtres[$key] = $label . ' (' . $counts[$key] . ')';
}
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="home"></span>
            <div>
              <h1>Refuge zones</h1>
              <p>Validez les lieux d'accueil proposés par les entreprises avant leur publication sur la carte.</p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--outline" href="<?= url('refuges') ?>"><span data-icon="external-link"></span> Voir la carte publique</a>
          </div>
        </div>

        <section class="admin-kpis" aria-label="Indicateurs">
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--outage" data-icon="clock"></span>
            <div><p class="admin-kpi__label">À valider</p><p class="admin-kpi__value"><?= $counts['en_attente'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--ok" data-icon="circle-check"></span>
            <div><p class="admin-kpi__label">Ouverts</p><p class="admin-kpi__value"><?= $counts['ouvert'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--water" data-icon="users"></span>
            <div><p class="admin-kpi__label">Complets / fermés</p><p class="admin-kpi__value"><?= $counts['complet'] + $counts['ferme'] ?></p></div>
          </article>
        </section>

        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="layers"></span> Liste des refuges (<?= count($refuges) ?>)</h2>
          </div>
          <div class="admin-card__body">
            <div class="admin-toolbar">
<?php foreach ($filtres as $key => $label): ?>
              <a class="admin-btn <?= $key === $statut ? 'admin-btn--primary' : 'admin-btn--outline' ?>" href="<?= url('admin-refuges', $key === '' ? [] : ['statut' => $key]) ?>"<?= $key === $statut ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
            </div>
          </div>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr><th>ID</th><th>Refuge</th><th>Entreprise</th><th>Gouvernorat</th><th>Places</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr>
              </thead>
              <tbody>
<?php if ($refuges === []): ?>
                <tr><td colspan="8" class="admin-empty">Aucun refuge<?= $statut !== '' ? ' avec ce statut' : '' ?>.</td></tr>
<?php endif; ?>
<?php foreach ($refuges as $r): [$label, $badge] = $statutLabels[$r->getStatut()] ?? [$r->getStatut(), 'muted']; ?>
                <tr>
                  <td class="admin-table__id">#<?= $r->getIdRefuge() ?></td>
                  <td><span class="admin-table__main"><?= e($r->getNom()) ?></span><br><small><?= e($r->getAdresse()) ?></small></td>
                  <td><?= e($r->getNomEntreprise() ?? '—') ?></td>
                  <td><?= e($r->getNomZone()) ?></td>
                  <td><?= $r->getPlacesDisponibles() ?> / <?= $r->getCapacite() ?></td>
                  <td><span class="admin-badge admin-badge--<?= $badge ?>"><?= e($label) ?></span></td>
                  <td><?= e(dateFr($r->getDateCreation())) ?></td>
                  <td>
                    <div class="admin-actions">
<?php if ($r->getStatut() === 'en_attente'): ?>
                      <form method="post" action="<?= url('admin-refuge-action') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $r->getIdRefuge() ?>">
                        <input type="hidden" name="op" value="valider">
                        <button class="admin-action" type="submit" title="Valider" aria-label="Valider <?= e($r->getNom()) ?>"><span data-icon="check"></span></button>
                      </form>
<?php else: ?>
                      <form method="post" action="<?= url('admin-refuge-action') ?>" data-confirm="Retirer ce refuge de la carte publique ?">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $r->getIdRefuge() ?>">
                        <input type="hidden" name="op" value="retirer">
                        <button class="admin-action" type="submit" title="Retirer de la carte" aria-label="Retirer <?= e($r->getNom()) ?> de la carte"><span data-icon="eye-off"></span></button>
                      </form>
<?php endif; ?>
                      <form method="post" action="<?= url('admin-refuge-action') ?>" data-confirm="Supprimer ce refuge ? Ses réservations seront aussi supprimées.">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $r->getIdRefuge() ?>">
                        <input type="hidden" name="op" value="supprimer">
                        <button class="admin-action admin-action--danger" type="submit" title="Supprimer" aria-label="Supprimer <?= e($r->getNom()) ?>"><span data-icon="x"></span></button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Refuge zones — WASLA Admin';
$currentPage = 'admin-refuges';
require __DIR__ . '/../../layout.php';
