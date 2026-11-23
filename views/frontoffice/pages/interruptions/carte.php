<?php
/**
 * Vue : Carte des 24 gouvernorats + formulaire « Signaler une coupure » — Front Office — Module 1 (Houssem).
 */
ob_start();
?>
  <main id="main">
    <!-- Statuts réels des gouvernorats (InterruptionController::getMapStatuses), lus par js/data.js -->
    <script type="application/json" id="wasla-live-statuses"><?= json_encode($mapStatuses, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Carte</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Réseau national</p>
            <h1>Carte des réseaux</h1>
            <p>Visualisez l'état de l'électricité et de l'eau dans les 24 gouvernorats. Filtrez par statut, recherchez une zone et ouvrez la carte en plein écran.</p>
          </div>
          <div class="page-hero__actions">
<?php if ($canReport): ?>
            <button class="btn btn--secondary" type="button" data-open-report>
              <span data-icon="triangle-alert"></span>
              Signaler une coupure
            </button>
<?php else: ?>
            <a class="btn btn--secondary" href="<?= url('connexion') ?>">
              <span data-icon="triangle-alert"></span>
              Connectez-vous pour signaler
            </a>
<?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <div class="summary-grid">
          <div class="summary-card">
            <span class="summary-card__icon" data-icon="map-pin"></span>
            <div>
              <p class="summary-card__value" data-summary="total">24</p>
              <p class="summary-card__label">Gouvernorats suivis</p>
            </div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--green" data-icon="circle-check"></span>
            <div>
              <p class="summary-card__value" data-summary="normal">19</p>
              <p class="summary-card__label">Réseaux normaux</p>
            </div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--amber" data-icon="triangle-alert"></span>
            <div>
              <p class="summary-card__value" data-summary="watch">3</p>
              <p class="summary-card__label">Zones en vigilance</p>
            </div>
          </div>
          <div class="summary-card">
            <span class="summary-card__icon summary-card__icon--red" data-icon="circle-alert"></span>
            <div>
              <p class="summary-card__value" data-summary="outage">1</p>
              <p class="summary-card__label">Coupure en cours</p>
            </div>
          </div>
        </div>

        <div class="filter-bar">
          <span class="filter-bar__label">Filtrer</span>
          <button class="chip is-active" type="button" data-status-filter="all">Tous <span data-count="all">24</span></button>
          <button class="chip" type="button" data-status-filter="normal"><span class="chip__dot chip__dot--normal"></span>Normale <span data-count="normal">19</span></button>
          <button class="chip" type="button" data-status-filter="watch"><span class="chip__dot chip__dot--watch"></span>Vigilance <span data-count="watch">3</span></button>
          <button class="chip" type="button" data-status-filter="outage"><span class="chip__dot chip__dot--outage"></span>Coupure <span data-count="outage">1</span></button>
          <button class="chip" type="button" data-status-filter="nodata"><span class="chip__dot chip__dot--nodata"></span>Sans données <span data-count="nodata">1</span></button>
          <span class="filter-bar__spacer"></span>
          <div class="filter-search">
            <span data-icon="search"></span>
            <label class="sr-only" for="zone-search">Rechercher un gouvernorat</label>
            <input id="zone-search" type="search" placeholder="Rechercher un gouvernorat…" data-zone-search>
          </div>
        </div>

        <div class="map-page-grid">
          <div class="map-card map-card--page" data-map-card>
            <div class="map-card__canvas" data-map-canvas data-map-mode="page">
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

          <aside class="map-page-side">
            <div class="card">
              <div class="card__header">
                <h2 class="card__title">Zones suivies</h2>
                <span class="status-badge status-badge--normal" data-zone-count-label>24 zones</span>
              </div>
              <div class="zone-list" data-zone-list></div>
              <div class="card__footer">
                <span><strong data-visible-count>24</strong> zones affichées</span>
                <button class="text-link" type="button" data-reset-filters>Réinitialiser</button>
              </div>
            </div>

            <!-- Interruptions du gouvernorat sélectionné (rempli par pages.js depuis les données du Back Office) -->
            <div class="card" data-zone-detail>
              <div class="card__header">
                <h2 class="card__title" data-zone-detail-title>Interruptions</h2>
              </div>
              <div class="card__body" data-zone-detail-body>
                <p class="text-muted" style="font-size:12.5px;">Sélectionnez un gouvernorat sur la carte ou dans la liste pour voir ses interruptions en cours et prévues.</p>
              </div>
            </div>

            <div class="card">
              <div class="card__header">
                <h2 class="card__title">Signaler une coupure</h2>
                <span class="status-badge status-badge--watch">Citoyen</span>
              </div>
              <div class="card__body">
                <p class="text-muted" style="font-size:12.5px;line-height:1.55;">Une coupure non signalée ? Aidez la communauté en partageant l'information en quelques secondes.</p>
<?php if ($canReport): ?>
                <button class="btn btn--primary btn--block" type="button" data-open-report style="margin-top:14px;">
                  <span data-icon="triangle-alert"></span>
                  Signaler maintenant
                </button>
<?php elseif ($user === null): ?>
                <p class="text-muted" style="font-size:12.5px;line-height:1.55;margin-top:10px;">Le signalement est réservé aux citoyens connectés.</p>
                <a class="btn btn--primary btn--block" href="<?= url('connexion') ?>" style="margin-top:14px;">
                  Se connecter
                </a>
<?php else: ?>
                <p class="text-muted" style="font-size:12.5px;line-height:1.55;margin-top:10px;">Le signalement est réservé aux comptes citoyens.</p>
<?php endif; ?>
              </div>
            </div>
          </aside>
        </div>

<?php if ($canReport): ?>
<?php
    $typeLabels   = ['electricite' => 'Électricité', 'eau' => 'Eau'];
    $statutLabels = [
        'en_attente' => ['En attente', 'watch'],
        'confirme'   => ['Confirmé', 'normal'],
        'rejete'     => ['Rejeté', 'outage'],
    ];
?>
        <div class="card" id="historique-signalements" style="margin-top:24px;">
          <div class="card__header">
            <h2 class="card__title">Historique de mes signalements</h2>
            <span class="status-badge status-badge--nodata"><?= count($historique) ?> signalement<?= count($historique) > 1 ? 's' : '' ?></span>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th scope="col">Date</th>
                  <th scope="col">Gouvernorat</th>
                  <th scope="col">Réseau</th>
                  <th scope="col">Adresse</th>
                  <th scope="col">Statut</th>
                </tr>
              </thead>
              <tbody>
<?php if (!$historique): ?>
                <tr class="data-table__empty"><td colspan="5">Vous n'avez encore envoyé aucun signalement.</td></tr>
<?php endif; ?>
<?php foreach ($historique as $s):
        [$statutLabel, $statutClass] = $statutLabels[$s->getStatut()] ?? [$s->getStatut(), 'nodata'];
        $ts = strtotime($s->getDateSignalement()); ?>
                <tr>
                  <td><?= e($ts ? date('d/m/Y H:i', $ts) : $s->getDateSignalement()) ?></td>
                  <td><strong><?= e($s->getNomZone()) ?></strong></td>
                  <td><?= e($typeLabels[$s->getTypeRessource()] ?? $s->getTypeRessource()) ?></td>
                  <td><?= e($s->getAdresse()) ?><?php if ($s->getMessage()): ?><br><span class="text-muted" style="font-size:12px;"><?= e($s->getMessage()) ?></span><?php endif; ?></td>
                  <td><span class="status-badge status-badge--<?= e($statutClass) ?>"><?= e($statutLabel) ?></span></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
<?php endif; ?>
      </div>
    </section>

<?php if ($canReport): ?>
    <div class="modal" data-report-modal data-server-zones<?= $reportErrors ? ' data-open-on-load' : '' ?> role="dialog" aria-modal="true" aria-labelledby="report-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="report-title">Signaler une coupure</h2>
            <p>Votre signalement sera visible par la communauté.</p>
          </div>
          <button class="modal__close" type="button" data-close-report aria-label="Fermer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
        <!-- Contrôle de saisie : data-validate + data-rules (validation.js), mêmes règles en PHP
             dans SignalementController::create() ; aucune validation HTML5 -->
        <form class="card__body form-grid" data-report-form data-validate method="post" action="<?= url('signaler') ?>">
          <?= csrfField() ?>
          <div class="form-row">
            <div class="field">
              <label for="report-zone">Gouvernorat</label>
              <select class="select<?= invalidClass($reportErrors, 'zone') ?>" id="report-zone" name="zone"
                      data-rules="required|int"<?= SignalementController::messageAttrs('zone') ?>>
                <option value="">Sélectionner un gouvernorat…</option>
<?php foreach ($zones as $zone): ?>
                <option value="<?= $zone->getIdZone() ?>"<?= ($reportOld['zone'] ?? '') === (string) $zone->getIdZone() ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($reportErrors, 'zone') ?>
            </div>
            <div class="field">
              <label for="report-type">Type de réseau</label>
              <select class="select<?= invalidClass($reportErrors, 'type') ?>" id="report-type" name="type" data-rules="required|in:electricite,eau"<?= SignalementController::messageAttrs('type') ?>>
                <option value="electricite"<?= ($reportOld['type'] ?? '') === 'electricite' ? ' selected' : '' ?>>Électricité</option>
                <option value="eau"<?= ($reportOld['type'] ?? '') === 'eau' ? ' selected' : '' ?>>Eau</option>
              </select>
              <?= fieldError($reportErrors, 'type') ?>
            </div>
          </div>
          <div class="field">
            <label for="report-address">Adresse ou zone</label>
            <input class="input<?= invalidClass($reportErrors, 'address') ?>" id="report-address" name="address" type="text" placeholder="Ex. Cité El Ons, rue 12"
                   value="<?= e($reportOld['address'] ?? '') ?>" data-rules="required|min:3|max:255"<?= SignalementController::messageAttrs('address') ?>>
            <?= fieldError($reportErrors, 'address') ?>
          </div>
          <div class="field">
            <label for="report-message">Description <span class="text-muted">(facultatif)</span></label>
            <textarea class="textarea<?= invalidClass($reportErrors, 'message') ?>" id="report-message" name="message" placeholder="Décrivez la situation…" data-rules="max:1000"><?= e($reportOld['message'] ?? '') ?></textarea>
            <?= fieldError($reportErrors, 'message') ?>
          </div>
          <div class="cluster" style="justify-content:flex-end;">
            <button class="btn btn--outline" type="button" data-close-report>Annuler</button>
            <button class="btn btn--primary" type="submit" data-loading="Envoi…">Envoyer le signalement</button>
          </div>
        </form>
      </div>
    </div>
<?php endif; ?>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Carte des réseaux — WASLA';
$description = 'Visualisez l\'état de l\'électricité et de l\'eau dans les 24 gouvernorats de Tunisie, filtrez par statut et signalez une coupure avec WASLA.';
$currentPage = 'carte';
$pageCss     = ['home', 'pages'];
$pageScripts = ['data/tunisia.js', 'js/data.js', 'js/icons.js', 'js/layout.js', 'js/map.js', 'js/pages.js'];
require __DIR__ . '/../../layout.php';
