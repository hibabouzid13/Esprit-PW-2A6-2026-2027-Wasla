<?php
/** Vue « services » — Front Office. */
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Services</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Services citoyens</p>
            <h1>Des services pour mieux vivre les coupures</h1>
            <p>Alertes, factures, fournisseurs, zones refuge et entraide : WASLA accompagne les citoyens avant, pendant et après chaque interruption.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--primary" href="<?= url(AuthController::isAuthenticated() ? 'mon-compte' : 'inscription') ?>">
              <span data-icon="user"></span>
              Créer mon espace
            </a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section">
      <div class="container">
        <div class="services-grid" data-services-grid></div>

        <div class="steps">
          <article class="step">
            <p class="step__num">01 — S'INFORMER</p>
            <h3>Consultez l'état du réseau</h3>
            <p>La carte et les analyses vous donnent une vision claire de la situation dans chaque gouvernorat.</p>
          </article>
          <article class="step">
            <p class="step__num">02 — ANTICIPER</p>
            <h3>Recevez les bonnes alertes</h3>
            <p>Choisissez vos zones et vos seuils pour être prévenu avant une coupure ou un pic de consommation.</p>
          </article>
          <article class="step">
            <p class="step__num">03 — AGIR</p>
            <h3>Activez les solutions locales</h3>
            <p>Zones refuge, relais de voisinage et priorité santé : des réponses concrètes pour chaque situation.</p>
          </article>
        </div>

        <div class="cta-band">
          <div>
            <h2>Un service ne figure pas encore ici ?</h2>
            <p>WASLA évolue avec les besoins des citoyens. Proposez un nouveau service ou signalez un besoin prioritaire.</p>
          </div>
          <div class="cta-band__actions">
            <a class="btn btn--primary" href="mailto:contact@wasla.tn?subject=Proposition%20de%20service">Proposer un service</a>
            <a class="btn btn--inverse" href="<?= url('connexion') ?>">Se connecter</a>
          </div>
        </div>
      </div>
    </section>
  </main>
<?php
$content = ob_get_clean();
$title       = 'Services citoyens — WASLA';
$description = 'Alertes personnalisées, factures, fournisseurs, zones refuge, relais de voisinage et priorité santé : découvrez les services WASLA.';
$currentPage = 'services';
require __DIR__ . '/../layout.php';
