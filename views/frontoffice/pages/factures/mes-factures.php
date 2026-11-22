<?php
/**
 * Vue : Mes factures (liste + recherche / filtres / tri) — Front Office — Module 2 (Hiba).
 * Variables reçues : $factures (FactureModel[]), $summary (FactureController::summary()),
 *                    $filters (FactureController::cleanFilters()).
 * Filtres : formulaire GET (lecture seule, pas de jeton CSRF). Les pastilles sont des boutons radio :
 * sans JavaScript, « Filtrer » envoie le formulaire ; avec JavaScript, un clic suffit (factures-crud.js).
 */
require_once __DIR__ . '/partials/ui.php';
ob_start();
$filtreActif = $filters['q'] !== '' || $filters['type'] !== '' || $filters['statut'] !== '';
$payees = $summary['total'] - $summary['aPayer'];
$pctPayees = $summary['total'] > 0 ? round($payees / $summary['total'] * 100) : 0;
$aujourdhui = date('Y-m-d');
$tris = [
    'recentes'     => 'Plus récentes',
    'anciennes'    => 'Plus anciennes',
    'montant_desc' => 'Montant décroissant',
    'montant_asc'  => 'Montant croissant',
    'echeance'     => 'Échéance la plus proche',
];
?>
  <main id="main" class="bill-ui">
<?php require __DIR__ . '/partials/sprite.php'; ?>
    <div class="container">
      <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="<?= url('accueil') ?>">Accueil</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <a href="<?= url('mon-compte') ?>">Mon espace</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <span aria-current="page">Mes factures</span>
      </nav>

      <header class="bill-head">
        <div>
          <p class="bill-eyebrow">Module 02 · Factures &amp; consommation</p>
          <h1>Mes factures</h1>
          <p>Retrouvez chaque facture STEG et SONEDE en quelques secondes et suivez ce qu'il reste à payer.</p>
        </div>
        <div class="bill-hero__ctas">
          <a class="bill-btn bill-btn--glass" href="<?= url('factures') ?>"><span data-icon="sparkles"></span>Factures &amp; IA</a>
          <a class="bill-btn bill-btn--glow" href="<?= url('facture-ajouter') ?>"><span data-icon="plus"></span>Ajouter une facture</a>
        </div>
      </header>

      <section class="bill-summary" aria-label="Synthèse de vos factures">
        <article class="bill-kpi bill-kpi--amber bill-glass">
          <p class="bill-kpi__top"><span class="bill-ico bill-ico--amber" data-icon="clock"></span>Reste à payer</p>
          <p class="bill-kpi__value"><?= e(number_format($summary['montantAPayer'], 3, ',', ' ')) ?><small>TND</small></p>
          <div class="bill-progress" role="img" aria-label="<?= $payees ?> facture<?= $payees > 1 ? 's' : '' ?> payée<?= $payees > 1 ? 's' : '' ?> sur <?= $summary['total'] ?>"><span data-progress="<?= $pctPayees ?>"></span></div>
          <p class="bill-kpi__foot"><?= $summary['aPayer'] ?> facture<?= $summary['aPayer'] > 1 ? 's' : '' ?> à payer sur <?= $summary['total'] ?></p>
        </article>
        <article class="bill-kpi bill-kpi--steg bill-glass">
          <p class="bill-kpi__top"><?= billLogo('electricite', 'md') ?>Électricité (STEG)</p>
          <p class="bill-kpi__value"><?= $summary['electricite'] ?><small>facture<?= $summary['electricite'] > 1 ? 's' : '' ?></small></p>
          <p class="bill-kpi__foot"><?= e(FactureController::formatMontant($summary['montantElectricite'])) ?> au total</p>
        </article>
        <article class="bill-kpi bill-kpi--sonede bill-glass">
          <p class="bill-kpi__top"><?= billLogo('eau', 'md') ?>Eau (SONEDE)</p>
          <p class="bill-kpi__value"><?= $summary['eau'] ?><small>facture<?= $summary['eau'] > 1 ? 's' : '' ?></small></p>
          <p class="bill-kpi__foot"><?= e(FactureController::formatMontant($summary['montantEau'])) ?> au total</p>
        </article>
      </section>

      <!-- Recherche / filtres / tri : formulaire GET (lecture seule, pas de jeton CSRF nécessaire) -->
      <form class="bill-filters bill-glass" method="get" action="index.php" role="search" data-bill-filters>
        <input type="hidden" name="action" value="mes-factures">
        <label class="bill-search">
          <span data-icon="search"></span>
          <span class="sr-only">Référence</span>
          <input id="f-q" name="q" type="text" inputmode="numeric" autocomplete="off" placeholder="N° d'abonnement ou de talon" value="<?= e($filters['q']) ?>">
        </label>
        <fieldset class="bill-chipset">
          <legend class="sr-only">Fournisseur</legend>
<?php foreach (['' => 'Toutes', 'electricite' => 'STEG', 'eau' => 'SONEDE'] as $val => $lib): ?>
          <label class="bill-fchip"><input type="radio" name="type" value="<?= e($val) ?>"<?= $filters['type'] === $val ? ' checked' : '' ?>><span><?= e($lib) ?></span></label>
<?php endforeach; ?>
        </fieldset>
        <span class="bill-sep" aria-hidden="true"></span>
        <fieldset class="bill-chipset">
          <legend class="sr-only">Statut</legend>
<?php foreach (['' => 'Tous', 'a_payer' => 'À payer', 'payee' => 'Payées'] as $val => $lib): ?>
          <label class="bill-fchip"><input type="radio" name="statut" value="<?= e($val) ?>"<?= $filters['statut'] === $val ? ' checked' : '' ?>><span><?= e($lib) ?></span></label>
<?php endforeach; ?>
        </fieldset>
        <label class="sr-only" for="f-tri">Trier par</label>
        <select class="select" id="f-tri" name="tri">
<?php foreach ($tris as $val => $lib): ?>
          <option value="<?= e($val) ?>"<?= $filters['tri'] === $val ? ' selected' : '' ?>><?= e($lib) ?></option>
<?php endforeach; ?>
        </select>
        <button class="bill-btn bill-btn--glass bill-btn--sm" type="submit"><span data-icon="search"></span>Filtrer</button>
<?php if ($filtreActif): ?>
        <a class="bill-text-btn" href="<?= url('mes-factures') ?>">Réinitialiser</a>
<?php endif; ?>
      </form>

      <p class="bill-muted"><?= count($factures) ?> résultat<?= count($factures) > 1 ? 's' : '' ?><?= $filtreActif ? ' (filtrés)' : '' ?></p>

<?php if ($factures === []): ?>
      <article class="bill-empty bill-glass">
        <svg aria-hidden="true" focusable="false"><use href="#bill-wasel"/></svg>
        <h2><?= $filtreActif ? 'Aucune facture ne correspond à ces critères' : 'Aucune facture pour le moment' ?></h2>
        <p><?= $filtreActif ? 'Modifiez la recherche ou réinitialisez les filtres.' : 'Ajoutez votre première facture STEG ou SONEDE pour commencer le suivi.' ?></p>
<?php if (!$filtreActif): ?>
        <a class="bill-btn bill-btn--glow" href="<?= url('facture-ajouter') ?>"><span data-icon="plus"></span>Ajouter une facture</a>
<?php endif; ?>
      </article>
<?php else: ?>
      <ul class="bill-rows">
<?php foreach ($factures as $f):
    $titre = lcfirst(FactureController::titre($f));
    $echeance = $f->getDateEcheance();
    $payee = $f->getStatut() === 'payee';
    $retard = !$payee && $echeance && $echeance < $aujourdhui;
?>
        <li class="bill-row bill-glass">
          <?= billLogo($f->getTypeRessource(), 'lg') ?>
          <div data-no-i18n>
            <p class="bill-row__title"><?= e(FactureController::formatReference($f->getReferenceAbonnement())) ?></p>
            <p class="bill-row__sub"><?= $f->getReferencePaiement() !== null ? 'Talon ' . e(FactureController::formatReference($f->getReferencePaiement(), 4)) : 'Sans talon' ?></p>
          </div>
          <div class="bill-row__col bill-row__col--opt bill-row__col--hide-sm"><span class="bill-row__label">Période</span><?= e(dateFr($f->getPeriodeDebut())) ?> → <?= e(dateFr($f->getPeriodeFin())) ?></div>
          <div class="bill-row__col"><span class="bill-row__label">Montant · <?= e(FactureController::formatQuantite($f->getQuantite(), $f->getTypeRessource())) ?></span><span class="bill-row__amount"><?= e(FactureController::formatMontant($f->getMontant())) ?></span></div>
          <div class="bill-row__col bill-row__col--status">
            <span class="bill-row__label"><?= $echeance ? 'Échéance ' . e(dateFr($echeance)) : 'Sans échéance' ?></span>
            <span class="bill-chip <?= $payee ? 'bill-chip--paid' : ($retard ? 'bill-chip--late' : 'bill-chip--due') ?>"><?= $payee ? 'Payée' : ($retard ? 'En retard' : 'À payer') ?></span>
          </div>
          <div class="bill-row__actions">
            <form method="post" action="<?= url('facture-supprimer') ?>" data-confirm="Supprimer la <?= e($titre) ?> ? Cette action est définitive.">
              <?= csrfField() ?>
              <input type="hidden" name="id_facture" value="<?= $f->getIdFacture() ?>">
              <button class="bill-icon-btn bill-icon-btn--danger" type="submit" aria-label="Supprimer la <?= e($titre) ?>" title="Supprimer"><span data-icon="trash"></span></button>
            </form>
            <a class="bill-icon-btn" href="<?= url('facture-voir', ['id' => $f->getIdFacture()]) ?>" aria-label="Voir la <?= e($titre) ?>" title="Voir"><span data-icon="arrow-right"></span></a>
          </div>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mes factures — WASLA';
$description = 'Vos factures STEG et SONEDE : ajout, consultation, modification, suppression et suivi des paiements.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
