<?php
/**
 * Vue « Comptes fournisseurs » — Back Office (super admin) — Module 4 (Bechir).
 * Variables reçues : $fournisseurs (UserModel[]), $filters (q, specialite, statut, verifie).
 */
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="wrench"></span>
            <div>
              <h1>Comptes fournisseurs</h1>
              <p>Seuls les comptes actifs et vérifiés apparaissent dans l'annuaire public et peuvent répondre aux demandes.</p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-fournisseur-form') ?>"><span data-icon="plus"></span> Ajouter un fournisseur</a>
          </div>
        </div>

        <section class="admin-card">
          <form class="admin-toolbar" method="get" action="index.php">
            <input type="hidden" name="action" value="admin-fournisseurs">
            <label class="sr-only" for="f-q">Rechercher</label>
            <input class="admin-input" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom, e-mail, nom commercial…" style="max-width:260px;">
            <label class="sr-only" for="f-spec">Spécialité</label>
            <select class="admin-select" id="f-spec" name="specialite">
              <option value="">Toutes les spécialités</option>
<?php foreach (UserModel::SPECIALITES as $spec): ?>
              <option value="<?= e($spec) ?>"<?= $filters['specialite'] === $spec ? ' selected' : '' ?>><?= e(specialiteLabel($spec)) ?></option>
<?php endforeach; ?>
            </select>
            <label class="sr-only" for="f-verif">Vérification</label>
            <select class="admin-select" id="f-verif" name="verifie">
              <option value="">Vérifiés et non vérifiés</option>
              <option value="1"<?= $filters['verifie'] === '1' ? ' selected' : '' ?>>Vérifiés</option>
              <option value="0"<?= $filters['verifie'] === '0' ? ' selected' : '' ?>>En attente de vérification</option>
            </select>
            <label class="sr-only" for="f-statut">Statut</label>
            <select class="admin-select" id="f-statut" name="statut">
              <option value="">Tous les statuts</option>
              <option value="actif"<?= $filters['statut'] === 'actif' ? ' selected' : '' ?>>Actifs</option>
              <option value="suspendu"<?= $filters['statut'] === 'suspendu' ? ' selected' : '' ?>>Suspendus</option>
            </select>
            <button class="admin-btn admin-btn--primary" type="submit"><span data-icon="search"></span> Filtrer</button>
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-fournisseurs') ?>">Réinitialiser</a>
          </form>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr><th>ID</th><th>Nom commercial</th><th>Contact</th><th>Spécialité</th><th>Gouvernorat</th><th>Vérification</th><th>Statut</th><th>Actions</th></tr>
              </thead>
              <tbody>
<?php if ($fournisseurs === []): ?>
                <tr><td colspan="8" class="admin-empty">Aucun fournisseur ne correspond à ces filtres.</td></tr>
<?php endif; ?>
<?php foreach ($fournisseurs as $f): $id = $f->getIdUtilisateur(); ?>
                <tr>
                  <td class="admin-table__id">#<?= $id ?></td>
                  <td><a class="admin-table__main" href="<?= url('admin-fournisseur-voir', ['id' => $id]) ?>"><?= e($f->getNomOrganisation() ?? '—') ?></a><br><small><?= e($f->getPrenom() . ' ' . $f->getNom()) ?></small></td>
                  <td><?= e($f->getEmail()) ?><br><small><?= e($f->getTelephone() ?? '—') ?></small></td>
                  <td><?= e(specialiteLabel($f->getSpecialite())) ?></td>
                  <td><?= e($f->getNomZone() ?? '—') ?></td>
                  <td><span class="admin-badge admin-badge--<?= $f->isCompteVerifie() ? 'ok' : 'warn' ?>"><?= $f->isCompteVerifie() ? 'Vérifié' : 'En attente' ?></span></td>
                  <td><span class="admin-badge admin-badge--<?= $f->getStatutCompte() === 'actif' ? 'ok' : 'danger' ?>"><?= $f->getStatutCompte() === 'actif' ? 'Actif' : 'Suspendu' ?></span></td>
                  <td>
                    <div class="admin-actions">
                      <a class="admin-action" href="<?= url('admin-fournisseur-voir', ['id' => $id]) ?>" title="Fiche" aria-label="Fiche du fournisseur #<?= $id ?>"><span data-icon="eye"></span></a>
                      <a class="admin-action" href="<?= url('admin-fournisseur-form', ['id' => $id]) ?>" title="Modifier" aria-label="Modifier le fournisseur #<?= $id ?>"><span data-icon="pencil"></span></a>
                      <form method="post" action="<?= url('admin-fournisseur-verifier') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="verifie" value="<?= $f->isCompteVerifie() ? '0' : '1' ?>">
                        <button class="admin-action" type="submit" title="<?= $f->isCompteVerifie() ? 'Retirer la vérification' : 'Vérifier' ?>" aria-label="<?= $f->isCompteVerifie() ? 'Retirer la vérification' : 'Vérifier' ?> #<?= $id ?>"><span data-icon="<?= $f->isCompteVerifie() ? 'x' : 'shield-check' ?>"></span></button>
                      </form>
                      <form method="post" action="<?= url('admin-fournisseur-statut') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="statut" value="<?= $f->getStatutCompte() === 'actif' ? 'suspendu' : 'actif' ?>">
                        <button class="admin-action" type="submit" title="<?= $f->getStatutCompte() === 'actif' ? 'Suspendre' : 'Réactiver' ?>" aria-label="<?= $f->getStatutCompte() === 'actif' ? 'Suspendre' : 'Réactiver' ?> #<?= $id ?>"><span data-icon="<?= $f->getStatutCompte() === 'actif' ? 'lock' : 'check' ?>"></span></button>
                      </form>
                      <form method="post" action="<?= url('admin-fournisseur-supprimer') ?>" data-confirm="Supprimer définitivement <?= e($f->getEmail()) ?> ? (refusé s'il a des données)">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="admin-action admin-action--danger" type="submit" title="Supprimer" aria-label="Supprimer #<?= $id ?>"><span data-icon="trash"></span></button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="admin-table-foot"><span><?= count($fournisseurs) ?> fournisseur(s)</span></div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Comptes fournisseurs — WASLA Admin';
$currentPage = 'admin-fournisseurs';
require __DIR__ . '/../../layout.php';
