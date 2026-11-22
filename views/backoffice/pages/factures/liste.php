<?php
/**
 * Vue : liste des factures — Back Office — Module 2 (Hiba).
 * Variables reçues : $user (UserModel), $scope (périmètre du rôle), $factures (FactureModel[]),
 *                    $summary (FactureController::summary()), $filters (FactureController::cleanFilters()).
 * Admin : toutes les factures ; STEG : électricité ; SONEDE : eau.
 */
ob_start();
$typeImpose = $scope['type'] ?? '';
$filtreActif = $filters['q'] !== '' || $filters['type'] !== '' || $filters['statut'] !== '';
// Logos STEG / SONEDE (images du module, recadrées par viewBox sur leur contenu pour être centrées)
$logos = [
    'electricite' => '<span class="bill-admin-logo bill-admin-logo--steg"><svg viewBox="25.5 13.5 186 355" aria-hidden="true" focusable="false"><image href="' . e(asset('img/factures/steg.png')) . '" width="267" height="378"/></svg></span>',
    'eau'         => '<span class="bill-admin-logo bill-admin-logo--sonede"><svg viewBox="49.5 17.5 329 329" aria-hidden="true" focusable="false"><image href="' . e(asset('img/factures/sonede.png')) . '" width="387" height="430"/></svg></span>',
];
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Factures &amp; consommation</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="file-text"></span>
              <div>
                <h1>Factures</h1>
                <p><?= $typeImpose !== ''
                    ? 'Factures ' . e(FactureController::typeLabel($typeImpose)) . ' enregistrées par les citoyens (périmètre de votre rôle)'
                    : 'Factures STEG et SONEDE enregistrées par les citoyens' ?></p>
              </div>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-facture-form') ?>"><span data-icon="plus"></span> Ajouter une facture</a>
          </div>
        </div>

        <section class="admin-kpis bill-admin-kpis" aria-label="Synthèse des factures">
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon" data-icon="file-text"></span>
            <div><p class="admin-kpi__label">Factures</p><p class="admin-kpi__value"><?= $summary['total'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--outage" data-icon="clock"></span>
            <div><p class="admin-kpi__label">À payer</p><p class="admin-kpi__value"><?= $summary['aPayer'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--ok" data-icon="wallet"></span>
            <div><p class="admin-kpi__label">Montant restant</p><p class="admin-kpi__value"><?= e(FactureController::formatMontant($summary['montantAPayer'])) ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--water" data-icon="banknote"></span>
            <div><p class="admin-kpi__label">Montant total facturé</p><p class="admin-kpi__value"><?= e(FactureController::formatMontant($summary['montantTotal'])) ?></p></div>
          </article>
        </section>

        <section class="admin-card">
          <!-- Recherche / filtres / tri côté serveur (formulaire GET) -->
          <form class="admin-toolbar bill-admin-toolbar" method="get" action="index.php" role="search">
            <input type="hidden" name="action" value="admin-factures">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="N° d'abonnement ou de talon, citoyen, e-mail" aria-label="Rechercher une facture">
            </div>
<?php if ($typeImpose === ''): ?>
            <select class="admin-select" name="type" aria-label="Filtrer par fournisseur">
              <option value="">Tous les fournisseurs</option>
<?php foreach (FactureModel::TYPES as $t): ?>
              <option value="<?= $t ?>"<?= $filters['type'] === $t ? ' selected' : '' ?>><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
            </select>
<?php endif; ?>
            <select class="admin-select" name="statut" aria-label="Filtrer par statut">
              <option value="">Tous les statuts</option>
<?php foreach (FactureModel::STATUTS as $s): ?>
              <option value="<?= $s ?>"<?= $filters['statut'] === $s ? ' selected' : '' ?>><?= e(FactureController::statutLabel($s)) ?></option>
<?php endforeach; ?>
            </select>
            <select class="admin-select" name="tri" aria-label="Trier">
              <option value="recentes"<?= $filters['tri'] === 'recentes' ? ' selected' : '' ?>>Plus récentes</option>
              <option value="anciennes"<?= $filters['tri'] === 'anciennes' ? ' selected' : '' ?>>Plus anciennes</option>
              <option value="montant_desc"<?= $filters['tri'] === 'montant_desc' ? ' selected' : '' ?>>Montant décroissant</option>
              <option value="montant_asc"<?= $filters['tri'] === 'montant_asc' ? ' selected' : '' ?>>Montant croissant</option>
              <option value="echeance"<?= $filters['tri'] === 'echeance' ? ' selected' : '' ?>>Échéance la plus proche</option>
            </select>
            <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="filter"></span> Filtrer</button>
<?php if ($filtreActif): ?>
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-factures') ?>">Réinitialiser</a>
<?php endif; ?>
          </form>

<?php if ($factures === []): ?>
          <p class="admin-empty"><?= $filtreActif ? 'Aucune facture ne correspond à ces critères.' : 'Aucune facture enregistrée.' ?></p>
<?php else: ?>
          <div class="admin-table-wrap">
            <table class="admin-table bill-admin-table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Références</th>
                  <th scope="col">Citoyen</th>
                  <th scope="col">Fournisseur</th>
                  <th scope="col">Période</th>
                  <th scope="col">Consommation</th>
                  <th scope="col">Montant</th>
                  <th scope="col">Statut</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($factures as $f): ?>
                <tr>
                  <td class="admin-table__id" data-label="N°"><?= $f->getIdFacture() ?></td>
                  <td class="admin-table__main bill-nowrap bill-refs" data-label="Références">
                    <span title="Référence d'abonnement"><?= e(FactureController::formatReference($f->getReferenceAbonnement())) ?></span>
<?php if ($f->getReferencePaiement() !== null): ?>
                    <small title="Référence de paiement (talon)">Talon <?= e(FactureController::formatReference($f->getReferencePaiement(), 4)) ?></small>
<?php endif; ?>
                  </td>
                  <td class="bill-client" data-label="Citoyen"><?= e($f->getNomClient()) ?><small><?= e($f->getEmailClient()) ?></small></td>
                  <td class="bill-nowrap" data-label="Fournisseur"><span class="bill-admin-provider"><?= $logos[$f->getTypeRessource()] ?? "" ?><span class="admin-badge <?= $f->getTypeRessource() === 'eau' ? 'admin-badge--ok' : 'admin-badge--info' ?>" title="<?= e(FactureController::typeLabel($f->getTypeRessource())) ?>"><?= e(FactureController::fournisseur($f->getTypeRessource())) ?></span></span></td>
                  <td class="bill-period" data-label="Période"><span><?= e(dateFr($f->getPeriodeDebut())) ?></span><span>→ <?= e(dateFr($f->getPeriodeFin())) ?></span></td>
                  <td class="bill-nowrap" data-label="Consommation"><?= e(FactureController::formatQuantite($f->getQuantite(), $f->getTypeRessource())) ?></td>
                  <td class="bill-amount" data-label="Montant"><?= e(FactureController::formatMontant($f->getMontant())) ?></td>
                  <td class="bill-nowrap" data-label="Statut"><span class="admin-badge <?= $f->getStatut() === 'payee' ? 'admin-badge--muted' : 'admin-badge--warn' ?>"><?= e(FactureController::statutLabel($f->getStatut())) ?></span></td>
                  <td class="bill-admin-actions"><div class="admin-actions">
                    <a class="admin-action" href="<?= url('admin-facture-form', ['id' => $f->getIdFacture()]) ?>" aria-label="Modifier la <?= e(lcfirst(FactureController::titre($f))) ?>"><span data-icon="pencil"></span></a>
                    <form method="post" action="<?= url('admin-facture-supprimer') ?>" data-confirm="Supprimer la <?= e(lcfirst(FactureController::titre($f))) ?> de <?= e($f->getNomClient()) ?> ?">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_facture" value="<?= $f->getIdFacture() ?>">
                      <button class="admin-action admin-action--danger" type="submit" aria-label="Supprimer la <?= e(lcfirst(FactureController::titre($f))) ?>"><span data-icon="trash"></span></button>
                    </form>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="admin-table-count"><?= count($factures) ?> facture<?= count($factures) > 1 ? 's' : '' ?><?= $filtreActif ? ' (filtrées)' : '' ?></p>
<?php endif; ?>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Factures — WASLA Admin';
$currentPage = 'admin-factures';
$pageScripts = ['js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
