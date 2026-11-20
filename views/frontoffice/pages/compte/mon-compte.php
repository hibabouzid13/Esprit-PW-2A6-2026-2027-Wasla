<?php
/**
 * Vue « Mon espace » — Front Office (tout utilisateur connecté).
 * Variable reçue : $user (UserModel).
 * La modification du profil est ajoutée par chaque module pour ses propres rôles.
 */
ob_start();
$isAdmin = AuthController::isBackOffice($user);
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Mon espace</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Mon espace</p>
            <h1>Bonjour, <?= e($user->getPrenom()) ?></h1>
            <p>Retrouvez ici l'état du réseau dans votre gouvernorat, vos analyses et les services WASLA.</p>
          </div>
          <div class="page-hero__actions">
<?php if ($isAdmin): ?>
            <a class="btn btn--primary" href="<?= url('dashboard') ?>"><span data-icon="settings"></span> Administration</a>
<?php endif; ?>
            <form method="post" action="<?= url('deconnexion') ?>">
              <?= csrfField() ?>
              <button class="btn btn--outline" type="submit"><span data-icon="log-out"></span> Se déconnecter</button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section">
      <div class="container account-grid">
        <aside class="card account-profile">
          <div class="account-profile__head">
            <span class="account-avatar" aria-hidden="true" data-no-i18n><?= e(initials($user)) ?></span>
            <div>
              <h2 data-no-i18n><?= e($user->getPrenom() . ' ' . $user->getNom()) ?></h2>
              <span class="chip"><?= e(roleLabel($user->getRole())) ?></span>
            </div>
          </div>
          <dl class="account-details">
            <div><dt>Adresse e-mail</dt><dd data-no-i18n><?= e($user->getEmail()) ?></dd></div>
            <div><dt>Téléphone</dt><dd><?= e($user->getTelephone() ?: '—') ?></dd></div>
            <div><dt>Gouvernorat</dt><dd><?= e($user->getNomZone() ?? '—') ?></dd></div>
            <div><dt>Membre depuis</dt><dd><?= e(dateFr($user->getDateInscription())) ?></dd></div>
            <div><dt>Dernière connexion</dt><dd><?= e(dateFr($user->getDerniereConnexion(), true)) ?></dd></div>
<?php if ($user->getNomOrganisation() !== null): ?>
            <div><dt><?= $user->getRole() === 'fournisseur' ? 'Nom commercial' : 'Entreprise' ?></dt><dd data-no-i18n><?= e($user->getNomOrganisation()) ?></dd></div>
<?php endif; ?>
<?php if ($user->getMatriculeFiscal() !== null): ?>
            <div><dt>Matricule fiscal</dt><dd data-no-i18n><?= e($user->getMatriculeFiscal()) ?></dd></div>
<?php endif; ?>
<?php if ($user->getSecteurActivite() !== null): ?>
            <div><dt>Secteur</dt><dd data-no-i18n><?= e($user->getSecteurActivite()) ?></dd></div>
<?php endif; ?>
<?php if ($user->getSpecialite() !== null): ?>
            <div><dt>Spécialité</dt><dd><?= e(specialiteLabel($user->getSpecialite())) ?></dd></div>
<?php endif; ?>
<?php if (in_array($user->getRole(), ['entreprise', 'fournisseur'], true)): ?>
            <div><dt>Compte vérifié</dt><dd><?= $user->isCompteVerifie() ? 'Oui' : "En attente de validation par l'administrateur" ?></dd></div>
<?php endif; ?>
          </dl>
        </aside>

        <div class="account-main">
          <h2 class="account-title">Accès rapides</h2>
          <div class="account-actions">
            <a class="service-card account-action" href="<?= url('carte') ?>">
              <span class="service-card__icon" data-icon="map"></span>
              <h3>Carte des réseaux</h3>
              <p>Consultez l'état de l'électricité et de l'eau<?= $user->getNomZone() ? ' à ' . e($user->getNomZone()) : '' ?> et signalez une coupure.</p>
            </a>
            <a class="service-card account-action" href="<?= url('analyses') ?>">
              <span class="service-card__icon service-card__icon--green" data-icon="chart-line"></span>
              <h3>Analyses &amp; consommation</h3>
              <p>Suivez les tendances de consommation et comparez-les à la moyenne de votre zone.</p>
            </a>
            <a class="service-card account-action" href="<?= url('alertes') ?>">
              <span class="service-card__icon service-card__icon--amber" data-icon="bell"></span>
              <h3>Alertes personnalisées</h3>
              <p>Soyez prévenu des interruptions planifiées et des incidents dans votre zone.</p>
            </a>
            <a class="service-card account-action" href="<?= url('factures') ?>">
              <span class="service-card__icon service-card__icon--navy" data-icon="file-text"></span>
              <h3>Factures</h3>
              <p>Analysez vos factures STEG et SONEDE et recevez des recommandations.</p>
            </a>
            <a class="service-card account-action" href="<?= url('refuges') ?>">
              <span class="service-card__icon" data-icon="home"></span>
              <h3>Zones refuge</h3>
              <p>Trouvez un lieu pour accéder à l'eau ou à l'électricité pendant une coupure.</p>
            </a>
            <a class="service-card account-action" href="<?= url('sante') ?>">
              <span class="service-card__icon service-card__icon--red" data-icon="heart-pulse"></span>
              <h3>Assistance d'urgence</h3>
              <p>Demandez de l'aide à un bénévole, un fournisseur ou un propriétaire solaire.</p>
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mon espace — WASLA';
$description = 'Votre espace personnel WASLA.';
$currentPage = 'mon-compte';
require __DIR__ . '/../../layout.php';
