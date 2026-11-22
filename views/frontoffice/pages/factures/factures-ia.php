<?php
/**
 * Vue : Service « Factures & IA » — Front Office — Module 2 (Hiba).
 * Variables reçues : $user (UserModel, citoyen connecté), $bills (ses factures, FactureController::pourJs()),
 *                    $old (formulaire vide de la fenêtre « Ajouter une facture »).
 *
 * Toutes les factures viennent de la base de données. Elles sont passées au JavaScript
 * (assets/js/services/factures.js) dans l'attribut data-bills, en JSON échappé par e() :
 * aucun script en ligne (CSP). Indicateurs, graphiques, constats de Wasel, analyse et assistant
 * sont calculés à partir de ces factures ; l'analyse et l'assistant restent des règles automatiques (simulation).
 * La fenêtre « Ajouter une facture » reprend le parcours guidé du formulaire (partials/form-fields.php) :
 * mêmes contrôles que le CRUD (validation.js + factures-crud.js, puis FactureController::create() en PHP).
 */
require_once __DIR__ . '/partials/ui.php';
ob_start();
$errors = [];
$prenom = $user->getPrenom();
?>
  <main id="main" class="bill-ui" data-service="factures" data-prenom="<?= e($prenom) ?>"
        data-bills="<?= e(json_encode($bills, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
<?php require __DIR__ . '/partials/sprite.php'; ?>
    <div class="container">
      <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="<?= url('accueil') ?>">Accueil</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <a href="<?= url('services') ?>">Services</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
        <span aria-current="page">Factures &amp; IA</span>
      </nav>

      <!-- En-tête : accroche + Wasel, l'assistant -->
      <header class="bill-hero">
        <div>
          <p class="bill-eyebrow">Service 02 · Suivi et analyse</p>
          <h1>Vos factures,<br><span>enfin claires.</span></h1>
          <p class="bill-hero__lead">Suivez vos factures STEG et SONEDE, voyez où part votre argent et comprenez chaque hausse grâce à l'analyse automatique et à Wasel, votre assistant.</p>
          <div class="bill-hero__ctas">
            <button class="bill-btn bill-btn--glow" type="button" data-svc-open="bill-modal"><span data-icon="plus"></span>Ajouter une facture</button>
            <a class="bill-btn bill-btn--glass" href="<?= url('mes-factures') ?>"><span data-icon="list"></span>Gérer mes factures</a>
          </div>
          <ul class="bill-hero__trust" data-hero-trust hidden>
            <li><b data-trust="count">—</b>factures suivies</li>
            <li><b data-trust="due">—</b>reste à payer</li>
            <li><b data-trust="next">—</b>prochaine échéance</li>
          </ul>
        </div>

        <div class="bill-core">
          <svg class="bill-core__lines" viewBox="0 0 600 480" preserveAspectRatio="none" aria-hidden="true" focusable="false" data-core-lines hidden>
            <path d="M205 80 C 250 80, 240 230, 290 270"/><path d="M395 390 C 350 390, 350 320, 330 300"/>
            <circle cx="205" cy="80" r="3.5"/><circle cx="395" cy="390" r="3.5"/>
          </svg>
          <div class="bill-halo" aria-hidden="true"></div>
          <div class="bill-wasel-shadow" aria-hidden="true"></div>
<?php require __DIR__ . '/partials/wasel.php'; ?>
          <div class="bill-bubble bill-glass" data-wasel-bubble>
            <p class="bill-bubble__name">Wasel · votre assistant</p>
            <p class="bill-bubble__text" data-wasel-text>Bonjour <?= e($prenom) ?> ! Je suis Wasel : j'analyse vos factures STEG et SONEDE enregistrées.</p>
            <div class="bill-bubble__actions">
              <button class="bill-btn bill-btn--glow bill-btn--sm" type="button" data-wasel-cta>Parler à Wasel</button>
              <button class="bill-btn bill-btn--glass bill-btn--sm" type="button" data-wasel-later>Plus tard</button>
            </div>
          </div>
          <div class="bill-float bill-float--tl bill-glass" data-float-last hidden></div>
          <div class="bill-float bill-float--br bill-glass" data-float-conso hidden></div>
        </div>
      </header>

      <div class="bill-tabs bill-glass" data-svc-tabs="bills" aria-label="Sections du service" data-bills-tabs>
        <button class="tab is-active" type="button" data-tab="overview">Vue générale</button>
        <button class="tab" type="button" data-tab="bills">Mes factures <span class="bill-count" data-bill-count>0</span></button>
        <button class="tab" type="button" data-tab="analysis">Analyse</button>
        <button class="tab" type="button" data-tab="assistant">Assistant</button>
      </div>

      <!-- Aucune facture : un seul état vide, à la place du contenu des onglets -->
      <article class="bill-empty bill-glass" data-bills-empty hidden>
        <svg aria-hidden="true" focusable="false"><use href="#bill-wasel"/></svg>
        <h2>Aucune facture enregistrée</h2>
        <p>Ajoutez votre première facture STEG ou SONEDE : indicateurs, graphiques, analyse et Wasel seront calculés à partir de vos factures.</p>
        <button class="bill-btn bill-btn--glow" type="button" data-svc-open="bill-modal"><span data-icon="plus"></span>Ajouter une facture</button>
      </article>

      <!-- Vue générale -->
      <div data-tab-panel="overview" data-tabs-for="bills">
        <div class="bill-kpis" data-overview-stats></div>
        <div class="bill-charts">
          <article class="bill-panel bill-glass">
            <div class="bill-panel__head">
              <div>
                <h2 data-trend-title>Consommation d'électricité</h2>
                <p>Par mois, calculée au prorata des jours de vos factures (12 mois)</p>
              </div>
              <div class="bill-toggle" role="group" aria-label="Énergie affichée" data-trend-switch>
                <button type="button" aria-pressed="true" data-trend="electricite">Électricité</button>
                <button type="button" aria-pressed="false" data-trend="eau">Eau</button>
              </div>
            </div>
            <div data-trend-chart></div>
            <p class="bill-chart-note" data-trend-legend></p>
          </article>
          <article class="bill-panel bill-glass">
            <div class="bill-panel__head">
              <div>
                <h2>Répartition des dépenses</h2>
                <p>Factures terminées depuis un an</p>
              </div>
            </div>
            <div data-spend-chart></div>
          </article>
        </div>

        <div class="bill-list-head">
          <div>
            <h2>Dernières factures</h2>
            <p>Comparées à la facture précédente du même fournisseur</p>
          </div>
          <button class="bill-btn bill-btn--glass" type="button" data-goto-tab="bills">Toutes mes factures<span data-icon="arrow-right"></span></button>
        </div>
        <ul class="bill-rows" data-recent-rows></ul>
        <p class="bill-chart-note bill-row-note">* Variation de la consommation moyenne par jour par rapport à la facture précédente du même fournisseur.</p>

        <div class="bill-list-head">
          <div>
            <h2>Ce que Wasel a remarqué</h2>
            <p>Constats calculés à partir de vos factures (règles automatiques)</p>
          </div>
        </div>
        <div class="bill-insights" data-insights></div>
      </div>

      <!-- Mes factures -->
      <div data-tab-panel="bills" data-tabs-for="bills" hidden>
        <div class="bill-toolbar bill-glass">
          <div>
            <label for="bill-filter">Fournisseur</label>
            <select class="select bill-filter-select" id="bill-filter" data-bill-filter>
              <option value="">Tous les fournisseurs</option>
              <option value="electricite">Électricité (STEG)</option>
              <option value="eau">Eau (SONEDE)</option>
            </select>
          </div>
          <p class="bill-muted" data-bill-caption></p>
          <button class="bill-btn bill-btn--glow bill-btn--sm" type="button" data-svc-open="bill-modal"><span data-icon="plus"></span>Ajouter</button>
        </div>
        <ul class="bill-rows" data-bill-rows></ul>
      </div>

      <!-- Analyse -->
      <div data-tab-panel="analysis" data-tabs-for="bills" hidden>
        <div class="bill-toolbar bill-glass">
          <div>
            <label for="analysis-bill">Facture analysée</label>
            <select class="select" id="analysis-bill" data-analysis-bill></select>
          </div>
          <button class="bill-btn bill-btn--glass bill-btn--sm" type="button" data-goto-tab="assistant"><span data-icon="message-circle"></span>Poser une question à Wasel</button>
        </div>
        <div data-analysis></div>
      </div>

      <!-- Assistant : Wasel -->
      <div data-tab-panel="assistant" data-tabs-for="bills" hidden>
        <div class="bill-assist">
          <article class="bill-chat bill-glass">
            <div class="bill-chat__head">
              <span class="bill-chat__avatar"><svg aria-hidden="true" focusable="false"><use href="#bill-wasel"/></svg></span>
              <div>
                <h2>Wasel · Assistant WASLA</h2>
                <p>Démonstration · réponses calculées à partir de vos factures enregistrées</p>
              </div>
              <button class="bill-text-btn" type="button" data-chat-clear>Effacer la conversation</button>
            </div>
            <div class="bill-chat__log" data-chat-log aria-live="polite" aria-label="Conversation avec Wasel"></div>
            <div class="bill-chips" data-chat-suggestions aria-label="Questions suggérées"></div>
            <form class="bill-chat__form" data-chat-form>
              <label class="sr-only" for="chat-input">Votre question</label>
              <input class="input" id="chat-input" name="q" autocomplete="off" placeholder="Posez une question sur vos factures…">
              <button class="bill-btn bill-btn--glow" type="submit" aria-label="Envoyer"><span data-icon="send"></span></button>
            </form>
          </article>
          <aside class="bill-side">
            <article class="bill-insight bill-glass">
              <h3><span data-icon="sparkles"></span>Ce que Wasel sait faire</h3>
              <ul>
                <li><span data-icon="check"></span>Expliquer les lignes d'une facture</li>
                <li><span data-icon="check"></span>Comparer deux périodes et chiffrer une hausse</li>
                <li><span data-icon="check"></span>Proposer des causes possibles et des économies</li>
                <li><span data-icon="check"></span>Estimer la consommation d'un appareil</li>
              </ul>
              <p>Les réponses sont des hypothèses produites par des règles simples à partir de vos factures : elles ne remplacent pas le service client STEG ou SONEDE. La conversation n'est pas enregistrée.</p>
            </article>
          </aside>
        </div>
      </div>
    </div>

    <!-- Ajouter une facture : le parcours guidé du formulaire, dans une fenêtre.
         Sans JavaScript, le formulaire est envoyé normalement à facture-ajouter. -->
    <div class="modal svc-modal bill-scope" id="bill-modal" role="dialog" aria-modal="true" aria-labelledby="bill-modal-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="bill-modal-title">Ajouter une facture</h2>
            <p>Quatre étapes : fournisseur, références, période et montant, statut.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body bill-wizard" method="post" action="<?= url('facture-ajouter') ?>"
              data-json-action="<?= url('facture-ajouter-json') ?>" data-validate data-bill-crud-form data-bill-wizard>
          <?= csrfField() ?>
<?php
$p = 'm';
$etapes = true;
$submitLabel = 'Enregistrer la facture';
$cancelHtml = '<button class="bill-btn bill-btn--glass" type="button" data-svc-close>Annuler</button>';
require __DIR__ . '/partials/form-fields.php';
?>
        </form>
      </div>
    </div>

    <!-- Détail d'une facture -->
    <div class="modal svc-modal bill-scope" id="bill-detail" role="dialog" aria-modal="true" aria-labelledby="bill-detail-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="bill-detail-title">Facture</h2>
            <p data-bill-detail-sub></p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <div class="svc-modal__body" data-bill-detail-body></div>
        <div class="svc-modal__foot" data-bill-detail-foot></div>
      </div>
    </div>

    <!-- Suppression : POST + jeton CSRF (la confirmation est demandée par factures.js) -->
    <form method="post" action="<?= url('facture-supprimer') ?>" data-bill-delete-form hidden>
      <?= csrfField() ?>
      <input type="hidden" name="id_facture" value="">
      <input type="hidden" name="retour" value="factures">
    </form>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Factures & IA — WASLA';
$description = 'Suivez vos factures d\'eau et d\'électricité, comprenez vos consommations et obtenez des conseils de Wasel pour économiser.';
$currentPage = 'factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/factures-crud.js', 'js/services/factures.js'];
require __DIR__ . '/../../layout.php';
