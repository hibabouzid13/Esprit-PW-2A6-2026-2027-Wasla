<?php
/** Vue « accueil » — Front Office. */
ob_start();
?>
  <main id="main">
    <!-- Hero -->
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero__media">
        <img src="<?= asset('img/hero-coast.jpg') ?>" srcset="<?= asset('img/hero-coast-960.jpg') ?> 960w, <?= asset('img/hero-coast.jpg') ?> 1920w" sizes="100vw" alt="Éoliennes sur la côte du Cap Bon en Tunisie, face à la Méditerranée" width="1920" height="1280" fetchpriority="high">
      </div>
      <div class="hero__overlay" aria-hidden="true"></div>
      <div class="hero__network" data-hero-network aria-hidden="true"></div>

      <div class="container hero__inner">
        <div class="hero__copy">
          <p class="eyebrow">Une Tunisie plus résiliente</p>
          <h1 class="hero__title" id="hero-title">WASLA</h1>
          <p class="hero__subtitle">Connecter. Anticiper. <span class="accent">Protéger.</span></p>
          <p class="hero__description">Une plateforme intelligente pour surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.</p>
          <div class="hero__actions">
            <a class="btn btn--primary btn--lg" href="#dashboard">
              Découvrir la plateforme
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      </div>

      <a class="hero__scroll" href="#features" aria-label="Faire défiler vers le contenu">
        <span class="hero__scroll-text">SCROLL</span>
        <span class="hero__scroll-mouse" aria-hidden="true"></span>
      </a>
    </section>

    <!-- Feature strip -->
    <section class="feature-strip" id="features" aria-label="Fonctionnalités clés">
      <div class="container feature-strip__inner">
        <div class="feature-item">
          <span class="feature-item__icon feature-item__icon--energy" data-icon="zap"></span>
          <span class="feature-item__text">Suivre la consommation d'électricité</span>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon feature-item__icon--water" data-icon="droplet"></span>
          <span class="feature-item__text">Surveiller la consommation d'eau</span>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon feature-item__icon--map" data-icon="map-pin"></span>
          <span class="feature-item__text">Consulter la carte en temps réel</span>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon feature-item__icon--alert" data-icon="bell"></span>
          <span class="feature-item__text">Recevoir des alertes personnalisées</span>
        </div>
        <div class="feature-item">
          <span class="feature-item__icon feature-item__icon--leaf" data-icon="leaf"></span>
          <span class="feature-item__text">Agir pour une Tunisie durable</span>
        </div>
      </div>
    </section>

    <!-- Dashboard -->
    <section class="dashboard-section" id="dashboard" aria-labelledby="dashboard-title">
      <div class="container">
        <div class="dashboard-heading">
          <div class="dashboard-heading__title">
            <h2 id="dashboard-title">État actuel des réseaux</h2>
          </div>
          <div class="dashboard-heading__meta">
            <span class="live-dot" aria-hidden="true"></span>
            Mis à jour à l'instant · Données de démonstration
          </div>
        </div>

        <div class="dashboard-grid">
          <div class="kpi-column">
            <article class="kpi-card">
              <div class="kpi-card__head">
                <span class="kpi-card__icon kpi-card__icon--energy" data-icon="zap"></span>
                <h3 class="kpi-card__title">Électricité</h3>
              </div>
              <div class="kpi-card__main">
                <div>
                  <p class="kpi-card__value">98<span>%</span></p>
                  <p class="kpi-card__status">Réseau stable</p>
                </div>
                <div class="kpi-card__spark" data-spark="electricity" aria-hidden="true"></div>
              </div>
              <div class="kpi-card__footer">
                <a class="text-link" href="<?= url('analyses') ?>">Voir les détails
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
              </div>
            </article>

            <article class="kpi-card">
              <div class="kpi-card__head">
                <span class="kpi-card__icon kpi-card__icon--water" data-icon="droplet"></span>
                <h3 class="kpi-card__title">Eau</h3>
              </div>
              <div class="kpi-card__main">
                <div>
                  <p class="kpi-card__value">95<span>%</span></p>
                  <p class="kpi-card__status">Réseau stable</p>
                </div>
                <div class="kpi-card__spark" data-spark="water" aria-hidden="true"></div>
              </div>
              <div class="kpi-card__footer">
                <a class="text-link" href="<?= url('analyses') ?>">Voir les détails
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
              </div>
            </article>
          </div>

          <div class="map-card" data-map-card>
            <div class="map-card__canvas" data-map-canvas data-map-mode="home">
              <div class="map-card__label"><span class="live-dot" aria-hidden="true"></span>Tunisie · Temps réel</div>
              <div class="map-card__toolbar">
                <button class="map-control" type="button" data-map-fullscreen aria-label="Afficher la carte en plein écran">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                </button>
              </div>
              <div class="map-card__legend" aria-label="Légende des statuts">
                <span class="map-card__legend-title">Statut du réseau</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--normal"></span>Normale</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--watch"></span>En vigilance</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--outage"></span>Coupure</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--nodata"></span>Sans données</span>
              </div>
              <div class="map-card__zoom">
                <button class="map-control" type="button" data-map-zoom="in" aria-label="Zoomer">+</button>
                <button class="map-control" type="button" data-map-zoom="out" aria-label="Dézoomer">−</button>
                <button class="map-control" type="button" data-map-reset aria-label="Recentrer la carte">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" x2="5" y1="12" y2="12"/><line x1="19" x2="22" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="5"/><line x1="12" x2="12" y1="19" y2="22"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <div class="map-tooltip" data-map-tooltip hidden></div>
            </div>
          </div>

          <article class="alert-card">
            <div class="alert-card__media">
              <img src="<?= asset('img/alert-powerlines.jpg') ?>" alt="Lignes électriques haute tension au coucher du soleil" loading="lazy">
            </div>
            <div class="alert-card__overlay" aria-hidden="true"></div>
            <div class="alert-card__content">
              <div class="alert-card__head">
                <span data-icon="triangle-alert"></span>
                <h3>Alerte importante</h3>
              </div>
              <p class="alert-card__body">La consommation électrique dans certaines zones approche du <strong>seuil critique</strong>. Pensez à réduire votre consommation.</p>
            </div>
            <div class="alert-card__foot">
              <a class="btn btn--secondary btn--sm" href="<?= url('carte') ?>">Voir la carte
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
              <span class="alert-card__tag">Vigilance</span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Engagements -->
    <section class="engagements" aria-labelledby="engagements-title">
      <div class="container engagements__inner">
        <div class="engagements__intro">
          <h2 id="engagements-title">Nos engagements</h2>
          <p>Pour une gestion durable et intelligente des ressources en Tunisie.</p>
        </div>
        <div class="engagements__stats">
          <div class="engagement-stat">
            <span data-icon="users"></span>
            <div>
              <p class="engagement-stat__value">+1M</p>
              <p class="engagement-stat__label">Utilisateurs</p>
            </div>
          </div>
          <div class="engagement-stat">
            <span data-icon="landmark"></span>
            <div>
              <p class="engagement-stat__value">24</p>
              <p class="engagement-stat__label">Gouvernorats</p>
            </div>
          </div>
          <div class="engagement-stat">
            <span data-icon="handshake"></span>
            <div>
              <p class="engagement-stat__value">100%</p>
              <p class="engagement-stat__label">Engagement citoyen</p>
            </div>
          </div>
        </div>
        <div class="engagements__visual">
          <img src="<?= asset('img/solar-panels.jpg') ?>" alt="Panneaux solaires dans une centrale photovoltaïque" loading="lazy">
          <div class="engagements__visual-overlay" aria-hidden="true"></div>
          <div class="engagements__visual-content">
            <span data-icon="leaf"></span>
            <p>Ensemble pour une Tunisie durable</p>
            <a class="arrow-circle" href="<?= url('a-propos') ?>" aria-label="Découvrir nos engagements">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>
<?php
$content = ob_get_clean();
$title       = 'WASLA — Connecter. Anticiper. Protéger.';
$description = "WASLA est une plateforme intelligente pour surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.";
$currentPage = 'accueil';
$pageCss     = ['home'];
$pageScripts = ['data/tunisia.js', 'js/data.js', 'js/icons.js', 'js/layout.js', 'js/charts.js', 'js/map.js'];
$extraHead   = '  <link rel="preload" href="' . asset('img/hero-coast.jpg') . '" as="image" imagesrcset="'
             . asset('img/hero-coast-960.jpg') . ' 960w, ' . asset('img/hero-coast.jpg') . ' 1920w" imagesizes="100vw">' . "\n";
require __DIR__ . '/../layout.php';
