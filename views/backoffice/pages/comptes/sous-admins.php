<?php
/**
 * Vue : Back Office : « Comptes sous-admins » STEG et SONEDE — Module 1 (Houssem). Super admin uniquement.
 * Variables reçues :
 *   $comptes    UserModel[]        les sous-admins (rôles steg et sonede uniquement)
 *   $parStatut  array<string,int>  nombre de sous-admins par statut
 * Recherche et filtres (rôle, statut) : data-table-search / data-table-filter (admin.js).
 */
$statutBadges = ['actif' => ['Actif', 'ok'], 'suspendu' => ['Suspendu', 'danger']];
$nbSteg   = count(array_filter($comptes, fn (UserModel $u) => $u->getRole() === 'steg'));
$nbSonede = count($comptes) - $nbSteg;

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Interruptions &amp; alertes</span><span>/</span><span>Comptes sous-admins</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="users"></span>
              <div>
                <h1>Comptes sous-admins</h1>
                <p>Agents STEG (électricité) et SONEDE (eau) : créés et gérés par le super admin uniquement</p>
              </div>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-sous-admin-form') ?>"><span data-icon="user-plus"></span> Ajouter un sous-admin</a>
          </div>
        </div>

        <section class="admin-kpis" aria-label="Sous-admins">
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--ok" data-icon="circle-check"></span>
            <div><p class="admin-kpi__label">Comptes actifs</p><p class="admin-kpi__value"><?= $parStatut['actif'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon" data-icon="lock"></span>
            <div><p class="admin-kpi__label">Comptes suspendus</p><p class="admin-kpi__value"><?= $parStatut['suspendu'] ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--outage" data-icon="zap"></span>
            <div><p class="admin-kpi__label">Agents STEG</p><p class="admin-kpi__value"><?= $nbSteg ?></p></div>
          </article>
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon" data-icon="droplet"></span>
            <div><p class="admin-kpi__label">Agents SONEDE</p><p class="admin-kpi__value"><?= $nbSonede ?></p></div>
          </article>
        </section>

        <section class="admin-card">
          <div class="admin-toolbar">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" placeholder="Rechercher (nom, e-mail, organisation…)" aria-label="Rechercher un sous-admin" data-table-search>
            </div>
            <select class="admin-select" aria-label="Filtrer par rôle" data-table-filter="role">
              <option value="">STEG et SONEDE</option>
              <option value="steg">STEG</option>
              <option value="sonede">SONEDE</option>
            </select>
            <select class="admin-select" aria-label="Filtrer par statut" data-table-filter="statut">
              <option value="">Tous les statuts</option>
              <option value="actif">Actif</option>
              <option value="suspendu">Suspendu</option>
            </select>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table" data-admin-table>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Compte</th>
                  <th scope="col">Rôle</th>
                  <th scope="col">Gouvernorat</th>
                  <th scope="col">Statut</th>
                  <th scope="col">Dernière connexion</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($comptes as $u):
        $id     = $u->getIdUtilisateur();
        $statut = $u->getStatutCompte();
        [$statutLabel, $statutClass] = $statutBadges[$statut] ?? [$statut, 'muted']; ?>
                <tr data-role="<?= e($u->getRole()) ?>" data-statut="<?= e($statut) ?>">
                  <td class="admin-table__id"><?= $id ?></td>
                  <td class="admin-table__main"><a href="<?= url('admin-sous-admin-voir', ['id' => $id]) ?>"><?= e($u->getPrenom() . ' ' . $u->getNom()) ?></a><br><small><?= e($u->getEmail()) ?></small><?php if ($u->getNomOrganisation()): ?><br><small><?= e($u->getNomOrganisation()) ?></small><?php endif; ?></td>
                  <td><span class="admin-badge admin-badge--info"><?= e(roleLabel($u->getRole())) ?></span></td>
                  <td><?= e($u->getNomZone() ?? '—') ?></td>
                  <td><span class="admin-badge admin-badge--<?= $statutClass ?>"><?= e($statutLabel) ?></span></td>
                  <td><?= $u->getDerniereConnexion() ? e(dateFr($u->getDerniereConnexion(), true)) : 'Jamais connecté' ?></td>
                  <td><div class="admin-actions">
                    <a class="admin-action" href="<?= url('admin-sous-admin-voir', ['id' => $id]) ?>" aria-label="Voir la fiche" title="Fiche"><span data-icon="eye"></span></a>
                    <a class="admin-action" href="<?= url('admin-sous-admin-form', ['id' => $id]) ?>" aria-label="Modifier" title="Modifier"><span data-icon="pencil"></span></a>
                    <form method="post" action="<?= url('admin-sous-admin-statut') ?>" data-confirm="<?= $statut === 'actif' ? 'Suspendre ce compte ? Il ne pourra plus se connecter (ses données sont conservées).' : 'Réactiver ce compte ?' ?>" style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_utilisateur" value="<?= $id ?>">
                      <input type="hidden" name="statut" value="<?= $statut === 'actif' ? 'suspendu' : 'actif' ?>">
                      <button class="admin-action" type="submit" aria-label="<?= $statut === 'actif' ? 'Suspendre' : 'Réactiver' ?>" title="<?= $statut === 'actif' ? 'Suspendre' : 'Réactiver' ?>"><span data-icon="<?= $statut === 'actif' ? 'lock' : 'circle-check' ?>"></span></button>
                    </form>
                    <form method="post" action="<?= url('admin-sous-admin-supprimer') ?>" data-confirm="Supprimer ce compte ? La suppression est refusée s'il a des données (interruptions, alertes publiées…) : suspendez-le plutôt." style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_utilisateur" value="<?= $id ?>">
                      <button class="admin-action admin-action--danger" type="submit" aria-label="Supprimer" title="Supprimer"><span data-icon="trash"></span></button>
                    </form>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
<?php if ($comptes): ?>
            <p class="admin-empty" data-table-empty hidden>Aucun compte ne correspond à la recherche.</p>
<?php else: ?>
            <p class="admin-empty">Aucun sous-admin. Ajoutez un agent STEG ou SONEDE.</p>
<?php endif; ?>
          </div>

          <div class="admin-table-foot">
            <span><strong data-table-count><?= count($comptes) ?></strong> compte(s) affiché(s)</span>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Comptes sous-admins — WASLA Admin';
$currentPage = 'admin-sous-admins';
require __DIR__ . '/../../layout.php';
