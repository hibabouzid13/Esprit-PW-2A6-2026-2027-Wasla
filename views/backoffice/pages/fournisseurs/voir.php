<?php
/**
 * Vue « Fiche fournisseur » — Back Office (super admin) — Module 4 (Bechir).
 * Variables reçues : $fournisseur (UserModel), $donnees (libellé => nombre), $demandesRecues (DemandeAssistanceModel[]).
 */
$id = $fournisseur->getIdUtilisateur();
$statutBadge = ['ouverte' => 'warn', 'prise_en_charge' => 'info', 'resolue' => 'ok', 'annulee' => 'muted'];
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="wrench"></span>
            <div>
              <h1><?= e($fournisseur->getNomOrganisation() ?? 'Fournisseur') ?></h1>
              <p><a href="<?= url('admin-fournisseurs') ?>">Comptes fournisseurs</a> › Fiche #<?= $id ?></p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-fournisseur-form', ['id' => $id]) ?>"><span data-icon="pencil"></span> Modifier</a>
            <form method="post" action="<?= url('admin-fournisseur-verifier') ?>">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="verifie" value="<?= $fournisseur->isCompteVerifie() ? '0' : '1' ?>">
              <input type="hidden" name="retour" value="admin-fournisseur-voir">
              <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="shield-check"></span> <?= $fournisseur->isCompteVerifie() ? 'Retirer la vérification' : 'Vérifier' ?></button>
            </form>
            <form method="post" action="<?= url('admin-fournisseur-statut') ?>">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="statut" value="<?= $fournisseur->getStatutCompte() === 'actif' ? 'suspendu' : 'actif' ?>">
              <input type="hidden" name="retour" value="admin-fournisseur-voir">
              <button class="admin-btn admin-btn--outline" type="submit"><span data-icon="lock"></span> <?= $fournisseur->getStatutCompte() === 'actif' ? 'Suspendre' : 'Réactiver' ?></button>
            </form>
<?php if ($donnees === []): ?>
            <form method="post" action="<?= url('admin-fournisseur-supprimer') ?>" data-confirm="Supprimer définitivement ce compte ?">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="retour" value="admin-fournisseur-voir">
              <button class="admin-btn admin-btn--danger" type="submit"><span data-icon="trash"></span> Supprimer</button>
            </form>
<?php endif; ?>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="heart-pulse"></span> Demandes d'intervention reçues (<?= count($demandesRecues) ?>)</h2>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table">
                <thead><tr><th>ID</th><th>Demande</th><th>Citoyen</th><th>Date souhaitée</th><th>Statut</th></tr></thead>
                <tbody>
<?php if ($demandesRecues === []): ?>
                  <tr><td colspan="5" class="admin-empty">Aucune demande reçue.</td></tr>
<?php endif; ?>
<?php foreach ($demandesRecues as $d): ?>
                  <tr>
                    <td class="admin-table__id">#<?= $d->getIdDemande() ?></td>
                    <td><a class="admin-table__main" href="<?= url('admin-demande', ['id' => $d->getIdDemande()]) ?>"><?= e($d->getTitre()) ?></a></td>
                    <td><?= e($d->getAuteurPrenom() . ' ' . $d->getAuteurNom()) ?></td>
                    <td><?= e(dateFr($d->getDateSouhaitee())) ?></td>
                    <td><span class="admin-badge admin-badge--<?= $statutBadge[$d->getStatut()] ?>"><?= e(DemandeAssistanceModel::STATUTS[$d->getStatut()]) ?></span></td>
                  </tr>
<?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>Responsable</dt><dd><?= e($fournisseur->getPrenom() . ' ' . $fournisseur->getNom()) ?></dd></div>
                <div><dt>Contact</dt><dd><?= e($fournisseur->getEmail()) ?><br><?= e($fournisseur->getTelephone() ?? '—') ?></dd></div>
                <div><dt>Spécialité</dt><dd><?= e(specialiteLabel($fournisseur->getSpecialite())) ?></dd></div>
                <div><dt>Adresse</dt><dd><?= e(($fournisseur->getAdresse() ? $fournisseur->getAdresse() . ', ' : '') . ($fournisseur->getNomZone() ?? '—')) ?></dd></div>
<?php if ($fournisseur->getDescription()): ?>
                <div><dt>Description</dt><dd><?= e($fournisseur->getDescription()) ?></dd></div>
<?php endif; ?>
                <div><dt>Vérification</dt><dd><span class="admin-badge admin-badge--<?= $fournisseur->isCompteVerifie() ? 'ok' : 'warn' ?>"><?= $fournisseur->isCompteVerifie() ? 'Vérifié' : 'En attente' ?></span></dd></div>
                <div><dt>Statut</dt><dd><span class="admin-badge admin-badge--<?= $fournisseur->getStatutCompte() === 'actif' ? 'ok' : 'danger' ?>"><?= $fournisseur->getStatutCompte() === 'actif' ? 'Actif' : 'Suspendu' ?></span></dd></div>
                <div><dt>Note moyenne</dt><dd><?= $fournisseur->getNoteMoyenne() !== null ? e(number_format($fournisseur->getNoteMoyenne(), 1, ',', '')) . ' / 5' : '—' ?></dd></div>
                <div><dt>Inscrit le</dt><dd><?= e(dateFr($fournisseur->getDateInscription(), true)) ?></dd></div>
                <div><dt>Dernière connexion</dt><dd><?= e(dateFr($fournisseur->getDerniereConnexion(), true)) ?></dd></div>
                <div>
                  <dt>Données liées</dt>
                  <dd>
<?php if ($donnees === []): ?>
                    Aucune : le compte peut être supprimé.
<?php else: ?>
<?php foreach ($donnees as $label => $n): ?>
                    <?= (int) $n ?> <?= e($label) ?><br>
<?php endforeach; ?>
                    <small>Suppression impossible : suspendez le compte.</small>
<?php endif; ?>
                  </dd>
                </div>
              </dl>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = 'Fiche fournisseur — WASLA Admin';
$currentPage = 'admin-fournisseur-voir';
require __DIR__ . '/../../layout.php';
