<?php
/**
 * Vue « Détail d'une demande d'assistance » — Back Office (admin) — Module 4 (Bechir).
 * Variables reçues : $demande, $reponses (ReponseModel[]), $auteur (?UserModel), $statutsPossibles (string[]).
 */
$statutBadge = ['ouverte' => 'warn', 'prise_en_charge' => 'info', 'resolue' => 'ok', 'annulee' => 'muted'];
$repBadge = ['proposee' => 'warn', 'acceptee' => 'ok', 'refusee' => 'muted'];
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="heart-pulse"></span>
            <div>
              <h1>Demande #<?= $demande->getIdDemande() ?></h1>
              <p><a href="<?= url('admin-demandes') ?>">Demandes d'assistance</a> › <?= e($demande->getTitre()) ?></p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <form method="post" action="<?= url('demande-supprimer') ?>" data-confirm="Supprimer cette demande et ses réponses ?">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $demande->getIdDemande() ?>">
              <input type="hidden" name="retour" value="admin-demande">
              <button class="admin-btn admin-btn--danger" type="submit"><span data-icon="trash"></span> Supprimer</button>
            </form>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="file-text"></span> <?= e($demande->getTitre()) ?></h2>
              <span class="admin-badge admin-badge--<?= $statutBadge[$demande->getStatut()] ?>"><?= e(DemandeAssistanceModel::STATUTS[$demande->getStatut()]) ?></span>
            </div>
            <div class="admin-card__body">
              <p style="white-space:pre-line;margin-top:0;"><?= e($demande->getDescription()) ?></p>

              <h3 style="margin:24px 0 8px;font-size:1rem;">Réponses (<?= count($reponses) ?>)</h3>
<?php if ($reponses === []): ?>
              <p class="admin-empty" style="text-align:start;padding:0;">Aucune réponse pour le moment.</p>
<?php else: ?>
              <div class="admin-table-wrap">
                <table class="admin-table">
                  <thead><tr><th>Auteur</th><th>Message</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                  <tbody>
<?php foreach ($reponses as $r): ?>
                    <tr>
                      <td><?= e($r->getAuteurPrenom() . ' ' . $r->getAuteurNom()) ?><br><small><?= e(roleLabel((string) $r->getAuteurRole())) ?></small></td>
                      <td style="white-space:pre-line;"><?= e($r->getMessage()) ?><?= $r->getInstallationAdresse() ? "\n<small>Recharge solaire : " . e($r->getInstallationAdresse()) . '</small>' : '' ?></td>
                      <td><span class="admin-badge admin-badge--<?= $repBadge[$r->getStatut()] ?? 'muted' ?>"><?= e(ReponseModel::STATUTS[$r->getStatut()] ?? '') ?></span></td>
                      <td><?= e(dateFr($r->getDateReponse(), true)) ?></td>
                      <td>
                        <form method="post" action="<?= url('reponse-supprimer') ?>" data-confirm="Supprimer cette réponse ?">
                          <?= csrfField() ?>
                          <input type="hidden" name="id_reponse" value="<?= $r->getIdReponse() ?>">
                          <input type="hidden" name="id" value="<?= $demande->getIdDemande() ?>">
                          <input type="hidden" name="retour" value="admin-demande">
                          <button class="admin-action admin-action--danger" type="submit" title="Supprimer" aria-label="Supprimer la réponse"><span data-icon="trash"></span></button>
                        </form>
                      </td>
                    </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
<?php endif; ?>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>Type</dt><dd><?= $demande->getCategorie() === DemandeAssistanceModel::CATEGORIE_SANTE ? 'Priorité santé (signalement)' : ($demande->getIdFournisseur() === null ? 'Relais — ' . e(DemandeAssistanceModel::SENS[$demande->getSens()]) : 'Intervention : ' . e($demande->getFournisseurNom() ?? '—')) ?></dd></div>
                <div><dt>Catégorie</dt><dd><?= e(DemandeAssistanceModel::TOUTES_CATEGORIES[$demande->getCategorie()] ?? '') ?></dd></div>
                <div><dt>Auteur</dt><dd><?= e($demande->getAuteurPrenom() . ' ' . $demande->getAuteurNom()) ?><?= $auteur ? '<br>' . e($auteur->getEmail()) : '' ?></dd></div>
                <div><dt>Zone</dt><dd><?= e(($demande->getQuartier() ? $demande->getQuartier() . ', ' : '') . $demande->getNomZone()) ?></dd></div>
<?php if ($demande->getSens() === 'demande'): ?>
                <div><dt>Urgence</dt><dd><?= e(DemandeAssistanceModel::URGENCES[$demande->getUrgence()] ?? '') ?></dd></div>
<?php endif; ?>
<?php if ($demande->getDateSouhaitee()): ?>
                <div><dt>Date souhaitée</dt><dd><?= e(dateFr($demande->getDateSouhaitee())) ?><?= $demande->getCreneau() ? ' · ' . e(DemandeAssistanceModel::CRENEAUX[$demande->getCreneau()]) : '' ?></dd></div>
<?php endif; ?>
<?php if ($demande->getTelephone()): ?>
                <div><dt>Téléphone</dt><dd><?= e($demande->getTelephone()) ?></dd></div>
<?php endif; ?>
                <div><dt>Créée le</dt><dd><?= e(dateFr($demande->getDateCreation(), true)) ?></dd></div>
              </dl>

              <form class="admin-field" method="post" action="<?= url('demande-statut') ?>" style="margin-top:20px;">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $demande->getIdDemande() ?>">
                <input type="hidden" name="retour" value="admin-demande">
                <label for="d-statut">Changer le statut</label>
                <select class="admin-select" id="d-statut" name="statut">
<?php foreach ($statutsPossibles as $s): ?>
                  <option value="<?= e($s) ?>"><?= e(DemandeAssistanceModel::STATUTS[$s]) ?></option>
<?php endforeach; ?>
                </select>
                <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="check"></span> Appliquer</button>
              </form>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = 'Demande #' . $demande->getIdDemande() . ' — WASLA Admin';
$currentPage = 'admin-demande';
require __DIR__ . '/../../layout.php';
