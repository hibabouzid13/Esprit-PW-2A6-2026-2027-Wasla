<?php
/**
 * Vue « Demandes d'assistance » — Back Office (admin) — Module 4 (Bechir).
 * Variables reçues : $demandes (DemandeAssistanceModel[]), $countByStatut, $filters, $gouvernorats.
 */
$statutBadge = ['ouverte' => 'warn', 'prise_en_charge' => 'info', 'resolue' => 'ok', 'annulee' => 'muted'];
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="heart-pulse"></span>
            <div>
              <h1>Demandes d'assistance</h1>
              <p>Publications du Relais de voisinage, demandes d'intervention adressées aux fournisseurs et signalements Priorité santé</p>
            </div>
          </div>
        </div>

        <section class="admin-kpis" aria-label="Demandes par statut">
<?php foreach (DemandeAssistanceModel::STATUTS as $key => $label): ?>
          <a class="admin-card admin-kpi" href="<?= url('admin-demandes', ['statut' => $key]) ?>" style="text-decoration:none;color:inherit;">
            <span class="admin-kpi__icon" data-icon="<?= ['ouverte' => 'circle-alert', 'prise_en_charge' => 'clock', 'resolue' => 'circle-check', 'annulee' => 'x'][$key] ?>"></span>
            <div>
              <p class="admin-kpi__label"><?= e($label) ?></p>
              <div class="admin-kpi__row"><p class="admin-kpi__value"><?= (int) $countByStatut[$key] ?></p></div>
            </div>
          </a>
<?php endforeach; ?>
        </section>

        <section class="admin-card">
          <form class="admin-toolbar" method="get" action="index.php">
            <input type="hidden" name="action" value="admin-demandes">
            <label class="sr-only" for="f-q">Rechercher</label>
            <input class="admin-input" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Titre, description, quartier…" style="max-width:260px;">
            <label class="sr-only" for="f-type">Type</label>
            <select class="admin-select" id="f-type" name="type">
              <option value="">Tous les types</option>
              <option value="relais"<?= $filters['type'] === 'relais' ? ' selected' : '' ?>>Relais de voisinage</option>
              <option value="fournisseur"<?= $filters['type'] === 'fournisseur' ? ' selected' : '' ?>>Intervention fournisseur</option>
              <option value="sante"<?= $filters['type'] === 'sante' ? ' selected' : '' ?>>Priorité santé</option>
            </select>
            <label class="sr-only" for="f-statut">Statut</label>
            <select class="admin-select" id="f-statut" name="statut">
              <option value="">Tous les statuts</option>
<?php foreach (DemandeAssistanceModel::STATUTS as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <label class="sr-only" for="f-cat">Catégorie</label>
            <select class="admin-select" id="f-cat" name="categorie">
              <option value="">Toutes les catégories</option>
<?php foreach (DemandeAssistanceModel::TOUTES_CATEGORIES as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $filters['categorie'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <label class="sr-only" for="f-zone">Gouvernorat</label>
            <select class="admin-select" id="f-zone" name="id_zone">
              <option value="">Tous les gouvernorats</option>
<?php foreach ($gouvernorats as $g): ?>
              <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $filters['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
            </select>
            <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="search"></span> Filtrer</button>
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-demandes') ?>">Réinitialiser</a>
          </form>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr><th>ID</th><th>Demande</th><th>Type</th><th>Auteur</th><th>Zone</th><th>Urgence</th><th>Statut</th><th>Réponses</th><th>Date</th><th>Actions</th></tr>
              </thead>
              <tbody>
<?php if ($demandes === []): ?>
                <tr><td colspan="10" class="admin-empty">Aucune demande ne correspond à ces filtres.</td></tr>
<?php endif; ?>
<?php foreach ($demandes as $d): ?>
                <tr>
                  <td class="admin-table__id">#<?= $d->getIdDemande() ?></td>
                  <td>
                    <a class="admin-table__main" href="<?= url('admin-demande', ['id' => $d->getIdDemande()]) ?>"><?= e($d->getTitre()) ?></a><br>
                    <small><?= e(DemandeAssistanceModel::TOUTES_CATEGORIES[$d->getCategorie()] ?? '') ?></small>
                  </td>
                  <td>
<?php if ($d->getCategorie() === DemandeAssistanceModel::CATEGORIE_SANTE): ?>
                    <span class="admin-badge admin-badge--warn">Priorité santé</span>
<?php elseif ($d->getIdFournisseur() === null): ?>
                    <?= e(DemandeAssistanceModel::SENS[$d->getSens()]) ?>
<?php else: ?>
                    Intervention<br><small><?= e($d->getFournisseurNom() ?? '—') ?></small>
<?php endif; ?>
                  </td>
                  <td><?= e($d->getAuteurPrenom() . ' ' . $d->getAuteurNom()) ?></td>
                  <td><?= e(($d->getQuartier() ? $d->getQuartier() . ', ' : '') . $d->getNomZone()) ?></td>
                  <td><?= $d->getSens() === 'demande' ? e(DemandeAssistanceModel::URGENCES[$d->getUrgence()] ?? '') : '—' ?></td>
                  <td><span class="admin-badge admin-badge--<?= $statutBadge[$d->getStatut()] ?>"><?= e(DemandeAssistanceModel::STATUTS[$d->getStatut()]) ?></span></td>
                  <td><?= $d->getNbReponses() ?></td>
                  <td><?= e(dateFr($d->getDateCreation())) ?></td>
                  <td>
                    <div class="admin-actions">
                      <a class="admin-action" href="<?= url('admin-demande', ['id' => $d->getIdDemande()]) ?>" title="Voir et gérer" aria-label="Voir la demande #<?= $d->getIdDemande() ?>"><span data-icon="eye"></span></a>
                      <form method="post" action="<?= url('demande-supprimer') ?>" data-confirm="Supprimer la demande « <?= e($d->getTitre()) ?> » et ses réponses ?">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $d->getIdDemande() ?>">
                        <input type="hidden" name="retour" value="admin-demandes">
                        <button class="admin-action admin-action--danger" type="submit" title="Supprimer" aria-label="Supprimer la demande #<?= $d->getIdDemande() ?>"><span data-icon="trash"></span></button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="admin-table-foot"><span><?= count($demandes) ?> demande(s)</span></div>
        </section>
<?php
$content     = ob_get_clean();
$title       = "Demandes d'assistance — WASLA Admin";
$currentPage = 'admin-demandes';
require __DIR__ . '/../../layout.php';
