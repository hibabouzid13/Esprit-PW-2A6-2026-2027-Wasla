<?php
/**
 * Vue : Service « Zones refuge » (carte, liste, fiches) — Front Office — Module 3 (Louay).
 * Variables reçues (routes/module3.php) :
 *   $refuges         RefugeZoneModel[]  refuges validés, lus dans la base
 *   $mapData         array              mêmes refuges, format attendu par assets/js/services/refuges.js
 *   $user            ?UserModel         utilisateur connecté
 *   $canReserve      bool               l'utilisateur peut réserver une place
 *   $myReservations  array<int,int>     id_refuge => personnes réservées (réservations actives)
 * Les données passent au script par des attributs data-* encodés en JSON (la CSP interdit tout <script> en ligne).
 */
ob_start();
?>
  <main id="main" data-service="refuges"
        data-refuges="<?= e(json_encode($mapData, JSON_UNESCAPED_UNICODE)) ?>"
        data-my-reservations="<?= e(json_encode((object) $myReservations)) ?>"
        data-can-reserve="<?= $canReserve ? '1' : '0' ?>"
        data-reserve-url="<?= e(url('refuge-reserver') . '&id=') ?>"
        data-login-url="<?= $user === null ? e(url('connexion')) : '' ?>"
        data-my-reservations-url="<?= e(url('mes-reservations')) ?>">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Zones refuge</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 04 · Points d'accueil</p>
            <h1>Zones refuge</h1>
            <p>Trouvez rapidement un lieu climatisé, un point d'eau potable ou une recharge électrique ouvert près de vous pendant une coupure ou une vague de chaleur.</p>
          </div>
          <div class="page-hero__actions">
<?php if ($canReserve): ?>
            <a class="btn btn--outline" href="<?= url('mes-reservations') ?>"><span data-icon="calendar"></span> Mes réservations</a>
<?php elseif ($user !== null && $user->getRole() === 'entreprise'): ?>
            <a class="btn btn--outline" href="<?= url('mes-refuges') ?>"><span data-icon="building-2"></span> Gérer mes refuges</a>
<?php elseif ($user === null): ?>
            <a class="btn btn--outline" href="<?= url('inscription-entreprise') ?>"><span data-icon="building-2"></span> Proposer un refuge</a>
<?php endif; ?>
            <button class="btn btn--primary" type="button" data-locate-me>
              <span data-icon="locate"></span>
              Me localiser
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice"><span data-icon="info"></span><span>Les lieux affichés sont proposés par des <strong>entreprises partenaires validées</strong> par WASLA. Les places disponibles sont mises à jour à chaque réservation. Les distances sont calculées à vol d'oiseau.</span></p>

        <div class="summary-grid">
          <div class="summary-card"><span class="summary-card__icon summary-card__icon--green" data-icon="home"></span><div><p class="summary-card__value" data-rstat="open">–</p><p class="summary-card__label">Lieux ouverts</p></div></div>
          <div class="summary-card"><span class="summary-card__icon" data-icon="users"></span><div><p class="summary-card__value" data-rstat="places">–</p><p class="summary-card__label">Places disponibles</p></div></div>
          <div class="summary-card"><span class="summary-card__icon" data-icon="snowflake"></span><div><p class="summary-card__value" data-rstat="clim">–</p><p class="summary-card__label">Espaces climatisés ouverts</p></div></div>
          <div class="summary-card"><span class="summary-card__icon summary-card__icon--amber" data-icon="map-pin"></span><div><p class="summary-card__value" data-rstat="nearest">–</p><p class="summary-card__label" data-rstat-label>Lieu ouvert le plus proche</p></div></div>
        </div>

        <div class="filter-bar">
          <div class="filter-search">
            <span data-icon="search"></span>
            <label class="sr-only" for="refuge-search">Rechercher un lieu</label>
            <input id="refuge-search" type="search" placeholder="Nom, ville, type de lieu…" data-refuge-search>
          </div>
          <span class="filter-bar__label">Équipements</span>
          <div class="svc-chips" data-feature-chips></div>
          <span class="filter-bar__spacer"></span>
          <label class="checkbox" style="align-items:center;"><input type="checkbox" data-only-available> Places disponibles</label>
          <label class="sr-only" for="refuge-distance">Proximité</label>
          <select class="select" id="refuge-distance" data-refuge-distance style="width:auto;min-height:34px;padding-block:6px;font-size:12px;">
            <option value="">Toutes distances</option>
            <option value="5">Moins de 5 km</option>
            <option value="15">Moins de 15 km</option>
            <option value="50">Moins de 50 km</option>
          </select>
        </div>

        <div class="map-page-grid">
          <div class="map-card map-card--page svc-map svc-map--tall" data-map-card>
            <div class="map-card__canvas" data-refuge-map>
              <div class="map-card__label"><span class="live-dot" aria-hidden="true"></span><span data-position-label>Position de référence : Tunis</span></div>
              <div class="map-card__legend" aria-label="Légende">
                <span class="map-card__legend-title">Statut</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--normal"></span>Ouvert</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--watch"></span>Complet</span>
                <span class="legend-item"><span class="legend-item__dot legend-item__dot--nodata"></span>Fermé</span>
                <span class="legend-item"><span class="legend-item__dot" style="background:var(--color-navy)"></span>Vous</span>
              </div>
              <p class="svc-map__empty" hidden>Aucun lieu ne correspond aux filtres</p>
            </div>
          </div>

          <aside class="map-page-side">
            <div class="card">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Lieux d'accueil</h2>
                  <p class="svc-card-sub" data-refuge-caption>Chargement…</p>
                </div>
                <label class="sr-only" for="refuge-origin">Position de référence</label>
                <select class="select" id="refuge-origin" data-refuge-origin style="width:auto;min-height:34px;padding-block:6px;font-size:12px;max-width:150px;"></select>
              </div>
              <div class="svc-refuge-list" data-refuge-list aria-live="polite"></div>
              <div class="card__footer">
                <span>Triés par distance</span>
                <button class="text-link" type="button" data-refuge-reset>Réinitialiser</button>
              </div>
            </div>
          </aside>
        </div>

        <article class="card svc-detail-card" data-refuge-detail style="margin-top:16px;" hidden></article>
      </div>
    </section>

  </main>
<?php
$content     = ob_get_clean();
$title       = 'Zones refuge — WASLA';
$description = 'Trouvez un lieu d\'accueil climatisé, un point d\'eau potable ou une recharge électrique proche de vous pendant une coupure.';
$currentPage = 'refuges';
$pageCss     = ['home', 'pages', 'services'];
$pageScripts = ['data/tunisia.js', 'js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/services/refuges.js'];
require __DIR__ . '/../../layout.php';
