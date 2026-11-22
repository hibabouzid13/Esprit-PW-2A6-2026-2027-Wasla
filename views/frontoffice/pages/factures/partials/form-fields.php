<?php
/**
 * Champs du formulaire de facture — partagés par facture-form.php (ajout / modification)
 * et par la fenêtre « Ajouter une facture » de Factures & IA.
 *
 * Variables attendues :
 *   $old         valeurs du formulaire (FactureController::emptyForm / toForm / postedForm)
 *   $errors      erreurs PHP (champ => message)
 *   $p           préfixe des id (« b » page, « m » fenêtre) : id uniques dans la page
 *   $etapes      true = parcours guidé en 4 étapes (ajout) ; false = formulaire complet (modification)
 *   $submitLabel texte du bouton d'envoi
 *   $cancelHtml  bouton / lien « Annuler » (balisage déjà échappé)
 *
 * Aucune validation HTML5 : data-rules (validation.js) + factures-crud.js, puis FactureController::check().
 * Sans JavaScript, les 4 étapes s'affichent l'une sous l'autre et le formulaire reste utilisable.
 */
require_once __DIR__ . '/ui.php';
$type = $old['type_ressource'];
$unite = $type === 'eau' ? 'm³' : 'kWh';
// Exemples selon le fournisseur (mis à jour par factures-crud.js quand on change de fournisseur)
$ex = [
    'electricite' => ['Ex : 937974800', 'Ex : 0000049937974800 (facultatif)', '9 chiffres, en haut de la facture STEG.', '16 chiffres, sur le talon en bas de la facture.'],
    'eau'         => ['Ex : 123456789', 'Ex : 00123456789012 (facultatif)', '8 ou 9 chiffres (code district + numéro de police), en haut de la facture SONEDE.', '12 à 16 chiffres, sur le talon en bas de la facture.'],
][$type] ?? ["Choisissez d'abord le fournisseur", "Choisissez d'abord le fournisseur", 'Choisissez le fournisseur pour voir le format attendu.', 'Facultatif : talon en bas de la facture.'];
$exQte = $type === 'eau' ? '22.5 ou 22.5 m³' : '455 ou 455 kWh';
$options = [
    'electricite' => ['Électricité · STEG', 'Abonnement 9 chiffres · consommation en kWh'],
    'eau'         => ['Eau · SONEDE', 'Abonnement 8 ou 9 chiffres · consommation en m³'],
];
?>
<?php if ($etapes): ?>
            <ol class="bill-dots" aria-hidden="true"><li class="is-done"></li><li></li><li></li><li></li></ol>
            <p class="sr-only" data-wizard-status aria-live="polite"></p>
<?php endif; ?>

            <fieldset class="bill-step" data-step="1">
              <legend><?php if ($etapes): ?><span class="bill-step__ico" aria-hidden="true"><span data-icon="file-text"></span></span><?php endif; ?>Quel fournisseur ?</legend>
              <p class="bill-step__lead">Le format des références dépend du fournisseur.</p>
              <div class="bill-options">
<?php foreach (FactureModel::TYPES as $t): ?>
                <label class="bill-option">
                  <input type="radio" name="type_ressource" value="<?= e($t) ?>"<?= $type === $t ? ' checked' : '' ?><?= isset($errors['type_ressource']) ? ' class="is-invalid"' : '' ?>
                         data-rules="required|in:electricite,eau" data-message-required="Choisissez le fournisseur.">
                  <span class="bill-option__box">
                    <?= billLogo($t, 'lg', true) ?>
                    <span class="bill-option__txt"><b><?= e($options[$t][0]) ?></b><span><?= e($options[$t][1]) ?></span></span>
                    <span class="bill-option__arrow" aria-hidden="true"><span data-icon="arrow-right"></span></span>
                  </span>
                </label>
<?php endforeach; ?>
              </div>
              <?= fieldError($errors, 'type_ressource') ?>
            </fieldset>

            <fieldset class="bill-step" data-step="2">
              <legend><?php if ($etapes): ?><span class="bill-step__ico" aria-hidden="true" data-chosen-logo><span data-icon="file-text"></span></span><?php endif; ?>Vos références <span data-chosen-name></span></legend>
              <p class="bill-step__lead">Recopiez-les depuis le haut et le talon de la facture.</p>
              <div class="bill-step__fields">
                <div class="field">
                  <label for="<?= $p ?>-abo">Référence d'abonnement</label>
                  <input class="input<?= invalidClass($errors, 'reference_abonnement') ?>" id="<?= $p ?>-abo" name="reference_abonnement" type="text"
                         inputmode="numeric" autocomplete="off" placeholder="<?= e($ex[0]) ?>" value="<?= e($old['reference_abonnement']) ?>"
                         data-rules="required" data-message-required="Indiquez la référence d'abonnement (en haut de la facture)."
                         aria-describedby="<?= $p ?>-abo-hint">
                  <p class="field__hint" id="<?= $p ?>-abo-hint" data-bill-abo-hint><?= e($ex[2]) ?></p>
                  <?= fieldError($errors, 'reference_abonnement') ?>
                </div>
                <div class="field">
                  <label for="<?= $p ?>-paie">Référence de paiement (talon) <span class="bill-optional">— facultatif</span></label>
                  <input class="input<?= invalidClass($errors, 'reference_paiement') ?>" id="<?= $p ?>-paie" name="reference_paiement" type="text"
                         inputmode="numeric" autocomplete="off" placeholder="<?= e($ex[1]) ?>" value="<?= e($old['reference_paiement']) ?>"
                         aria-describedby="<?= $p ?>-paie-hint">
                  <p class="field__hint" id="<?= $p ?>-paie-hint" data-bill-paie-hint><?= e($ex[3]) ?></p>
                  <?= fieldError($errors, 'reference_paiement') ?>
                </div>
              </div>
            </fieldset>

            <fieldset class="bill-step" data-step="3">
              <legend><?php if ($etapes): ?><span class="bill-step__ico" aria-hidden="true"><span data-icon="clock"></span></span><?php endif; ?>Période et montant</legend>
              <p class="bill-step__lead">Tels qu'ils figurent sur la facture.</p>
              <div class="bill-step__fields">
                <div class="form-row">
                  <div class="field">
                    <label for="<?= $p ?>-debut">Début de la période</label>
                    <input class="input<?= invalidClass($errors, 'periode_debut') ?>" id="<?= $p ?>-debut" name="periode_debut" type="date"
                           value="<?= e($old['periode_debut']) ?>" data-rules="required|date" data-message-required="Indiquez le début de la période facturée.">
                    <?= fieldError($errors, 'periode_debut') ?>
                  </div>
                  <div class="field">
                    <label for="<?= $p ?>-fin">Fin de la période</label>
                    <input class="input<?= invalidClass($errors, 'periode_fin') ?>" id="<?= $p ?>-fin" name="periode_fin" type="date"
                           value="<?= e($old['periode_fin']) ?>" data-rules="required|date" data-message-required="Indiquez la fin de la période facturée.">
                    <?= fieldError($errors, 'periode_fin') ?>
                  </div>
                </div>
                <div class="form-row">
                  <div class="field">
                    <label for="<?= $p ?>-qte">Consommation (<span data-bill-unit><?= $unite ?></span>)</label>
                    <input class="input<?= invalidClass($errors, 'quantite') ?>" id="<?= $p ?>-qte" name="quantite" type="text" autocomplete="off" placeholder="ex. <?= $exQte ?>"
                           value="<?= e($old['quantite']) ?>" data-rules="required|max:20" data-message-required="Indiquez la consommation facturée."
                           data-message-max="Valeur trop longue.">
                    <?= fieldError($errors, 'quantite') ?>
                  </div>
                  <div class="field">
                    <label for="<?= $p ?>-montant">Montant (TND)</label>
                    <input class="input<?= invalidClass($errors, 'montant') ?>" id="<?= $p ?>-montant" name="montant" type="text" autocomplete="off" placeholder="ex. 500 ou 500 TND"
                           value="<?= e($old['montant']) ?>" data-rules="required|max:20" data-message-required="Indiquez le montant à payer."
                           data-message-max="Valeur trop longue.">
                    <?= fieldError($errors, 'montant') ?>
                  </div>
                </div>
                <div class="field">
                  <label for="<?= $p ?>-echeance">Date limite de paiement <span class="bill-optional">— facultatif</span></label>
                  <input class="input<?= invalidClass($errors, 'date_echeance') ?>" id="<?= $p ?>-echeance" name="date_echeance" type="date"
                         value="<?= e($old['date_echeance']) ?>" data-rules="date">
                  <?= fieldError($errors, 'date_echeance') ?>
                </div>
              </div>
            </fieldset>

            <fieldset class="bill-step" data-step="4">
              <legend><?php if ($etapes): ?><span class="bill-step__ico" aria-hidden="true"><span data-icon="circle-check"></span></span><?php endif; ?>Statut de la facture</legend>
              <p class="bill-step__lead">Avez-vous déjà réglé cette facture ?</p>
              <div class="bill-pills">
<?php foreach (FactureModel::STATUTS as $s): ?>
                <label class="bill-pill"><input type="radio" name="statut" value="<?= e($s) ?>"<?= $old['statut'] === $s ? ' checked' : '' ?>
                       data-rules="required|in:a_payer,payee" data-message-required="Choisissez le statut."><span><?= e(FactureController::statutLabel($s)) ?></span></label>
<?php endforeach; ?>
              </div>
              <?= fieldError($errors, 'statut') ?>
<?php if ($etapes): ?>
              <dl class="bill-recap" data-wizard-recap hidden></dl>
<?php endif; ?>
            </fieldset>

            <div class="bill-wizard__foot">
              <?= $cancelHtml ?>
              <span class="bill-wizard__end">
<?php if ($etapes): ?>
                <button class="bill-btn bill-btn--glass" type="button" data-wizard-nav="prev"><span data-icon="arrow-left"></span>Retour</button>
                <button class="bill-btn bill-btn--white" type="button" data-wizard-nav="next">Continuer<span data-icon="arrow-right"></span></button>
<?php endif; ?>
                <button class="bill-btn bill-btn--glow" type="submit" data-wizard-submit data-loading="Enregistrement…"><span data-icon="check"></span><?= e($submitLabel) ?></button>
              </span>
            </div>
