<?php
/**
 * Vue : détail d'une facture — Front Office — Module 2 (Hiba).
 * Variables reçues : $facture (FactureModel, déjà vérifiée : elle appartient au citoyen connecté),
 *                    $precedente (FactureModel|null : facture précédente du même fournisseur, même périmètre).
 * Depuis cette page : modifier, supprimer (POST + confirmation) ou analyser la facture dans Factures & IA.
 */
require_once __DIR__ . '/partials/ui.php';
ob_start();
$id = $facture->getIdFacture();
$type = $facture->getTypeRessource();
$eau = $type === 'eau';
$payee = $facture->getStatut() === 'payee';
$debut = new DateTimeImmutable($facture->getPeriodeDebut());
$fin = new DateTimeImmutable($facture->getPeriodeFin());
$jours = max(1, (int) $debut->diff($fin)->days);
$titre = FactureController::titre($facture);
$unite = $eau ? 'm³' : 'kWh';
$parJour = $facture->getQuantite() / $jours;
$fmtJour = fn (float $v): string => number_format($v, $eau ? 2 : 1, ',', ' ') . ' ' . $unite . '/j';

// Barre « date limite » : temps écoulé entre la fin de la période et la date limite
$aujourdhui = new DateTimeImmutable('today');
$echeance = $facture->getDateEcheance() ? new DateTimeImmutable($facture->getDateEcheance()) : null;
$retard = !$payee && $echeance !== null && $echeance < $aujourdhui;
$dueTexte = '';
$duePct = 100;
if ($payee) {
    $dueTexte = 'Facture payée';
} elseif ($echeance !== null) {
    $total = max(1, (int) $fin->diff($echeance)->days);
    $ecoule = $aujourdhui < $fin ? 0 : (int) $fin->diff($aujourdhui)->days;
    $reste = (int) $aujourdhui->diff($echeance)->days;
    $duePct = $retard ? 100 : min(100, (int) round($ecoule / $total * 100));
    $dueTexte = $retard ? 'Dépassée de ' . $reste . ' jour' . ($reste > 1 ? 's' : '') : ($reste === 0 ? "Aujourd'hui" : 'Dans ' . $reste . ' jour' . ($reste > 1 ? 's' : ''));
}

// Comparaison avec la facture précédente (consommation moyenne par jour, comme dans Factures & IA)
$variation = null;
if ($precedente !== null) {
    $pd = new DateTimeImmutable($precedente->getPeriodeDebut());
    $pf = new DateTimeImmutable($precedente->getPeriodeFin());
    $prevParJour = $precedente->getQuantite() / max(1, (int) $pd->diff($pf)->days);
    $variation = $prevParJour > 0 ? ($parJour - $prevParJour) / $prevParJour * 100 : null;
    $maxJour = max($parJour, $prevParJour) ?: 1;
}
?>
  <main id="main" class="bill-ui">
<?php require __DIR__ . '/partials/sprite.php'; ?>
    <div class="container">
      <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="<?= url('mon-compte') ?>">Mon espace</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <a href="<?= url('mes-factures') ?>">Mes factures</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <span aria-current="page" data-no-i18n>Facture <?= e(FactureController::fournisseur($type)) ?></span>
      </nav>

      <header class="bill-head">
        <div>
          <p class="bill-eyebrow">Mes factures · Détail</p>
          <h1 data-no-i18n>Facture <?= e(FactureController::fournisseur($type)) ?></h1>
          <p>Du <?= e(dateFr($facture->getPeriodeDebut())) ?> au <?= e(dateFr($facture->getPeriodeFin())) ?> · <?= $jours ?> jours</p>
        </div>
      </header>

      <div class="bill-detail">
        <article class="bill-ticket bill-glass<?= $eau ? ' bill-ticket--sonede' : '' ?>">
          <div class="bill-ticket__top">
            <?= billLogo($type, 'lg', true) ?>
            <div>
              <p class="bill-row__title"><?= e(FactureController::typeLabel($type)) ?></p>
              <p class="bill-row__sub" data-no-i18n>Abonnement <?= e(FactureController::formatReference($facture->getReferenceAbonnement())) ?></p>
            </div>
            <span class="bill-chip <?= $payee ? 'bill-chip--paid' : ($retard ? 'bill-chip--late' : 'bill-chip--due') ?>"><?= $payee ? 'Payée' : ($retard ? 'En retard' : 'À payer') ?></span>
          </div>
          <p class="bill-ticket__amount"><?= e(number_format($facture->getMontant(), 3, ',', ' ')) ?> <small>TND</small></p>
          <p class="bill-muted"><?= $payee ? 'Montant réglé' : 'Montant à payer' ?></p>

<?php if ($dueTexte !== ''): ?>
          <div class="bill-due">
            <p class="bill-due__row">
              <span><?= $echeance !== null ? 'Date limite : ' . e(dateFr($facture->getDateEcheance())) : 'Statut' ?></span>
              <span class="<?= $retard ? 'bill-delta-bad' : ($payee ? 'bill-delta-good' : '') ?>"><?= e($dueTexte) ?></span>
            </p>
            <div class="bill-progress<?= $payee ? ' bill-progress--paid' : ($retard ? ' bill-progress--late' : '') ?>" role="img" aria-label="<?= e($dueTexte) ?>"><span data-progress="<?= $payee ? 100 : $duePct ?>"></span></div>
          </div>
<?php endif; ?>

          <dl class="bill-facts">
            <div class="bill-fact"><dt>Consommation</dt><dd><?= e(FactureController::formatQuantite($facture->getQuantite(), $type)) ?></dd></div>
            <div class="bill-fact"><dt>Par jour</dt><dd><?= e($fmtJour($parJour)) ?></dd></div>
            <div class="bill-fact"><dt>Talon</dt><?= $facture->getReferencePaiement() !== null
                ? '<dd data-no-i18n>' . e(FactureController::formatReference($facture->getReferencePaiement(), 4)) . '</dd>'
                : '<dd class="bill-muted">Non renseigné</dd>' ?></div>
            <div class="bill-fact"><dt>Début</dt><dd><?= e(dateFr($facture->getPeriodeDebut())) ?></dd></div>
            <div class="bill-fact"><dt>Fin</dt><dd><?= e(dateFr($facture->getPeriodeFin())) ?></dd></div>
            <div class="bill-fact"><dt>Enregistrée le</dt><dd><?= e(dateFr($facture->getDateAjout(), true)) ?></dd></div>
          </dl>

          <div class="bill-ticket__actions">
            <a class="bill-btn bill-btn--white" href="<?= url('facture-modifier', ['id' => $id]) ?>"><span data-icon="pencil"></span>Modifier la facture</a>
            <form method="post" action="<?= url('facture-supprimer') ?>" data-confirm="Supprimer la <?= e(lcfirst($titre)) ?> ? Cette action est définitive.">
              <?= csrfField() ?>
              <input type="hidden" name="id_facture" value="<?= $id ?>">
              <button class="bill-btn bill-btn--danger" type="submit"><span data-icon="trash"></span>Supprimer</button>
            </form>
            <a class="bill-btn bill-btn--glass" href="<?= url('mes-factures') ?>"><span data-icon="arrow-left"></span>Retour</a>
          </div>
        </article>

        <aside class="bill-aside">
          <article class="bill-panel bill-glass">
            <h2>Comparée à la facture précédente</h2>
<?php if ($precedente !== null): ?>
            <div class="bill-compare">
              <div class="bill-compare__row">
                <span>Cette facture <b><?= e($fmtJour($parJour)) ?></b></span>
                <div class="bill-progress<?= $eau ? '' : ' bill-progress--paid' ?>"><span data-progress="<?= round($parJour / $maxJour * 100) ?>"></span></div>
              </div>
              <div class="bill-compare__row">
                <span>Précédente (<?= e(dateFr($precedente->getPeriodeDebut())) ?> → <?= e(dateFr($precedente->getPeriodeFin())) ?>) <b><?= e($fmtJour($prevParJour)) ?></b></span>
                <div class="bill-progress bill-progress--prev"><span data-progress="<?= round($prevParJour / $maxJour * 100) ?>"></span></div>
              </div>
            </div>
<?php if ($variation !== null): ?>
            <p><span class="<?= $variation > 0 ? 'bill-delta-bad' : 'bill-delta-good' ?>"><?= ($variation > 0 ? '+' : ($variation < 0 ? '−' : '')) . e(number_format(abs($variation), 1, ',', ' ')) ?> %</span> <span class="bill-muted">de consommation par jour</span></p>
<?php endif; ?>
<?php else: ?>
            <p class="bill-muted">C'est votre première facture <?= e(FactureController::fournisseur($type)) ?> enregistrée : la comparaison sera disponible dès la prochaine.</p>
<?php endif; ?>
          </article>
          <article class="bill-insight bill-glass">
            <h3><span data-icon="sparkles"></span>Analyse de Wasel</h3>
            <p>Causes possibles d'une variation, décomposition du montant et conseils pour économiser, calculés à partir de vos factures.</p>
            <a class="bill-btn bill-btn--glass" href="<?= url('factures', ['onglet' => 'analysis', 'facture' => $id]) ?>">Analyser cette facture<span data-icon="arrow-right"></span></a>
          </article>
        </aside>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = $titre . ' — WASLA';
$description = 'Détail d\'une facture STEG / SONEDE.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
