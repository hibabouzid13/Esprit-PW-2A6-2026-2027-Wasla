<?php
/**
 * Vue : détail d'une facture — Front Office — Module 2 (Hiba).
 * Variable reçue : $facture (FactureModel, déjà vérifiée : elle appartient au citoyen connecté).
 * Depuis cette page : modifier ou supprimer la facture.
 */
ob_start();
$id = $facture->getIdFacture();
$type = $facture->getTypeRessource();
$payee = $facture->getStatut() === 'payee';
$debut = new DateTimeImmutable($facture->getPeriodeDebut());
$fin = new DateTimeImmutable($facture->getPeriodeFin());
$jours = (int) $debut->diff($fin)->days;
$titre = FactureController::titre($facture);
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mes-factures') ?>">Mes factures</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page" data-no-i18n>Facture <?= e(FactureController::fournisseur($type)) ?></span>
        </nav>
        <p class="eyebrow">Module 02 · Factures &amp; consommation</p>
        <h1 data-no-i18n><?= e($titre) ?></h1>
        <p>Abonnement <span data-no-i18n><?= e(FactureController::formatReference($facture->getReferenceAbonnement())) ?></span> · <?= e(FactureController::typeLabel($type)) ?>.</p>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <article class="card bill-detail">
          <header class="bill-detail__head">
            <span class="svc-icon <?= $type === 'eau' ? 'svc-icon--green' : '' ?>" data-icon="<?= $type === 'eau' ? 'droplet' : 'zap' ?>"></span>
            <div>
              <p class="bill-detail__label">Montant</p>
              <p class="bill-detail__amount"><?= e(FactureController::formatMontant($facture->getMontant())) ?></p>
            </div>
            <span class="svc-tag <?= $payee ? 'svc-tag--green' : 'svc-tag--demo' ?> bill-detail__status"><?= e(FactureController::statutLabel($facture->getStatut())) ?></span>
          </header>

          <dl class="bill-detail__grid">
            <div><dt>Fournisseur</dt><dd><span class="svc-tag <?= $type === 'eau' ? 'svc-tag--green' : 'svc-tag--blue' ?>"><?= e(FactureController::typeLabel($type)) ?></span></dd></div>
            <div><dt>Référence d'abonnement</dt><dd data-no-i18n><?= e(FactureController::formatReference($facture->getReferenceAbonnement())) ?></dd></div>
            <div><dt>Référence de paiement (talon)</dt><dd data-no-i18n><?= $facture->getReferencePaiement() !== null ? e(FactureController::formatReference($facture->getReferencePaiement(), 4)) : '<span class="text-muted">Non renseignée</span>' ?></dd></div>
            <div><dt>Période</dt><dd><?= e(dateFr($facture->getPeriodeDebut())) ?> → <?= e(dateFr($facture->getPeriodeFin())) ?> <small>(<?= $jours ?> jours)</small></dd></div>
            <div><dt>Consommation</dt><dd><?= e(FactureController::formatQuantite($facture->getQuantite(), $type)) ?></dd></div>
            <div><dt>Date limite de paiement</dt><dd><?= $facture->getDateEcheance() ? e(dateFr($facture->getDateEcheance())) : '<span class="text-muted">Non renseignée</span>' ?></dd></div>
            <div><dt>Enregistrée le</dt><dd><?= e(dateFr($facture->getDateAjout(), true)) ?></dd></div>
          </dl>

          <footer class="svc-actions bill-detail__actions">
            <a class="btn btn--ghost" href="<?= url('mes-factures') ?>">Retour à mes factures</a>
            <form method="post" action="<?= url('facture-supprimer') ?>" data-confirm="Supprimer la <?= e(lcfirst($titre)) ?> ? Cette action est définitive.">
              <?= csrfField() ?>
              <input type="hidden" name="id_facture" value="<?= $id ?>">
              <button class="btn btn--outline bill-btn-danger" type="submit"><span data-icon="trash"></span> Supprimer</button>
            </form>
            <a class="btn btn--primary" href="<?= url('facture-modifier', ['id' => $id]) ?>"><span data-icon="pencil"></span> Modifier</a>
          </footer>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = $titre . ' — WASLA';
$description = 'Détail d\'une facture STEG / SONEDE.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
