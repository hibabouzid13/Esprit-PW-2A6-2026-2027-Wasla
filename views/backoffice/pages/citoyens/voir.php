<?php
/**
 * Vue : fiche d'un citoyen — Back Office (super admin) — Module 2 (Hiba).
 * Variables reçues : $citoyen (UserModel), $donnees (libellé => nombre : données liées au compte).
 */
ob_start();
$id = $citoyen->getIdUtilisateur();
$nom = $citoyen->getPrenom() . ' ' . $citoyen->getNom();
$actif = $citoyen->getStatutCompte() === 'actif';
?>
        <link rel="stylesheet" href="<?= e(asset('css/factures-admin.css', 'backoffice')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/citoyens-admin.css', 'backoffice')) ?>">
        <div class="admin-page-head bill-admin-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><a href="<?= url('admin-citoyens') ?>">Comptes citoyens</a><span>/</span><span>Fiche</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="user"></span>
              <div>
                <h1 class="citizen-name"><?= e($nom) ?></h1>
                <p>Compte citoyen n° <?= $id ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-form-layout">
          <section class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="user"></span> Informations</h2>
              <span class="admin-badge <?= $actif ? 'admin-badge--ok' : 'admin-badge--danger' ?> citizen-status"><?= e(CitoyenController::statutLabel($citoyen->getStatutCompte())) ?></span>
            </div>
            <div class="admin-card__body">
              <dl class="citizen-facts">
                <div><dt>Prénom</dt><dd><?= e($citoyen->getPrenom()) ?></dd></div>
                <div><dt>Nom</dt><dd><?= e($citoyen->getNom()) ?></dd></div>
                <div><dt>Adresse e-mail</dt><dd><?= e($citoyen->getEmail()) ?></dd></div>
                <div><dt>Téléphone</dt><dd><?= e($citoyen->getTelephone() ?: '—') ?></dd></div>
                <div><dt>Gouvernorat</dt><dd><?= e($citoyen->getNomZone() ?? '—') ?></dd></div>
                <div><dt>Adresse</dt><dd><?= e($citoyen->getAdresse() ?: '—') ?></dd></div>
                <div><dt>Inscription</dt><dd><?= e(dateFr($citoyen->getDateInscription())) ?></dd></div>
                <div><dt>Dernière connexion</dt><dd><?= e(dateFr($citoyen->getDerniereConnexion(), true)) ?></dd></div>
              </dl>
            </div>
          </section>

          <aside class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="file-text"></span> Données du compte</h2>
            </div>
            <div class="admin-card__body">
<?php if ($donnees === []): ?>
              <p class="citizen-note">Aucune donnée n'est liée à ce compte.</p>
<?php else: ?>
              <ul class="citizen-data">
<?php foreach ($donnees as $libelle => $nombre): ?>
                <li><span><?= e($libelle) ?></span><b><?= $nombre ?></b></li>
<?php endforeach; ?>
              </ul>
<?php if (isset($donnees['Factures'])): ?>
              <a class="admin-btn admin-btn--outline citizen-link" href="<?= e(url('admin-factures', ['q' => mb_substr($citoyen->getEmail(), 0, 50)])) ?>"><span data-icon="file-text"></span> Voir ses factures</a>
<?php endif; ?>
<?php endif; ?>
            </div>
          </aside>
        </div>
<?php
$content     = ob_get_clean();
$title       = 'Fiche citoyen — WASLA Admin';
$currentPage = 'admin-citoyen-voir';
$pageScripts = ['js/factures-crud.js'];   // icônes du module
require __DIR__ . '/../../layout.php';
