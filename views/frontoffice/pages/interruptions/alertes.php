<?php
/**
 * Vue : Service « Alertes personnalisées » — Front Office — Module 1 (Houssem).
 * Service 01 — Alertes personnalisées. Les listes sont remplies par assets/js/services/alertes.js (données de démonstration).
 */
ob_start();
?>
  <main id="main" data-service="alertes">
    <!-- Notifications réelles (InterruptionController::getAlertFeed), lues par js/services/alertes.js -->
    <script type="application/json" id="wasla-live-alerts"><?= json_encode($alertFeed, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Alertes personnalisées</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 01 · Notifications ciblées</p>
            <h1>Alertes personnalisées</h1>
            <p>Suivez les coupures d'eau et d'électricité, les fortes chaleurs et les incidents pour votre domicile, votre travail et les lieux de vos proches.</p>
          </div>
          <div class="page-hero__actions">
            <button class="btn btn--outline" type="button" data-scroll-to="alert-prefs">
              <span data-icon="settings"></span>
              Préférences
            </button>
            <button class="btn btn--primary" type="button" data-svc-open="place-modal">
              <span data-icon="map-pin"></span>
              Suivre un lieu
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice"><span data-icon="info"></span><span><strong>Source officielle :</strong> les notifications proviennent des interruptions publiées par STEG et SONEDE. Vos lieux, préférences et lectures sont enregistrés uniquement dans ce navigateur ; aucun e-mail ni SMS n'est envoyé.</span></p>

        <div class="summary-grid">
          <div class="summary-card">
            <span class="summary-card__icon" data-icon="bell"></span>
            <div><p class="summary-card__value" data-stat="unread">–</p><p class="summary-card__label">Notifications non lues</p></div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--red" data-icon="circle-alert"></span>
            <div><p class="summary-card__value" data-stat="critical">–</p><p class="summary-card__label">Alertes critiques en cours</p></div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--amber" data-icon="triangle-alert"></span>
            <div><p class="summary-card__value" data-stat="active">–</p><p class="summary-card__label">Alertes actives sur vos lieux</p></div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--green" data-icon="map-pin"></span>
            <div><p class="summary-card__value" data-stat="places">–</p><p class="summary-card__label">Lieux surveillés</p></div>
          </div>
        </div>

        <div class="filter-bar" data-alert-filters>
          <span class="filter-bar__label">Catégorie</span>
          <div class="svc-chips" data-category-chips></div>
          <span class="filter-bar__spacer"></span>
          <label class="sr-only" for="alert-severity">Gravité minimale</label>
          <select class="select" id="alert-severity" data-severity-filter style="width:auto;min-height:34px;padding-block:6px;font-size:12px;">
            <option value="">Toutes gravités</option>
            <option value="critique">Critique</option>
            <option value="elevee">Élevée et plus</option>
            <option value="moderee">Modérée et plus</option>
          </select>
          <div class="filter-search">
            <span data-icon="search"></span>
            <label class="sr-only" for="alert-search">Rechercher une alerte</label>
            <input id="alert-search" type="search" placeholder="Rechercher une alerte, un lieu…" data-alert-search>
          </div>
        </div>

        <div class="svc-layout">
          <div class="svc-stack">
            <article class="card">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Centre de notifications</h2>
                  <p class="svc-card-sub" data-list-caption>Chargement…</p>
                </div>
                <div class="svc-actions">
                  <div class="tabs svc-tabs" data-svc-tabs="alert-view" aria-label="Affichage des alertes">
                    <button class="tab is-active" type="button" data-tab="all">Toutes <span class="svc-count" data-count="all">0</span></button>
                    <button class="tab" type="button" data-tab="unread">Non lues <span class="svc-count" data-count="unread">0</span></button>
                    <button class="tab" type="button" data-tab="history">Historique <span class="svc-count" data-count="history">0</span></button>
                  </div>
                </div>
              </div>
              <div class="svc-list" data-alert-list aria-live="polite"></div>
              <div class="card__footer svc-toolbar" style="margin:0;">
                <span class="text-muted" style="font-size:11.5px;" data-active-filters></span>
                <div class="svc-actions">
                  <button class="text-link" type="button" data-reset-filters>Réinitialiser les filtres</button>
                  <button class="btn btn--outline btn--sm" type="button" data-mark-all>
                    <span data-icon="check"></span>
                    Tout marquer comme lu
                  </button>
                </div>
              </div>
            </article>
          </div>

          <aside class="svc-stack">
            <article class="card">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Mes lieux suivis</h2>
                  <p class="svc-card-sub">Domicile, travail, proches…</p>
                </div>
                <button class="btn btn--outline btn--sm" type="button" data-svc-open="place-modal">
                  <span data-icon="plus"></span>
                  Ajouter
                </button>
              </div>
              <div class="svc-list" data-place-list></div>
            </article>

            <article class="map-card svc-map" data-map-card>
              <div class="map-card__canvas" data-alert-map>
                <div class="map-card__label"><span class="live-dot" aria-hidden="true"></span>Zones concernées</div>
                <div class="map-card__legend" aria-label="Légende">
                  <span class="map-card__legend-title">Légende</span>
                  <span class="legend-item"><span class="legend-item__dot legend-item__dot--outage"></span>Critique</span>
                  <span class="legend-item"><span class="legend-item__dot legend-item__dot--watch"></span>Élevée</span>
                  <span class="legend-item"><span class="legend-item__dot legend-item__dot--normal"></span>Modérée / info</span>
                  <span class="legend-item"><span class="legend-item__dot" style="background:var(--color-network-blue)"></span>Mes lieux</span>
                </div>
                <p class="svc-map__empty" hidden>Aucune alerte à afficher</p>
              </div>
            </article>

            <article class="card" id="alert-prefs">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Préférences</h2>
                  <p class="svc-card-sub">Choisissez ce que vous voulez recevoir</p>
                </div>
              </div>
              <form class="card__body" data-pref-form novalidate>
                <div class="svc-setting">
                  <span class="svc-setting__label"><span class="svc-icon svc-icon--sm" data-icon="droplet"></span><span>Alertes eau<small>Coupures, pression, pénurie</small></span></span>
                  <button class="switch" type="button" role="switch" aria-checked="true" aria-label="Alertes eau" data-pref-switch="water"></button>
                </div>
                <div class="svc-setting">
                  <span class="svc-setting__label"><span class="svc-icon svc-icon--sm svc-icon--green" data-icon="zap"></span><span>Alertes électricité<small>Coupures, délestages, incidents</small></span></span>
                  <button class="switch" type="button" role="switch" aria-checked="true" aria-label="Alertes électricité" data-pref-switch="electricity"></button>
                </div>
                <div class="svc-setting">
                  <span class="svc-setting__label"><span class="svc-icon svc-icon--sm svc-icon--gray" data-icon="map-pin"></span><span>Uniquement mes lieux<small>Masquer les autres gouvernorats</small></span></span>
                  <button class="switch" type="button" role="switch" aria-checked="true" aria-label="Uniquement mes lieux" data-pref-switch="onlyMyPlaces"></button>
                </div>

                <div class="field" style="margin-top:16px;">
                  <label>Catégories reçues</label>
                  <div class="svc-check-grid" data-pref-categories></div>
                  <p class="field__error" id="pref-cat-error" data-error-for="categories"></p>
                </div>

                <div class="field" style="margin-top:16px;">
                  <label for="pref-min">Gravité minimale</label>
                  <select class="select" id="pref-min" name="minSeverity">
                    <option value="info">Toutes les alertes</option>
                    <option value="moderee">Modérée et plus</option>
                    <option value="elevee">Élevée et plus</option>
                    <option value="critique">Critique uniquement</option>
                  </select>
                </div>

                <div class="field" style="margin-top:16px;">
                  <label>Canaux de notification</label>
                  <div class="svc-check-grid">
                    <label class="checkbox"><input type="checkbox" name="channels" value="site"> Sur le site</label>
                    <label class="checkbox"><input type="checkbox" name="channels" value="email"> E-mail</label>
                    <label class="checkbox"><input type="checkbox" name="channels" value="sms"> SMS</label>
                  </div>
                  <p class="field__hint">E-mail et SMS : envoi non connecté dans ce prototype.</p>
                  <p class="field__error" id="pref-chan-error" data-error-for="channels"></p>
                </div>

                <div class="field" style="margin-top:16px;">
                  <label>Fréquence</label>
                  <div class="svc-radio-row">
                    <label class="svc-radio"><input type="radio" name="frequency" value="immediate"><span>Immédiate</span></label>
                    <label class="svc-radio"><input type="radio" name="frequency" value="daily"><span>Résumé quotidien</span></label>
                  </div>
                </div>

                <button class="btn btn--secondary btn--block" type="submit" style="margin-top:18px;">
                  <span data-icon="check"></span>
                  Enregistrer mes préférences
                </button>
              </form>
            </article>
          </aside>
        </div>
      </div>
    </section>

    <!-- Détail d'une alerte -->
    <div class="modal svc-modal" id="alert-modal" role="dialog" aria-modal="true" aria-labelledby="alert-modal-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="alert-modal-title">Détail de l'alerte</h2>
            <p data-alert-modal-sub></p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <div class="svc-modal__body" data-alert-modal-body></div>
        <div class="svc-modal__foot" data-alert-modal-foot></div>
      </div>
    </div>

    <!-- Ajout d'un lieu suivi -->
    <div class="modal svc-modal" id="place-modal" role="dialog" aria-modal="true" aria-labelledby="place-modal-title" hidden>
      <div class="modal__panel modal__panel--narrow">
        <div class="modal__head">
          <div>
            <h2 id="place-modal-title">Suivre un nouveau lieu</h2>
            <p>Vous recevrez les alertes qui concernent ce lieu.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid" data-place-form novalidate>
          <div class="field">
            <label>Type de lieu</label>
            <div class="svc-radio-row">
              <label class="svc-radio"><input type="radio" name="kind" value="domicile" data-required="Choisissez un type de lieu."><span><span data-icon="home"></span>Domicile</span></label>
              <label class="svc-radio"><input type="radio" name="kind" value="travail"><span><span data-icon="briefcase"></span>Travail</span></label>
              <label class="svc-radio"><input type="radio" name="kind" value="proche"><span><span data-icon="heart"></span>Proche</span></label>
              <label class="svc-radio"><input type="radio" name="kind" value="autre"><span><span data-icon="map-pin"></span>Autre</span></label>
            </div>
            <p class="field__error" id="place-kind-error" data-error-for="kind"></p>
          </div>
          <div class="field">
            <label for="place-label">Nom du lieu</label>
            <input class="input" id="place-label" name="label" placeholder="ex. Chez mes grands-parents" data-required="Donnez un nom à ce lieu." data-min="2" data-autofocus data-max="40">
            <p class="field__error" id="place-label-error" data-error-for="label"></p>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="place-gov">Gouvernorat</label>
              <select class="select" id="place-gov" name="gov" data-required="Choisissez un gouvernorat."></select>
              <p class="field__error" id="place-gov-error" data-error-for="gov"></p>
            </div>
            <div class="field">
              <label for="place-area">Quartier / délégation</label>
              <input class="input" id="place-area" name="area" placeholder="ex. El Menzah 6" data-required="Indiquez un quartier." data-min="2" data-max="60">
              <p class="field__error" id="place-area-error" data-error-for="area"></p>
            </div>
          </div>
          <p class="field__hint">Pas besoin d'adresse exacte : le quartier suffit pour recevoir les bonnes alertes.</p>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--primary btn--sm" type="submit"><span data-icon="plus"></span>Suivre ce lieu</button>
          </div>
        </form>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Alertes personnalisées — WASLA';
$description = 'Recevez les alertes de coupure d\'eau et d\'électricité, de fortes chaleurs et d\'incidents pour les lieux qui comptent pour vous.';
$currentPage = 'alertes';
$pageCss     = ['home', 'pages', 'services'];
$pageScripts = ['data/tunisia.js', 'js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/demo-data.js', 'js/services/core.js', 'js/services/alertes.js'];
require __DIR__ . '/../../layout.php';
