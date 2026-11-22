<?php
/**
 * Vue : liste des comptes citoyens — Back Office (super admin) — Module 2 (Hiba).
 * Variables reçues : $citoyens (UserModel[]), $summary (CitoyenController::summary()),
 *                    $nbFactures (id => nombre), $filters, $gouvernorats (ZoneModel[]).
 */
ob_start();
$filtreActif = $filters['q'] !== '' || $filters['statut'] !== '' || $filters['zone'] > 0;
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/citoyens-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head bill-admin-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Comptes citoyens</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="users"></span>
              <div>
                <h1>Comptes citoyens</h1>
                <p>Consulter et gérer les comptes des citoyens</p>
              </div>
            </div>
          </div>
        </div>

        <section class="admin-kpis bill-admin-kpis" aria-label="Synthèse des comptes citoyens">
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon" data-icon="users"></span>
            <div><p class="admin-kpi__label">Citoyens</p><p class="admin-kpi__value"><?= $summary['total'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--ok" data-icon="circle-check"></span>
            <div><p class="admin-kpi__label">Actifs</p><p class="admin-kpi__value"><?= $summary['actifs'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--outage" data-icon="lock"></span>
            <div><p class="admin-kpi__label">Suspendus</p><p class="admin-kpi__value"><?= $summary['suspendus'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--water" data-icon="file-text"></span>
            <div><p class="admin-kpi__label">Avec des factures</p><p class="admin-kpi__value"><?= $summary['avecFactures'] ?></p></div>
          </article>
        </section>

        <section class="admin-card">
          <!-- Recherche / filtres / tri côté serveur (formulaire GET) -->
          <form class="admin-toolbar bill-admin-toolbar" method="get" action="index.php" role="search">
            <input type="hidden" name="action" value="admin-citoyens">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom, e-mail ou téléphone" aria-label="Rechercher un citoyen">
            </div>
            <select class="admin-select" name="zone" aria-label="Filtrer par gouvernorat">
              <option value="">Tous les gouvernorats</option>
<?php foreach ($gouvernorats as $zone): ?>
              <option value="<?= $zone->getIdZone() ?>"<?= $filters['zone'] === $zone->getIdZone() ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
            </select>
            <select class="admin-select" name="statut" aria-label="Filtrer par statut">
              <option value="">Tous les statuts</option>
<?php foreach (['actif', 'suspendu'] as $s): ?>
              <option value="<?= $s ?>"<?= $filters['statut'] === $s ? ' selected' : '' ?>><?= e(CitoyenController::statutLabel($s)) ?></option>
<?php endforeach; ?>
            </select>
            <select class="admin-select" name="tri" aria-label="Trier">
<?php foreach (CitoyenController::TRIS as $cle => [$libelle]): ?>
              <option value="<?= $cle ?>"<?= $filters['tri'] === $cle ? ' selected' : '' ?>><?= e($libelle) ?></option>
<?php endforeach; ?>
            </select>
            <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="filter"></span> Filtrer</button>
<?php if ($filtreActif): ?>
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-citoyens') ?>">Réinitialiser</a>
<?php endif; ?>
          </form>

<?php if ($citoyens === []): ?>
          <p class="admin-empty"><?= $filtreActif ? 'Aucun citoyen ne correspond à ces critères.' : 'Aucun compte citoyen.' ?></p>
<?php else: ?>
          <div class="admin-table-wrap">
            <table class="admin-table bill-admin-table citizen-table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Citoyen</th>
                  <th scope="col">Téléphone</th>
                  <th scope="col">Gouvernorat</th>
                  <th scope="col">Factures</th>
                  <th scope="col">Inscription</th>
                  <th scope="col">Statut</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($citoyens as $c):
    $id = $c->getIdUtilisateur();
    $nom = $c->getPrenom() . ' ' . $c->getNom();
?>
                <tr>
                  <td class="admin-table__id" data-label="N°"><?= $id ?></td>
                  <td class="bill-client" data-label="Citoyen"><strong><?= e($nom) ?></strong><small><?= e($c->getEmail()) ?></small></td>
                  <td data-label="Téléphone"><?= e($c->getTelephone() ?: '—') ?></td>
                  <td data-label="Gouvernorat"><?= e($c->getNomZone() ?? '—') ?></td>
                  <td data-label="Factures"><?= $nbFactures[$id] ?? 0 ?></td>
                  <td data-label="Inscription"><?= e(dateFr($c->getDateInscription())) ?></td>
                  <td class="bill-nowrap" data-label="Statut"><span class="admin-badge <?= $c->getStatutCompte() === 'actif' ? 'admin-badge--ok' : 'admin-badge--danger' ?>"><?= e(CitoyenController::statutLabel($c->getStatutCompte())) ?></span></td>
                  <td class="bill-admin-actions"><div class="admin-actions">
                    <a class="admin-action" href="<?= url('admin-citoyen-voir', ['id' => $id]) ?>" aria-label="Voir le compte de <?= e($nom) ?>"><span data-icon="eye"></span></a>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="admin-table-count"><?= count($citoyens) ?> citoyen<?= count($citoyens) > 1 ? 's' : '' ?><?= $filtreActif ? ' (liste filtrée)' : '' ?></p>
<?php endif; ?>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Comptes citoyens — WASLA Admin';
$currentPage = 'admin-citoyens';
$pageScripts = ['js/factures-crud.js'];   // icônes du module
require __DIR__ . '/../../layout.php';
