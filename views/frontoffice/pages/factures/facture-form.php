<?php
/**
 * Vue : formulaire d'ajout / de modification d'une facture — Front Office — Module 2 (Hiba).
 * Variables reçues : $mode ('ajout' | 'modification'), $facture (FactureModel|null),
 *                    $old (valeurs du formulaire), $errors (champ => message).
 * Aucune validation HTML5 : validation.js + factures-crud.js dans le navigateur, puis PHP.
 */
ob_start();
$edition = $mode === 'modification';
$action = $edition ? url('facture-modifier', ['id' => $facture->getIdFacture()]) : url('facture-ajouter');
$unite = $old['type_ressource'] === 'eau' ? 'm³' : 'kWh';
// Exemples selon la ressource (mis à jour par factures-crud.js quand on change de ressource)
$exRef = ['electricite' => 'STG-20260918', 'eau' => 'SND-20260930'][$old['type_ressource']] ?? '';
$exQte = $old['type_ressource'] === 'eau' ? '22.5 ou 22.5 m³' : '455 ou 455 kWh';
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mes-factures') ?>">Mes factures</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
<?php if ($edition): ?>
          <a href="<?= url('facture-voir', ['id' => $facture->getIdFacture()]) ?>" data-no-i18n><?= e($facture->getReference()) ?></a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
<?php endif; ?>
          <span aria-current="page"><?= $edition ? 'Modifier' : 'Ajouter' ?></span>
        </nav>
        <p class="eyebrow">Module 02 · Factures &amp; consommation</p>
        <h1><?= $edition ? 'Modifier la facture' : 'Ajouter une facture' ?></h1>
        <p><?= $edition ? 'Corrigez les informations de votre facture.' : 'Recopiez les informations principales de votre facture STEG ou SONEDE.' ?></p>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <div class="card bill-form-card">
<?php if ($errors !== []): ?>
          <div class="form-alert form-alert--error" role="alert"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>

          <form class="bill-form" method="post" action="<?= $action ?>" data-validate data-bill-crud-form>
            <?= csrfField() ?>

            <div class="form-row">
              <div class="field">
                <label for="b-type">Ressource</label>
                <select class="select<?= invalidClass($errors, 'type_ressource') ?>" id="b-type" name="type_ressource"
                        data-rules="required|in:electricite,eau" data-message-required="Choisissez le type de facture.">
                  <option value="">Choisir…</option>
<?php foreach (FactureModel::TYPES as $t): ?>
                  <option value="<?= $t ?>"<?= $old['type_ressource'] === $t ? ' selected' : '' ?>><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'type_ressource') ?>
              </div>
              <div class="field">
                <label for="b-ref">Référence de la facture</label>
                <input class="input<?= invalidClass($errors, 'reference') ?>" id="b-ref" name="reference" type="text" autocomplete="off"
                       placeholder="<?= $exRef !== '' ? 'ex. ' . $exRef : "Choisissez d'abord la ressource" ?>"
                       value="<?= e($old['reference']) ?>" data-rules="required|max:30" data-message-required="Indiquez la référence imprimée sur la facture."
                       data-message-max="La référence est trop longue (30 caractères au plus).">
                <p class="field__hint" data-bill-ref-hint><?= $exRef !== '' ? 'Format : ' . substr($exRef, 0, 3) . '- suivi de chiffres (ex. ' . $exRef . ').' : 'STG-chiffres pour la STEG, SND-chiffres pour la SONEDE.' ?></p>
                <?= fieldError($errors, 'reference') ?>
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <label for="b-debut">Début de la période</label>
                <input class="input<?= invalidClass($errors, 'periode_debut') ?>" id="b-debut" name="periode_debut" type="date"
                       value="<?= e($old['periode_debut']) ?>" data-rules="required|date" data-message-required="Indiquez le début de la période facturée.">
                <?= fieldError($errors, 'periode_debut') ?>
              </div>
              <div class="field">
                <label for="b-fin">Fin de la période</label>
                <input class="input<?= invalidClass($errors, 'periode_fin') ?>" id="b-fin" name="periode_fin" type="date"
                       value="<?= e($old['periode_fin']) ?>" data-rules="required|date"
                       data-message-required="Indiquez la fin de la période facturée.">
                <?= fieldError($errors, 'periode_fin') ?>
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <label for="b-qte">Consommation (<span data-bill-unit><?= $unite ?></span>)</label>
                <input class="input<?= invalidClass($errors, 'quantite') ?>" id="b-qte" name="quantite" type="text" autocomplete="off" placeholder="ex. <?= $exQte ?>"
                       value="<?= e($old['quantite']) ?>" data-rules="required|max:20" data-message-required="Indiquez la consommation facturée."
                       data-message-max="Valeur trop longue.">
                <?= fieldError($errors, 'quantite') ?>
              </div>
              <div class="field">
                <label for="b-montant">Montant (TND)</label>
                <input class="input<?= invalidClass($errors, 'montant') ?>" id="b-montant" name="montant" type="text" autocomplete="off" placeholder="ex. 500 ou 500 TND"
                       value="<?= e($old['montant']) ?>" data-rules="required|max:20" data-message-required="Indiquez le montant à payer."
                       data-message-max="Valeur trop longue.">
                <?= fieldError($errors, 'montant') ?>
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <label for="b-echeance">Date limite de paiement <span class="field__optional">(facultatif)</span></label>
                <input class="input<?= invalidClass($errors, 'date_echeance') ?>" id="b-echeance" name="date_echeance" type="date"
                       value="<?= e($old['date_echeance']) ?>" data-rules="date">
                <?= fieldError($errors, 'date_echeance') ?>
              </div>
              <fieldset class="field svc-fieldset">
                <legend>Statut</legend>
                <div class="svc-radio-row">
<?php foreach (FactureModel::STATUTS as $s): ?>
                  <label class="svc-radio"><input type="radio" name="statut" value="<?= $s ?>"<?= $old['statut'] === $s ? ' checked' : '' ?>
                         data-rules="required|in:a_payer,payee" data-message-required="Choisissez le statut."><span><?= e(FactureController::statutLabel($s)) ?></span></label>
<?php endforeach; ?>
                </div>
                <?= fieldError($errors, 'statut') ?>
              </fieldset>
            </div>

            <div class="svc-actions bill-form__actions">
              <a class="btn btn--ghost" href="<?= $edition ? url('facture-voir', ['id' => $facture->getIdFacture()]) : url('mes-factures') ?>">Annuler</a>
              <button class="btn btn--primary" type="submit" data-loading="Enregistrement…"><?= $edition ? 'Enregistrer les modifications' : 'Ajouter la facture' ?></button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = ($edition ? 'Modifier une facture' : 'Ajouter une facture') . ' — WASLA';
$description = 'Formulaire de facture STEG / SONEDE.';
$currentPage = 'mes-factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
