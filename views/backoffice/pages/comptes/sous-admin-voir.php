<?php
/**
 * Vue : Back Office : fiche d'un sous-admin STEG ou SONEDE — Module 1 (Houssem). Super admin uniquement.
 * Variables reçues :
 *   $compte  UserModel   le sous-admin
 *   $liens   string[]    données liées (vide = le compte peut être supprimé)
 */
$id     = $compte->getIdUtilisateur();
$actif  = $compte->getStatutCompte() === 'actif';

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-sous-admins') ?>">Comptes sous-admins</a><span>/</span><span>Fiche</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="user"></span>
              <div>
                <h1><?= e($compte->getPrenom() . ' ' . $compte->getNom()) ?></h1>
                <p><?= e(roleLabel($compte->getRole())) ?> · <span class="admin-badge admin-badge--<?= $actif ? 'ok' : 'danger' ?>"><?= $actif ? 'Actif' : 'Suspendu' ?></span></p>
              </div>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--outline" href="<?= url('admin-sous-admin-form', ['id' => $id]) ?>"><span data-icon="pencil"></span> Modifier</a>
            <form method="post" action="<?= url('admin-sous-admin-statut') ?>" data-confirm="<?= $actif ? 'Suspendre ce compte ? Il ne pourra plus se connecter (ses données sont conservées).' : 'Réactiver ce compte ?' ?>" style="display:contents;">
              <?= csrfField() ?>
              <input type="hidden" name="id_utilisateur" value="<?= $id ?>">
              <input type="hidden" name="statut" value="<?= $actif ? 'suspendu' : 'actif' ?>">
              <button class="admin-btn admin-btn--outline" type="submit"><span data-icon="<?= $actif ? 'lock' : 'circle-check' ?>"></span> <?= $actif ? 'Suspendre' : 'Réactiver' ?></button>
            </form>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="info"></span> Informations</h2>
            </div>
            <div class="admin-card__body admin-aside-note">
              <dl>
                <div><dt>N°</dt><dd><?= $id ?></dd></div>
                <div><dt>E-mail</dt><dd><?= e($compte->getEmail()) ?></dd></div>
                <div><dt>Téléphone</dt><dd><?= e($compte->getTelephone() ?? '—') ?></dd></div>
                <div><dt>Gouvernorat</dt><dd><?= e($compte->getNomZone() ?? '—') ?></dd></div>
                <div><dt>Service / district</dt><dd><?= e($compte->getNomOrganisation() ?? '—') ?></dd></div>
                <div><dt>Ressource gérée</dt><dd><?= $compte->getRole() === 'steg' ? 'Électricité' : 'Eau' ?></dd></div>
                <div><dt>Créé le</dt><dd><?= e(dateFr($compte->getDateInscription(), true)) ?></dd></div>
                <div><dt>Dernière connexion</dt><dd><?= $compte->getDerniereConnexion() ? e(dateFr($compte->getDerniereConnexion(), true)) : 'Jamais connecté' ?></dd></div>
              </dl>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="layers"></span> Données liées</h2>
            </div>
            <div class="admin-card__body admin-aside-note">
<?php if ($liens): ?>
              <ul>
<?php foreach ($liens as $l): ?>
                <li><?= e($l) ?></li>
<?php endforeach; ?>
              </ul>
              <p>Ce compte ne peut pas être supprimé : suspendez-le pour bloquer l'accès en gardant l'historique.</p>
<?php else: ?>
              <p>Aucune donnée liée : ce compte peut être supprimé.</p>
              <form method="post" action="<?= url('admin-sous-admin-supprimer') ?>" data-confirm="Supprimer définitivement ce compte ?">
                <?= csrfField() ?>
                <input type="hidden" name="id_utilisateur" value="<?= $id ?>">
                <button class="admin-btn admin-btn--outline" type="submit"><span data-icon="trash"></span> Supprimer le compte</button>
              </form>
<?php endif; ?>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = 'Fiche sous-admin — WASLA Admin';
$currentPage = 'admin-sous-admin-voir';
require __DIR__ . '/../../layout.php';
