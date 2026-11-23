<?php
/**
 * Vue : modifier une demande d'assistance — Front Office — Module 4 (Bechir).
 * Sert aux deux types : publication du Relais (id_fournisseur NULL) et demande d'intervention à un fournisseur.
 *
 * Variables reçues (routes/module4.php → 'demande-modifier') :
 *   $demande (DemandeAssistanceModel), $errors, $old, $gouvernorats, $retour ('relais' | 'fournisseurs').
 */
$isRelais = $demande->getIdFournisseur() === null;
$rules = $isRelais ? DemandeAssistanceController::relaisRules(false) : DemandeAssistanceController::fournisseurRules(false);
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url($retour) ?>"><?= $isRelais ? 'Relais de voisinage' : 'Fournisseurs' ?></a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Modifier</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow"><?= $isRelais ? 'Relais de voisinage' : "Demande d'intervention · " . e($demande->getFournisseurNom() ?? '') ?></p>
            <h1>Modifier ma demande</h1>
            <p>Une demande peut être modifiée tant qu'elle est encore ouverte.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container" style="max-width:760px;">
        <form class="card card__body form-grid" method="post" action="<?= url('demande-modifier', ['id' => $demande->getIdDemande()]) ?>" data-validate<?= $isRelais ? ' data-sens-form' : '' ?>>
          <?= csrfField() ?>
<?php if (isset($errors['form'])): ?>
          <p class="flash flash--error"><?= e($errors['form']) ?></p>
<?php endif; ?>
<?php if ($isRelais): ?>
          <div class="field">
            <label>Type de publication *</label>
            <div class="svc-radio-row">
<?php foreach (DemandeAssistanceModel::SENS as $key => $label): ?>
              <label class="svc-radio"><input type="radio" name="sens" value="<?= e($key) ?>" data-rules="<?= e($rules['sens']) ?>"<?= $old['sens'] === $key ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
<?php endforeach; ?>
            </div>
            <?= fieldError($errors, 'sens') ?>
          </div>
<?php endif; ?>
          <div class="field">
            <label for="d-cat"><?= $isRelais ? 'Catégorie' : 'Type de problème' ?> *</label>
            <select class="select<?= invalidClass($errors, 'categorie') ?>" id="d-cat" name="categorie" data-rules="<?= e($rules['categorie']) ?>">
              <option value="">Choisir…</option>
<?php foreach (DemandeAssistanceModel::CATEGORIES as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $old['categorie'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'categorie') ?>
          </div>
          <div class="field">
            <label for="d-titre"><?= $isRelais ? 'Titre' : 'Objet' ?> *</label>
            <input class="input<?= invalidClass($errors, 'titre') ?>" id="d-titre" name="titre" value="<?= e($old['titre']) ?>" data-rules="<?= e($rules['titre']) ?>">
            <?= fieldError($errors, 'titre') ?>
          </div>
          <div class="field">
            <label for="d-desc">Description *</label>
            <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="d-desc" name="description" data-rules="<?= e($rules['description']) ?>"><?= e($old['description']) ?></textarea>
            <?= fieldError($errors, 'description') ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="d-zone">Gouvernorat *</label>
              <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="d-zone" name="id_zone" data-rules="<?= e($rules['id_zone']) ?>">
                <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'id_zone') ?>
            </div>
            <div class="field">
              <label for="d-quartier">Quartier *</label>
              <input class="input<?= invalidClass($errors, 'quartier') ?>" id="d-quartier" name="quartier" value="<?= e($old['quartier']) ?>" data-rules="<?= e($rules['quartier']) ?>">
              <?= fieldError($errors, 'quartier') ?>
            </div>
          </div>
          <div class="field"<?= $isRelais ? ' data-when-sens="demande"' : '' ?>>
            <label>Niveau d'urgence *</label>
            <div class="svc-radio-row">
<?php foreach (DemandeAssistanceModel::URGENCES as $key => $label): ?>
              <label class="svc-radio"><input type="radio" name="urgence" value="<?= e($key) ?>" data-rules="<?= e($rules['urgence']) ?>"<?= $old['urgence'] === $key ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
<?php endforeach; ?>
            </div>
            <?= fieldError($errors, 'urgence') ?>
          </div>
<?php if (!$isRelais): ?>
          <div class="form-row">
            <div class="field">
              <label for="d-date">Date souhaitée *</label>
              <input class="input<?= invalidClass($errors, 'date_souhaitee') ?>" id="d-date" name="date_souhaitee" type="date" value="<?= e($old['date_souhaitee']) ?>" data-rules="<?= e($rules['date_souhaitee']) ?>">
              <?= fieldError($errors, 'date_souhaitee') ?>
            </div>
            <div class="field">
              <label for="d-tel">Téléphone de contact *</label>
              <input class="input<?= invalidClass($errors, 'telephone') ?>" id="d-tel" name="telephone" inputmode="tel" value="<?= e($old['telephone']) ?>" data-rules="<?= e($rules['telephone']) ?>">
              <?= fieldError($errors, 'telephone') ?>
            </div>
          </div>
<?php endif; ?>
          <div class="field"<?= $isRelais ? ' data-when-sens="offre"' : '' ?>>
            <label for="d-creneau"><?= $isRelais ? 'Disponibilité' : 'Créneau' ?></label>
            <select class="select<?= invalidClass($errors, 'creneau') ?>" id="d-creneau" name="creneau" data-rules="<?= e($rules['creneau']) ?>">
              <option value=""><?= $isRelais ? 'À convenir' : 'Indifférent' ?></option>
<?php foreach (DemandeAssistanceModel::CRENEAUX as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $old['creneau'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'creneau') ?>
          </div>
          <div class="svc-actions" style="justify-content:flex-end;">
            <a class="btn btn--outline btn--sm" href="<?= url($retour) ?>">Annuler</a>
            <button class="btn btn--primary btn--sm" type="submit" data-loading="Enregistrement…"><span data-icon="check"></span>Enregistrer</button>
          </div>
        </form>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Modifier ma demande — WASLA';
$currentPage = $retour;
$pageCss     = ['pages', 'services', 'assistance'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/services/assistance.js'];
require __DIR__ . '/../../layout.php';
