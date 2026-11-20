<?php
/**
 * Vue : Page « Analyses & tendances » de la consommation — Front Office — Module 2 (Hiba).
 */
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Analyses</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Observatoire</p>
            <h1>Analyses &amp; tendances</h1>
            <p>Suivez l'évolution de la consommation, identifiez les zones à risque et comparez les réseaux d'électricité et d'eau.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('carte') ?>"><span data-icon="map"></span> Voir la carte</a>
            <button class="btn btn--secondary" type="button" data-demo-action="Le rapport PDF sera disponible prochainement.">
              <span data-icon="download"></span>
              Télécharger le rapport
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section">
      <div class="container">
        <div class="analysis-toolbar">
          <div class="tabs" role="tablist" aria-label="Période d'analyse">
            <button class="tab is-active" type="button" role="tab" aria-selected="true" data-period="7j">7 jours</button>
            <button class="tab" type="button" role="tab" aria-selected="false" data-period="30j">30 jours</button>
            <button class="tab" type="button" role="tab" aria-selected="false" data-period="12m">12 mois</button>
          </div>
          <div class="analysis-toolbar__right">
            <label class="sr-only" for="analysis-zone">Filtrer par gouvernorat</label>
            <select class="select" id="analysis-zone" data-analysis-zone></select>
            <button class="btn btn--outline btn--sm" type="button" data-demo-action="Export CSV de démonstration.">
              <span data-icon="download"></span>
              Exporter
            </button>
          </div>
        </div>

        <div class="stats-row">
          <article class="stat-card">
            <p class="stat-card__label"><span data-icon="zap"></span> Électricité consommée</p>
            <p class="stat-card__value">1,24 <span>GWh</span></p>
            <p class="stat-card__delta stat-card__delta--up"><span data-icon="trending-up"></span> +4,2 % vs période précédente</p>
          </article>
          <article class="stat-card">
            <p class="stat-card__label"><span data-icon="droplet"></span> Eau consommée</p>
            <p class="stat-card__value">842 <span>k m³</span></p>
            <p class="stat-card__delta stat-card__delta--up"><span data-icon="trending-up"></span> +2,8 % vs période précédente</p>
          </article>
          <article class="stat-card">
            <p class="stat-card__label"><span data-icon="triangle-alert"></span> Coupures signalées</p>
            <p class="stat-card__value">7</p>
            <p class="stat-card__delta stat-card__delta--up"><span data-icon="trending-down"></span> −12 % vs période précédente</p>
          </article>
          <article class="stat-card">
            <p class="stat-card__label"><span data-icon="activity"></span> Stabilité moyenne</p>
            <p class="stat-card__value">96,4 <span>%</span></p>
            <p class="stat-card__delta stat-card__delta--up"><span data-icon="trending-up"></span> +1,1 point vs période précédente</p>
          </article>
        </div>

        <div class="chart-grid">
          <article class="card chart-card">
            <div class="chart-card__head">
              <div>
                <h2 class="chart-card__title">Évolution de la consommation</h2>
                <p class="chart-card__sub">Indice de charge — <span data-period-label>7 derniers jours</span></p>
              </div>
              <span class="status status--normal"><span class="status__dot"></span>Données consolidées</span>
            </div>
            <div class="chart-card__body" data-line-chart></div>
            <div class="chart-legend">
              <span class="chart-legend__item"><span class="chart-legend__swatch"></span>Électricité</span>
              <span class="chart-legend__item"><span class="chart-legend__swatch chart-legend__swatch--water"></span>Eau</span>
            </div>
          </article>

          <article class="card chart-card">
            <div class="chart-card__head">
              <div>
                <h2 class="chart-card__title">Consommation par gouvernorat</h2>
                <p class="chart-card__sub">Top 6 — indice relatif</p>
              </div>
              <span class="status status--watch"><span class="status__dot"></span>Vigilance</span>
            </div>
            <div class="chart-card__body" data-bar-chart></div>
            <div class="chart-legend">
              <span class="chart-legend__item"><span class="chart-legend__swatch"></span>Électricité</span>
              <span class="chart-legend__item"><span class="chart-legend__swatch chart-legend__swatch--water"></span>Eau</span>
            </div>
          </article>
        </div>

        <article class="card">
          <div class="card__header">
            <h2 class="card__title">Derniers incidents</h2>
            <a class="text-link" href="<?= url('carte') ?>">Voir la carte
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Zone</th>
                  <th>Réseau</th>
                  <th>Statut</th>
                  <th>Durée</th>
                  <th>Dernière mise à jour</th>
                </tr>
              </thead>
              <tbody data-outage-table></tbody>
            </table>
          </div>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Analyses & tendances — WASLA';
$description = 'Suivez l\'évolution de la consommation d\'électricité et d\'eau, identifiez les zones à risque et consultez les derniers incidents en Tunisie.';
$currentPage = 'analyses';
$pageCss     = ['pages'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/charts.js', 'js/pages.js'];
require __DIR__ . '/../../layout.php';
