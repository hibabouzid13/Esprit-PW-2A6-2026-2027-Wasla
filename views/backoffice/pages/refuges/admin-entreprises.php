<?php
/**
 * Vue « Comptes entreprises » — Back Office (admin) — Module 3 (Louay).
 * Variables reçues : $entreprises (UserModel[] de rôle entreprise), $nbRefuges (id => nombre),
 *                    $stats (total, a_valider, verifiees, suspendues), $filtre (filtre courant),
 *                    $recherche (texte cherché dans la raison sociale, le responsable, l'e-mail ou le matricule).
 */
$filtres = [
    ''           => 'Toutes (' . $stats['total'] . ')',
    'a_valider'  => 'À valider (' . $stats['a_valider'] . ')',
    'verifiees'  => 'Validées (' . $stats['verifiees'] . ')',
    'suspendues' => 'Suspendues (' . $stats['suspendues'] . ')',
];
/** Petit formulaire POST d'action (jeton CSRF + confirmation éventuelle). */
$actionForm = static function (int $id, string $op, string $icon, string $title, string $confirm = '', bool $danger = false): string {
    return '<form method="post" action="' . url('admin-entreprise-action') . '"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . csrfField()
        . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="op" value="' . e($op) . '">'
        . '<button class="admin-action' . ($danger ? ' admin-action--danger' : '') . '" type="submit" title="' . e($title) . '" aria-label="' . e($title) . '"><span data-icon="' . e($icon) . '"></span></button>'
        . '</form>';
};
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="building-2"></span>
            <div>
              <h1>Comptes entreprises</h1>
              <p>Entreprise est un rôle de l'utilisateur : validez le matricule fiscal avant d'autoriser la publication de refuges.</p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-entreprise-form') ?>"><span data-icon="plus"></span> Ajouter une entreprise</a>
          </div>
        </div>

        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="users"></span> Entreprises (<?= count($entreprises) ?>)</h2>
          </div>
          <div class="admin-card__body">
            <div class="admin-toolbar">
              <form class="admin-search" role="search" method="get" action="index.php">
                <input type="hidden" name="action" value="admin-entreprises">
<?php if ($filtre !== ''): ?>
                <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
<?php endif; ?>
                <span class="admin-search__icon" data-icon="search"></span>
                <input type="search" name="q" value="<?= e($recherche) ?>" placeholder="Nom, e-mail ou matricule…" aria-label="Rechercher une entreprise">
              </form>
<?php foreach ($filtres as $key => $label):
    $params = ($key === '' ? [] : ['filtre' => $key]) + ($recherche !== '' ? ['q' => $recherche] : []); ?>
              <a class="admin-btn <?= $key === $filtre ? 'admin-btn--primary' : 'admin-btn--outline' ?>" href="<?= url('admin-entreprises', $params) ?>"<?= $key === $filtre ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
<?php if ($recherche !== ''): ?>
              <a class="admin-btn admin-btn--outline" href="<?= url('admin-entreprises', $filtre === '' ? [] : ['filtre' => $filtre]) ?>">Effacer la recherche</a>
<?php endif; ?>
            </div>
          </div>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr><th>ID</th><th>Entreprise</th><th>Matricule fiscal</th><th>Responsable</th><th>Gouvernorat</th><th>Refuges</th><th>Validation</th><th>Compte</th><th>Actions</th></tr>
              </thead>
              <tbody>
<?php if ($entreprises === []): ?>
                <tr><td colspan="9" class="admin-empty">Aucun compte entreprise<?= $recherche !== '' ? ' pour « ' . e($recherche) . ' »' : ($filtre !== '' ? ' dans cette catégorie' : '') ?>.</td></tr>
<?php endif; ?>
<?php foreach ($entreprises as $u): $id = $u->getIdUtilisateur(); ?>
                <tr>
                  <td class="admin-table__id">#<?= $id ?></td>
                  <td><a class="admin-table__main" href="<?= url('admin-entreprise-voir', ['id' => $id]) ?>"><?= e($u->getNomOrganisation() ?? '—') ?></a><br><small><?= e($u->getSecteurActivite() ?? '') ?></small></td>
                  <td><?= e($u->getMatriculeFiscal() ?? '—') ?></td>
                  <td><?= e($u->getPrenom() . ' ' . $u->getNom()) ?><br><small><?= e($u->getEmail()) ?> · <?= e($u->getTelephone() ?? '') ?></small></td>
                  <td><?= e($u->getNomZone() ?? '—') ?></td>
                  <td><?= $nbRefuges[$id] ?? 0 ?></td>
                  <td><span class="admin-badge admin-badge--<?= $u->isCompteVerifie() ? 'ok' : 'warn' ?>"><?= $u->isCompteVerifie() ? 'Validée' : 'À valider' ?></span></td>
                  <td><span class="admin-badge admin-badge--<?= $u->getStatutCompte() === 'actif' ? 'info' : 'danger' ?>"><?= $u->getStatutCompte() === 'actif' ? 'Actif' : 'Suspendu' ?></span></td>
                  <td>
                    <div class="admin-actions">
                      <?= $u->isCompteVerifie()
                          ? $actionForm($id, 'devalider', 'shield-check', 'Retirer la validation', 'Retirer la validation ? Ses refuges seront masqués de la carte.')
                          : $actionForm($id, 'valider', 'check', 'Valider le compte') ?>
                      <a class="admin-action" href="<?= url('admin-entreprise-voir', ['id' => $id]) ?>" title="Voir la fiche" aria-label="Voir la fiche de <?= e($u->getNomOrganisation() ?? '') ?>"><span data-icon="eye"></span></a>
                      <a class="admin-action" href="<?= url('admin-entreprise-form', ['id' => $id]) ?>" title="Modifier" aria-label="Modifier <?= e($u->getNomOrganisation() ?? '') ?>"><span data-icon="settings"></span></a>
                      <?= $u->getStatutCompte() === 'actif'
                          ? $actionForm($id, 'suspendre', 'lock', 'Suspendre le compte', 'Suspendre ce compte ? Il ne pourra plus se connecter.')
                          : $actionForm($id, 'reactiver', 'refresh', 'Réactiver le compte') ?>
                      <?= $actionForm($id, 'supprimer', 'x', 'Supprimer le compte', 'Supprimer définitivement ce compte entreprise ? La suppression est refusée s\'il a des données (refuges, réservations…) : suspendez-le alors.', true) ?>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Comptes entreprises — WASLA Admin';
$currentPage = 'admin-entreprises';
require __DIR__ . '/../../layout.php';
