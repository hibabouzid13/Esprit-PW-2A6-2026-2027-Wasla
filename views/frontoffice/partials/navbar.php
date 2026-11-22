  <a class="skip-link" href="#main">Aller au contenu</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?= url('accueil') ?>" aria-label="WASLA — Accueil">
        <span class="brand__mark">
          <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
            <path d="M32 3.5C32 3.5 10.5 25.5 10.5 40.5C10.5 51.8 20.1 61.5 32 61.5C43.9 61.5 53.5 51.8 53.5 40.5C53.5 25.5 32 3.5 32 3.5Z" fill="#FFFFFF" stroke="#00A3D7" stroke-width="4.5" stroke-linejoin="round"/>
            <path d="M32 12.5C32 12.5 18.5 29.5 18.5 39.5C18.5 47 24.5 53 32 53C39.5 53 45.5 47 45.5 39.5C45.5 29.5 32 12.5 32 12.5Z" fill="#003B5C"/>
            <path d="M36.2 19.5C36.2 19.5 27 31 27 39.2C27 44.2 30.8 48 35.8 48C41 48 45.2 43.8 45.2 38.6C45.2 30.5 40.4 24.5 36.2 19.5Z" fill="#73D13D"/>
            <path d="M32.2 21.5L24.6 39.2H29.4L27.2 49.2L36.8 31.8H31.9L34.2 21.5H32.2Z" fill="#EAF4FB"/>
          </svg>
        </span>
        <span class="brand__text">
          <span class="brand__name">WASLA</span>
          <span class="brand__tagline">Connecter. Anticiper. <span class="accent">Protéger.</span></span>
        </span>
      </a>

      <nav class="main-nav" aria-label="Navigation principale">
        <a class="nav-link <?= isActive('accueil', $currentPage) ?>" href="<?= url('accueil') ?>" data-nav="accueil" <?= ariaCurrent('accueil', $currentPage) ?>>Accueil</a>
        <a class="nav-link <?= isActive('carte', $currentPage) ?>" href="<?= url('carte') ?>" data-nav="carte" <?= ariaCurrent('carte', $currentPage) ?>>Carte</a>
        <a class="nav-link <?= isActive('analyses', $currentPage) ?>" href="<?= url('analyses') ?>" data-nav="analyses" <?= ariaCurrent('analyses', $currentPage) ?>>Analyses</a>
        <div class="nav-item">
          <button class="nav-link <?= in_array($currentPage, SERVICE_PAGES, true) ? 'is-active' : '' ?>" type="button" data-dropdown-toggle data-nav="services" aria-expanded="false" aria-controls="services-dropdown">
            Services
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="dropdown" id="services-dropdown" hidden>
            <p class="dropdown__label">Services citoyens</p>
            <a href="<?= url('alertes') ?>"><span class="dropdown__icon" data-icon="bell"></span><span class="dropdown__text">Alertes personnalisées<small>Notifications ciblées</small></span></a>
<?php if (serviceVisible('factures')): ?>
            <a href="<?= url('factures') ?>"><span class="dropdown__icon" data-icon="file-text"></span><span class="dropdown__text">Factures &amp; IA<small>Suivi et analyse</small></span></a>
<?php endif; ?>
            <a href="<?= url('fournisseurs') ?>"><span class="dropdown__icon" data-icon="wrench"></span><span class="dropdown__text">Fournisseurs<small>Techniciens agréés</small></span></a>
            <a href="<?= url('refuges') ?>"><span class="dropdown__icon" data-icon="home"></span><span class="dropdown__text">Zones refuge<small>Points d'accueil</small></span></a>
            <a href="<?= url('relais') ?>"><span class="dropdown__icon" data-icon="snowflake"></span><span class="dropdown__text">Relais de voisinage<small>Entraide locale</small></span></a>
            <a href="<?= url('sante') ?>"><span class="dropdown__icon" data-icon="heart-pulse"></span><span class="dropdown__text">Priorité santé<small>Besoins vitaux</small></span></a>
          </div>
        </div>
        <a class="nav-link <?= isActive('a-propos', $currentPage) ?>" href="<?= url('a-propos') ?>" data-nav="a-propos" <?= ariaCurrent('a-propos', $currentPage) ?>>À propos</a>
      </nav>

      <div class="header-actions">
        <div class="nav-item lang-switch">
          <button class="icon-btn lang-switch__btn" type="button" data-dropdown-toggle aria-expanded="false" aria-controls="lang-menu" aria-label="Changer de langue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            <span data-lang-code data-no-i18n>FR</span>
            <svg class="lang-switch__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="lang-menu" id="lang-menu" role="menu" aria-label="Langue" hidden>
            <button type="button" role="menuitemradio" aria-checked="true" data-set-lang="fr" lang="fr"><span class="lang-menu__code" data-no-i18n>FR</span><span data-no-i18n>Français</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
            <button type="button" role="menuitemradio" aria-checked="false" data-set-lang="en" lang="en"><span class="lang-menu__code" data-no-i18n>EN</span><span data-no-i18n>English</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
            <button type="button" role="menuitemradio" aria-checked="false" data-set-lang="ar" lang="ar" dir="rtl"><span class="lang-menu__code" data-no-i18n>AR</span><span data-no-i18n>العربية</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
          </div>
        </div>
        <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Activer le mode sombre">
          <svg class="theme-toggle__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
          <svg class="theme-toggle__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        </button>
        <button class="icon-btn" type="button" data-open-search aria-label="Rechercher (Ctrl+K)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
        <span class="header-divider" aria-hidden="true"></span>
        <div class="notification-wrap">
          <button class="icon-btn" type="button" data-open-notifications aria-label="Notifications, 3 non lues" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
            <span class="icon-btn__badge">3</span>
          </button>
        </div>
<?php if ($authUser = AuthController::currentUser()): ?>
<?php if (AuthController::isBackOffice($authUser)): ?>
        <a class="btn btn--primary btn--sm header-login" href="<?= url('dashboard') ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
          Administration
        </a>
<?php endif; ?>
        <a class="btn btn--secondary btn--sm header-login <?= isActive('mon-compte', $currentPage) ?>" href="<?= url('mon-compte') ?>" title="Mon espace">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span class="header-user" data-no-i18n><?= e($authUser->getPrenom()) ?></span>
        </a>
<?php else: ?>
        <a class="btn btn--secondary btn--sm header-login" href="<?= url('connexion') ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Connexion
        </a>
<?php endif; ?>
        <button class="menu-toggle" type="button" data-open-menu aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <div data-mobile-menu-root></div>
<?php if ($flashes = AuthController::takeFlashes()): ?>
  <div class="container flash-stack" role="status">
<?php foreach ($flashes as $flash): ?>
    <p class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
<?php endforeach; ?>
  </div>
<?php endif; ?>
  <div data-search-root></div>

