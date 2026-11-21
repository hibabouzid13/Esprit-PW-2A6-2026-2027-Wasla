<?php
/**
 * Vue : Mes factures (liste + recherche / filtres / tri) — Front Office — Module 2 (Hiba).
 * Variables reçues : $factures (FactureModel[]), $summary (FactureController::summary()),
 *                    $filters (FactureController::cleanFilters()).
 */
ob_start();
$filtreActif = $filters['q'] !== '' || $filters['type'] !== '' || $filters['statut'] !== '';
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Mes factures</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Module 02 · Factures &amp; consommation</p>
            <h1>Mes factures</h1>
            <p>Enregistrez vos factures STEG et SONEDE, suivez ce qu'il reste à payer et retrouvez chaque facture en quelques secondes.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('factures') ?>"><span data-icon="sparkles"></span> Factures &amp; IA</a>
            <a class="btn btn--primary" href="<?= url('facture-ajouter') ?>"><span data-icon="plus"></span> Ajouter une facture</a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <div class="bill-stats">
          <div class="card bill-stat">
            <span class="svc-icon svc-icon--navy" data-icon="file-text"></span>
            <div><p class="bill-stat__label">Factures enregistrées</p><p class="bill-stat__value"><?= $summary['total'] ?></p></div>
          </div>
          <div class="card bill-stat">
            <span class="svc-icon svc-icon--amber" data-icon="clock"></span>
            <div><p class="bill-stat__label">Reste à payer</p><p class="bill-stat__value"><?= e(FactureController::formatMontant($summary['montantAPayer'])) ?></p>
              <p class="bill-stat__sub"><?= $summary['aPayer'] ?> facture<?= $summary['aPayer'] > 1 ? 's' : '' ?> à payer</p></div>
          </div>
          <div class="card bill-stat">
            <span class="svc-icon" data-icon="zap"></span>
            <div><p class="bill-stat__label">Électricité (STEG)</p><p class="bill-stat__value"><?= $summary['electricite'] ?></p></div>
          </div>
          <div class="card bill-stat">
            <span class="svc-icon svc-icon--green" data-icon="droplet"></span>
            <div><p class="bill-stat__label">Eau (SONEDE)</p><p class="bill-stat__value"><?= $summary['eau'] ?></p></div>
          </div>
        </div>

        <div class="card">
          <div class="card__header">
            <div>
              <h2 class="card__title">Factures enregistrées</h2>
              <p class="svc-card-sub"><?= count($factures) ?> résultat<?= count($factures) > 1 ? 's' : '' ?><?= $filtreActif ? ' (filtrés)' : '' ?></p>
            </div>
          </div>

          <!-- Recherche / filtres / tri : formulaire GET (lecture seule, pas de jeton CSRF nécessaire) -->
          <form class="bill-filters" method="get" action="index.php" role="search">
            <input type="hidden" name="action" value="mes-factures">
            <div class="field">
              <label for="f-q">Référence</label>
              <input class="input" id="f-q" name="q" type="text" inputmode="numeric" autocomplete="off" placeholder="N° d'abonnement ou de talon" value="<?= e($filters['q']) ?>">
            </div>
            <div class="field">
              <label for="f-type">Fournisseur</label>
              <select class="select" id="f-type" name="type">
                <option value="">Toutes</option>
<?php foreach (FactureModel::TYPES as $t): ?>
                <option value="<?= $t ?>"<?= $filters['type'] === $t ? ' selected' : '' ?>><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="f-statut">Statut</label>
              <select class="select" id="f-statut" name="statut">
                <option value="">Tous</option>
<?php foreach (FactureModel::STATUTS as $s): ?>
                <option value="<?= $s ?>"<?= $filters['statut'] === $s ? ' selected' : '' ?>><?= e(FactureController::statutLabel($s)) ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="f-tri">Trier par</label>
              <select class="select" id="f-tri" name="tri">
                <option value="recentes"<?= $filters['tri'] === 'recentes' ? ' selected' : '' ?>>Plus récentes</option>
                <option value="anciennes"<?= $filters['tri'] === 'anciennes' ? ' selected' : '' ?>>Plus anciennes</option>
                <option value="montant_desc"<?= $filters['tri'] === 'montant_desc' ? ' selected' : '' ?>>Montant décroissant</option>
                <option value="montant_asc"<?= $filters['tri'] === 'montant_asc' ? ' selected' : '' ?>>Montant croissant</option>
                <option value="echeance"<?= $filters['tri'] === 'echeance' ? ' selected' : '' ?>>Échéance la plus proche</option>
              </select>
            </div>
            <div class="bill-filters__actions">
              <button class="btn btn--secondary" type="submit"><span data-icon="search"></span> Filtrer</button>
<?php if ($filtreActif): ?>
              <a class="btn btn--ghost" href="<?= url('mes-factures') ?>">Réinitialiser</a>
<?php endif; ?>
            </div>
          </form>

<?php if ($factures === []): ?>
          <div class="empty-state bill-empty">
            <span class="svc-icon svc-icon--gray" data-icon="file-text"></span>
            <h3><?= $filtreActif ? 'Aucune facture ne correspond à ces critères' : 'Aucune facture pour le moment' ?></h3>
            <p><?= $filtreActif ? 'Modifiez la recherche ou réinitialisez les filtres.' : 'Ajoutez votre première facture STEG ou SONEDE pour commencer le suivi.' ?></p>
<?php if (!$filtreActif): ?>
            <a class="btn btn--primary" href="<?= url('facture-ajouter') ?>"><span data-icon="plus"></span> Ajouter une facture</a>
<?php endif; ?>
          </div>
<?php else: ?>
          <div class="table-wrap">
            <table class="data-table svc-table">
              <thead>
                <tr>
                  <th scope="col">Fournisseur</th>
                  <th scope="col">Références</th>
                  <th scope="col">Période</th>
                  <th scope="col">Consommation</th>
                  <th scope="col">Montant</th>
                  <th scope="col">Échéance</th>
                  <th scope="col">Statut</th>
                  <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($factures as $f): ?>
                <tr>
                  <td data-label="Fournisseur"><span class="svc-tag <?= $f->getTypeRessource() === 'eau' ? 'svc-tag--green' : 'svc-tag--blue' ?>"><?= e(FactureController::typeLabel($f->getTypeRessource())) ?></span></td>
                  <td class="bill-refs" data-label="Références" data-no-i18n>
                    <strong title="Référence d'abonnement"><?= e(FactureController::formatReference($f->getReferenceAbonnement())) ?></strong>
<?php if ($f->getReferencePaiement() !== null): ?>
                    <small title="Référence de paiement (talon)">Talon <?= e(FactureController::formatReference($f->getReferencePaiement(), 4)) ?></small>
<?php endif; ?>
                  </td>
                  <td data-label="Période"><?= e(dateFr($f->getPeriodeDebut())) ?> → <?= e(dateFr($f->getPeriodeFin())) ?></td>
                  <td data-label="Consommation"><?= e(FactureController::formatQuantite($f->getQuantite(), $f->getTypeRessource())) ?></td>
                  <td data-label="Montant"><strong><?= e(FactureController::formatMontant($f->getMontant())) ?></strong></td>
                  <td data-label="Échéance"><?= e(dateFr($f->getDateEcheance())) ?></td>
                  <td data-label="Statut"><span class="svc-tag <?= $f->getStatut() === 'payee' ? 'svc-tag--green' : 'svc-tag--demo' ?>"><?= e(FactureController::statutLabel($f->getStatut())) ?></span></td>
                  <td class="svc-table__actions">
                    <div class="svc-actions bill-row-actions">
                      <a class="svc-icon-btn" href="<?= url('facture-voir', ['id' => $f->getIdFacture()]) ?>" aria-label="Voir la <?= e(lcfirst(FactureController::titre($f))) ?>" title="Voir"><span data-icon="eye"></span></a>
                      <form method="post" action="<?= url('facture-supprimer') ?>" data-confirm="Supprimer la <?= e(lcfirst(FactureController::titre($f))) ?> ? Cette action est définitive.">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_facture" value="<?= $f->getIdFacture() ?>">
                        <button class="svc-icon-btn svc-icon-btn--danger" type="submit" aria-label="Supprimer la <?= e(lcfirst(FactureController::titre($f))) ?>" title="Supprimer"><span data-icon="trash"></span></button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
<?php endif; ?>
        </div>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mes factures — WASLA';
$description = 'Vos factures STEG et SONEDE : ajout, consultation, modification, suppression et suivi des paiements.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
