<?php
/**
 * Vue : ajout / modification d'une facture — Back Office — Module 2 (Hiba).
 * Variables reçues : $facture (FactureModel|null : null = ajout), $old (valeurs), $errors,
 *                    $citoyens (UserModel[], ajout seulement), $typeImpose ('' | 'electricite' | 'eau').
 * Validation : validation.js (navigateur) puis FactureController::check() (PHP).
 */
ob_start();
$edition = $facture !== null;
$action = $edition ? url('admin-facture-form', ['id' => $facture->getIdFacture()]) : url('admin-facture-form');
$err = fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
$msg = fn (string $k): string => '<p class="admin-field__error">' . e($errors[$k] ?? '') . '</p>';
// Fournisseur courant (imposé pour STEG / SONEDE) : unité et exemples affichés même sans JavaScript
$typeCourant = $typeImpose !== '' ? $typeImpose : $old['type_ressource'];
$unite = $typeCourant === 'eau' ? 'm³' : 'kWh';
$exQte = $typeCourant === 'eau' ? '22.5 ou 22.5 m³' : '455 ou 455 kWh';
$ex = [
    'electricite' => ['Ex : 937974800', 'Ex : 0000049937974800 (facultatif)', '9 chiffres, en haut de la facture STEG.', '16 chiffres, sur le talon en bas de la facture.'],
    'eau'         => ['Ex : 123456789', 'Ex : 00123456789012 (facultatif)', '8 ou 9 chiffres (code district + numéro de police), en haut de la facture SONEDE.', '12 à 16 chiffres, sur le talon en bas de la facture.'],
][$typeCourant] ?? ["Choisissez d'abord le fournisseur", "Choisissez d'abord le fournisseur", 'Choisissez le fournisseur pour voir le format attendu.', 'Facultatif : talon en bas de la facture.'];
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head bill-admin-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-factures') ?>">Factures</a><span>/</span><span><?= $edition ? 'Modifier' : 'Ajouter' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="file-text"></span>
              <div>
                <h1><?= $edition ? 'Modifier la ' . e(lcfirst(FactureController::titre($facture))) : 'Ajouter une facture' ?></h1>
                <p><?= $edition ? 'Titulaire : ' . e($facture->getNomClient()) . ' (' . e($facture->getEmailClient()) . ')' : 'Enregistrer une facture pour un citoyen' ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__body">
<?php if (!empty($errors['form'])): ?>
              <p class="admin-flash admin-flash--error" role="alert"><?= e($errors['form']) ?></p>
<?php endif; ?>
              <form class="admin-form" method="post" action="<?= $action ?>" data-validate data-bill-crud-form>
                <?= csrfField() ?>

<?php if (!$edition): ?>
                <div class="admin-field admin-field--full<?= $err('id_utilisateur') ?>">
                  <label for="id_utilisateur">Citoyen titulaire <span class="req" aria-hidden="true">*</span></label>
                  <select class="admin-select" id="id_utilisateur" name="id_utilisateur" data-rules="required|int" data-message-required="<?= e(FactureController::MESSAGES['id_utilisateur']['required']) ?>">
                    <option value="">— Choisir un citoyen —</option>
<?php foreach ($citoyens as $c): ?>
                    <option value="<?= $c->getIdUtilisateur() ?>"<?= $old['id_utilisateur'] === (string) $c->getIdUtilisateur() ? ' selected' : '' ?>><?= e($c->getPrenom() . ' ' . $c->getNom() . ' — ' . ($typeImpose !== '' ? 'compte n° ' . $c->getIdUtilisateur() : $c->getEmail())) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= $msg('id_utilisateur') ?>
                </div>
<?php endif; ?>

                <div class="admin-field admin-field--full<?= $err('type_ressource') ?>">
                  <label for="type_ressource">Fournisseur <span class="req" aria-hidden="true">*</span></label>
<?php if ($typeImpose !== ''): ?>
                  <!-- STEG / SONEDE : ressource imposée par le rôle (revérifiée en PHP) -->
                  <input type="hidden" name="type_ressource" value="<?= e($typeImpose) ?>">
                  <input class="admin-input" id="type_ressource" type="text" value="<?= e(FactureController::typeLabel($typeImpose)) ?>" readonly>
<?php else: ?>
                  <select class="admin-select" id="type_ressource" name="type_ressource" data-rules="required|in:electricite,eau" data-message-required="<?= e(FactureController::MESSAGES['type_ressource']['required']) ?>">
                    <option value="">— Choisir —</option>
<?php foreach (FactureModel::TYPES as $t): ?>
                    <option value="<?= $t ?>"<?= $old['type_ressource'] === $t ? ' selected' : '' ?>><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
                  </select>
<?php endif; ?>
                  <?= $msg('type_ressource') ?>
                </div>

                <div class="admin-field<?= $err('reference_abonnement') ?>">
                  <label for="reference_abonnement">Référence d'abonnement <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="reference_abonnement" name="reference_abonnement" type="text" inputmode="numeric" autocomplete="off"
                         value="<?= e($old['reference_abonnement']) ?>" placeholder="<?= e($ex[0]) ?>" aria-describedby="abo-hint"
                         data-rules="required" data-message-required="<?= e(FactureController::MESSAGES['reference_abonnement']['required']) ?>">
                  <p class="admin-field__hint" id="abo-hint" data-bill-abo-hint><?= e($ex[2]) ?></p>
                  <?= $msg('reference_abonnement') ?>
                </div>

                <div class="admin-field<?= $err('reference_paiement') ?>">
                  <label for="reference_paiement">Référence de paiement (talon) <span class="admin-field__optional">(facultatif)</span></label>
                  <input class="admin-input" id="reference_paiement" name="reference_paiement" type="text" inputmode="numeric" autocomplete="off"
                         value="<?= e($old['reference_paiement']) ?>" placeholder="<?= e($ex[1]) ?>" aria-describedby="paie-hint">
                  <p class="admin-field__hint" id="paie-hint" data-bill-paie-hint><?= e($ex[3]) ?></p>
                  <?= $msg('reference_paiement') ?>
                </div>

                <div class="admin-field<?= $err('periode_debut') ?>">
                  <label for="periode_debut">Début de la période <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="periode_debut" name="periode_debut" type="date" value="<?= e($old['periode_debut']) ?>"
                         data-rules="required|date" data-message-required="<?= e(FactureController::MESSAGES['periode_debut']['required']) ?>">
                  <?= $msg('periode_debut') ?>
                </div>

                <div class="admin-field<?= $err('periode_fin') ?>">
                  <label for="periode_fin">Fin de la période <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="periode_fin" name="periode_fin" type="date" value="<?= e($old['periode_fin']) ?>"
                         data-rules="required|date" data-message-required="<?= e(FactureController::MESSAGES['periode_fin']['required']) ?>">
                  <?= $msg('periode_fin') ?>
                </div>

                <div class="admin-field<?= $err('quantite') ?>">
                  <label for="quantite">Consommation (<span data-bill-unit><?= $unite ?></span>) <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="quantite" name="quantite" type="text" autocomplete="off" value="<?= e($old['quantite']) ?>" placeholder="ex. <?= $exQte ?>"
                         data-rules="required|max:20" data-message-required="<?= e(FactureController::MESSAGES['quantite']['required']) ?>" data-message-max="<?= e(FactureController::MESSAGES['quantite']['max']) ?>">
                  <?= $msg('quantite') ?>
                </div>

                <div class="admin-field<?= $err('montant') ?>">
                  <label for="montant">Montant (TND) <span class="req" aria-hidden="true">*</span></label>
                  <input class="admin-input" id="montant" name="montant" type="text" autocomplete="off" value="<?= e($old['montant']) ?>" placeholder="ex. 500 ou 500 TND"
                         data-rules="required|max:20" data-message-required="<?= e(FactureController::MESSAGES['montant']['required']) ?>" data-message-max="<?= e(FactureController::MESSAGES['montant']['max']) ?>">
                  <?= $msg('montant') ?>
                </div>

                <div class="admin-field<?= $err('date_echeance') ?>">
                  <label for="date_echeance">Date limite de paiement <span class="admin-field__optional">(facultatif)</span></label>
                  <input class="admin-input" id="date_echeance" name="date_echeance" type="date" value="<?= e($old['date_echeance']) ?>"
                         data-rules="date">
                  <?= $msg('date_echeance') ?>
                </div>

                <fieldset class="admin-field<?= $err('statut') ?>">
                  <legend>Statut <span class="req" aria-hidden="true">*</span></legend>
                  <div class="admin-choices">
<?php foreach (FactureModel::STATUTS as $s): ?>
                    <label class="admin-choice">
                      <input type="radio" name="statut" value="<?= $s ?>"<?= $old['statut'] === $s ? ' checked' : '' ?> data-rules="required|in:a_payer,payee" data-message-required="<?= e(FactureController::MESSAGES['statut']['required']) ?>">
                      <span><?= e(FactureController::statutLabel($s)) ?></span>
                    </label>
<?php endforeach; ?>
                  </div>
                  <?= $msg('statut') ?>
                </fieldset>

                <div class="admin-form__actions">
                  <a class="admin-btn admin-btn--outline" href="<?= url('admin-factures') ?>">Annuler</a>
                  <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="save"></span> Enregistrer</button>
                </div>
              </form>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="info"></span> À savoir</h2>
            </div>
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>Périmètre</dt><dd><?= $typeImpose !== '' ? 'Votre rôle ne gère que les factures ' . e(FactureController::typeLabel($typeImpose)) . '.' : 'Super admin : toutes les factures.' ?></dd></div>
                <div><dt>Références</dt><dd>Chiffres uniquement, espaces retirés. Abonnement (obligatoire) : STEG 9 chiffres, SONEDE 8 ou 9. Talon (facultatif) : STEG 16 chiffres, SONEDE 12 à 16, une seule fois par citoyen.</dd></div>
                <div><dt>Période</dt><dd>Depuis 2016, jamais dans le futur. STEG : 15 jours à 12 mois. SONEDE : 15 jours à 6 mois. Pas de chevauchement avec une autre facture du même fournisseur.</dd></div>
                <div><dt>Date limite de paiement</dt><dd>Entre la fin de la période et 90 jours après.</dd></div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = ($edition ? 'Modifier une facture' : 'Ajouter une facture') . ' — WASLA Admin';
$currentPage = 'admin-facture-form';
$pageScripts = ['js/factures-crud.js'];
require __DIR__ . '/../../layout.php';
