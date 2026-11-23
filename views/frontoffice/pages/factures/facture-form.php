<?php
/**
 * Vue : ajout / modification d'une facture — Front Office — Module 2 (Hiba).
 * Variables reçues : $mode ('ajout' | 'modification'), $facture (FactureModel|null),
 *                    $old (valeurs du formulaire), $errors (champ => message).
 * Ajout : parcours guidé en 4 étapes (fournisseur, références, période et montant, statut).
 * Modification : les mêmes champs sur une seule page.
 * Aucune validation HTML5 : validation.js + factures-crud.js dans le navigateur, puis PHP.
 */
ob_start();
$edition = $mode === 'modification';
$action = $edition ? url('facture-modifier', ['id' => $facture->getIdFacture()]) : url('facture-ajouter');
$retour = $edition ? url('facture-voir', ['id' => $facture->getIdFacture()]) : url('mes-factures');
?>
  <main id="main" class="bill-ui">
<?php require __DIR__ . '/partials/sprite.php'; ?>
    <div class="container">
      <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="<?= url('mon-compte') ?>">Mon espace</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <a href="<?= url('mes-factures') ?>">Mes factures</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
<?php if ($edition): ?>
        <a href="<?= $retour ?>" data-no-i18n><?= e(FactureController::titre($facture)) ?></a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
<?php endif; ?>
        <span aria-current="page"><?= $edition ? 'Modifier' : 'Ajouter' ?></span>
      </nav>

      <header class="bill-head">
        <div>
          <p class="bill-eyebrow">Mes factures · <?= $edition ? 'Modifier' : 'Ajouter' ?></p>
          <h1><?= $edition ? 'Modifier la facture' : 'Ajouter une facture' ?></h1>
          <p><?= $edition
              ? 'Corrigez les informations de votre facture, puis enregistrez.'
              : 'Un parcours en 4 étapes : fournisseur, références, période et montant, statut.' ?></p>
        </div>
      </header>

      <form class="bill-wizard bill-glass<?= $edition ? ' bill-wizard--full' : '' ?>" method="post" action="<?= $action ?>"
            data-validate data-bill-crud-form<?= $edition ? '' : ' data-bill-wizard' ?>>
        <?= csrfField() ?>
<?php if ($errors !== []): ?>
        <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
<?php
$p = 'b';
$etapes = !$edition;
$submitLabel = $edition ? 'Enregistrer les modifications' : 'Ajouter la facture';
$cancelHtml = '<a class="bill-btn bill-btn--glass" href="' . $retour . '">Annuler</a>';
require __DIR__ . '/partials/form-fields.php';
?>
      </form>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = ($edition ? 'Modifier une facture' : 'Ajouter une facture') . ' — WASLA';
$description = 'Formulaire de facture STEG / SONEDE.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
