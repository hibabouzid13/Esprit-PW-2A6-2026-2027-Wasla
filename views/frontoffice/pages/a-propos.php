<?php
/** Vue « a-propos » — Front Office. */
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">À propos</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Notre mission</p>
            <h1>Connecter les territoires, anticiper les crises, protéger les citoyens.</h1>
            <p>WASLA est née d'un constat simple : l'information sur les coupures d'eau et d'électricité doit être accessible, fiable et utile à tous.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--primary" href="<?= url(AuthController::isAuthenticated() ? 'mon-compte' : 'inscription') ?>">Rejoindre WASLA</a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section">
      <div class="container">
        <div class="about-grid">
          <div class="about-copy">
            <h2 class="display-title">Une plateforme citoyenne, utile et transparente.</h2>
            <p>En Tunisie, les coupures d'eau et d'électricité affectent le quotidien de millions de foyers. WASLA rassemble les données disponibles, les rend lisibles et aide chacun à anticiper.</p>
            <p>Notre approche est simple : connecter les réseaux et les citoyens, anticiper les tensions grâce à l'analyse, et protéger les plus vulnérables en priorisant les besoins essentiels.</p>
            <p>WASLA est un prototype de démonstration. Les données affichées sont illustratives et servent à présenter l'expérience et les services de la plateforme.</p>
            <div class="cluster" style="margin-top:18px;">
              <a class="btn btn--secondary" href="<?= url('services') ?>">Découvrir les services</a>
              <a class="btn btn--outline" href="<?= url('carte') ?>">Explorer la carte</a>
            </div>
          </div>
          <div class="about-visual">
            <img src="<?= asset('img/solar-panels.jpg') ?>" alt="Panneaux solaires dans une centrale photovoltaïque" loading="lazy">
            <div class="about-visual__overlay" aria-hidden="true"></div>
            <div class="about-visual__content">
              <p>Une énergie locale, propre et partagée pour les générations futures.</p>
              <div class="about-visual__stat"><strong>100%</strong><span>ENGAGEMENT</span></div>
            </div>
          </div>
        </div>

        <div class="values-grid">
          <article class="value-card">
            <span data-icon="eye"></span>
            <h3>Transparence</h3>
            <p>Des données claires et accessibles, pour des décisions éclairées.</p>
          </article>
          <article class="value-card">
            <span data-icon="users"></span>
            <h3>Solidarité</h3>
            <p>Un réseau d'entraide qui relie voisins, fournisseurs et institutions.</p>
          </article>
          <article class="value-card">
            <span data-icon="leaf"></span>
            <h3>Durabilité</h3>
            <p>Une consommation plus responsable et des ressources préservées.</p>
          </article>
          <article class="value-card">
            <span data-icon="shield-check"></span>
            <h3>Protection</h3>
            <p>Les besoins vitaux prioritaires, surtout pour les plus vulnérables.</p>
          </article>
        </div>

        <div class="coverage-band" id="engagements">
          <div class="coverage-band__item">
            <span data-icon="users"></span>
            <div><p class="coverage-band__value">+1M</p><p class="coverage-band__label">Utilisateurs visés</p></div>
          </div>
          <div class="coverage-band__item">
            <span data-icon="landmark"></span>
            <div><p class="coverage-band__value">24</p><p class="coverage-band__label">Gouvernorats couverts</p></div>
          </div>
          <div class="coverage-band__item">
            <span data-icon="handshake"></span>
            <div><p class="coverage-band__value">100%</p><p class="coverage-band__label">Engagement citoyen</p></div>
          </div>
        </div>

        <div class="cta-band">
          <div>
            <h2>Prêt à agir pour une Tunisie plus résiliente ?</h2>
            <p>Créez votre espace citoyen et recevez des alertes adaptées à votre zone.</p>
          </div>
          <div class="cta-band__actions">
            <a class="btn btn--primary" href="<?= url(AuthController::isAuthenticated() ? 'mon-compte' : 'inscription') ?>">Créer mon espace</a>
            <a class="btn btn--inverse" href="mailto:contact@wasla.tn">Nous contacter</a>
          </div>
        </div>

        <div id="mentions" style="margin-top:30px; font-size:12px; line-height:1.6; color:var(--color-text-secondary);">
          <p><strong style="color:var(--color-heading);">Mentions légales —</strong> WASLA est un prototype de démonstration. Les données, chiffres et statuts affichés sont fictifs. Les photographies sont utilisées à des fins d'illustration.</p>
          <p id="confidentialite" style="margin-top:6px;"><strong style="color:var(--color-heading);">Confidentialité —</strong> Aucune donnée personnelle n'est collectée dans cette démonstration. Les formulaires sont simulés.</p>
          <p id="accessibilite" style="margin-top:6px;"><strong style="color:var(--color-heading);">Accessibilité —</strong> L'interface vise le niveau AA : contraste renforcé, navigation clavier et respect de la préférence de réduction des animations.</p>
        </div>
      </div>
    </section>
  </main>
<?php
$content = ob_get_clean();
$title       = 'À propos — WASLA';
$description = "Découvrez la mission, les valeurs et les engagements de WASLA pour une Tunisie plus résiliente face aux coupures d'eau et d'électricité.";
$currentPage = 'a-propos';
require __DIR__ . '/../layout.php';
