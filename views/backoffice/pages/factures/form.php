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
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-factures') ?>">Factures</a><span>/</span><span><?= $edition ? 'Modifier' : 'Ajouter' ?></span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="file-text"></span>
              <div>
                <h1><?= $edition ? 'Modifier la facture ' . e($facture->getReference()) : 'Ajouter une facture' ?></h1>
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
                  <label for="id_utilisateur">Citoyen titulaire <span class="req">*</span></label>
                  <select class="admin-select" id="id_utilisateur" name="id_utilisateur" data-rules="required|int" data-message-required="Choisissez le citoyen titulaire de la facture.">
                    <option value="">— Choisir un citoyen —</option>
<?php foreach ($citoyens as $c): ?>
                    <option value="<?= $c->getIdUtilisateur() ?>"<?= $old['id_utilisateur'] === (string) $c->getIdUtilisateur() ? ' selected' : '' ?>><?= e($c->getPrenom() . ' ' . $c->getNom() . ' — ' . $c->getEmail()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= $msg('id_utilisateur') ?>
                </div>
<?php endif; ?>

                <div class="admin-field<?= $err('type_ressource') ?>">
                  <label for="type_ressource">Ressource <span class="req">*</span></label>
<?php if ($typeImpose !== ''): ?>
                  <!-- STEG / SONEDE : ressource imposée par le rôle (revérifiée en PHP) -->
                  <input type="hidden" name="type_ressource" value="<?= e($typeImpose) ?>">
                  <input class="admin-input" id="type_ressource" type="text" value="<?= e(FactureController::typeLabel($typeImpose)) ?>" readonly>
<?php else: ?>
                  <select class="admin-select" id="type_ressource" name="type_ressource" data-rules="required|in:electricite,eau" data-message-required="Choisissez la ressource.">
                    <option value="">— Choisir —</option>
<?php foreach (FactureModel::TYPES as $t): ?>
                    <option value="<?= $t ?>"<?= $old['type_ressource'] === $t ? ' selected' : '' ?>><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
                  </select>
<?php endif; ?>
                  <?= $msg('type_ressource') ?>
                </div>

                <div class="admin-field<?= $err('reference') ?>">
                  <label for="reference">Référence <span class="req">*</span></label>
                  <?php $typeRef = $typeImpose !== '' ? $typeImpose : $old['type_ressource'];
                        $exRef = ['electricite' => 'STG-20260918', 'eau' => 'SND-20260930'][$typeRef] ?? ''; ?>
                  <input class="admin-input" id="reference" name="reference" type="text" autocomplete="off" value="<?= e($old['reference']) ?>"
                         placeholder="<?= $exRef !== '' ? 'ex. ' . $exRef : "Choisissez d'abord la ressource" ?>"
                         data-rules="required|max:30" data-message-required="Indiquez la référence de la facture." data-message-max="La référence est trop longue (30 caractères au plus).">
                  <p class="admin-field__hint" data-bill-ref-hint><?= $exRef !== '' ? 'Format : ' . substr($exRef, 0, 3) . '- suivi de chiffres (ex. ' . $exRef . ').' : 'STG-chiffres pour la STEG, SND-chiffres pour la SONEDE.' ?></p>
                  <?= $msg('reference') ?>
                </div>

                <div class="admin-field<?= $err('periode_debut') ?>">
                  <label for="periode_debut">Début de la période <span class="req">*</span></label>
                  <input class="admin-input" id="periode_debut" name="periode_debut" type="date" value="<?= e($old['periode_debut']) ?>"
                         data-rules="required|date" data-message-required="Indiquez le début de la période.">
                  <?= $msg('periode_debut') ?>
                </div>

                <div class="admin-field<?= $err('periode_fin') ?>">
                  <label for="periode_fin">Fin de la période <span class="req">*</span></label>
                  <input class="admin-input" id="periode_fin" name="periode_fin" type="date" value="<?= e($old['periode_fin']) ?>"
                         data-rules="required|date" data-message-required="Indiquez la fin de la période.">
                  <?= $msg('periode_fin') ?>
                </div>

                <div class="admin-field<?= $err('quantite') ?>">
                  <label for="quantite">Consommation (<span data-bill-unit>kWh</span>) <span class="req">*</span></label>
                  <input class="admin-input" id="quantite" name="quantite" type="text" autocomplete="off" value="<?= e($old['quantite']) ?>" placeholder="ex. 455 ou 455 kWh"
                         data-rules="required|max:20" data-message-required="Indiquez la consommation." data-message-max="Valeur trop longue.">
                  <?= $msg('quantite') ?>
                </div>

                <div class="admin-field<?= $err('montant') ?>">
                  <label for="montant">Montant (TND) <span class="req">*</span></label>
                  <input class="admin-input" id="montant" name="montant" type="text" autocomplete="off" value="<?= e($old['montant']) ?>" placeholder="ex. 500 ou 500 TND"
                         data-rules="required|max:20" data-message-required="Indiquez le montant." data-message-max="Valeur trop longue.">
                  <?= $msg('montant') ?>
                </div>

                <div class="admin-field<?= $err('date_echeance') ?>">
                  <label for="date_echeance">Date limite de paiement</label>
                  <input class="admin-input" id="date_echeance" name="date_echeance" type="date" value="<?= e($old['date_echeance']) ?>"
                         data-rules="date">
                  <p class="admin-field__hint">Facultatif.</p>
                  <?= $msg('date_echeance') ?>
                </div>

                <fieldset class="admin-field<?= $err('statut') ?>">
                  <legend>Statut <span class="req">*</span></legend>
                  <div class="admin-choices">
<?php foreach (FactureModel::STATUTS as $s): ?>
                    <label class="admin-choice">
                      <input type="radio" name="statut" value="<?= $s ?>"<?= $old['statut'] === $s ? ' checked' : '' ?> data-rules="required|in:a_payer,payee" data-message-required="Choisissez le statut.">
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
                <div><dt>Référence</dt><dd>STG-chiffres (STEG) ou SND-chiffres (SONEDE), unique par citoyen.</dd></div>
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
