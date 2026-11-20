<?php
/**
 * Vue : Service « Factures & IA » — Front Office — Module 2 (Hiba).
 * Service 02 — Factures & IA. Les données, graphiques et réponses de l'assistant sont produits par assets/js/services/factures.js (démonstration).
 */
ob_start();
?>
  <main id="main" data-service="factures">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Factures &amp; IA</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 02 · Suivi et analyse</p>
            <h1>Factures &amp; IA</h1>
            <p>Centralisez vos factures STEG et SONEDE, suivez vos consommations et comprenez chaque variation grâce à l'analyse automatique et à l'assistant.</p>
          </div>
          <div class="page-hero__actions">
            <button class="btn btn--outline" type="button" data-svc-open="bill-modal">
              <span data-icon="plus"></span>
              Ajouter une facture
            </button>
            <button class="btn btn--primary" type="button" data-goto-tab="import">
              <span data-icon="upload"></span>
              Importer une facture
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice"><span data-icon="info"></span><span><strong>Prototype :</strong> factures, montants et tarifs sont fictifs et indicatifs. La lecture des documents et l'assistant sont <strong>simulés</strong> (règles automatiques, aucun modèle d'IA connecté) ; les factures ajoutées restent dans ce navigateur. Pour enregistrer vos vraies factures dans votre compte : <a href="<?= url('mes-factures') ?>">Mes factures</a>.</span></p>

        <div class="analysis-toolbar">
          <div class="tabs svc-tabs" data-svc-tabs="bills" aria-label="Sections du service">
            <button class="tab is-active" type="button" data-tab="overview">Vue générale</button>
            <button class="tab" type="button" data-tab="bills">Mes factures <span class="svc-count" data-bill-count>0</span></button>
            <button class="tab" type="button" data-tab="analysis">Analyse IA</button>
            <button class="tab" type="button" data-tab="assistant">Assistant</button>
          </div>
          <div class="analysis-toolbar__right">
            <span class="status status--normal"><span class="status__dot"></span>Compteurs : relevés mensuels (démo)</span>
          </div>
        </div>

        <!-- Vue générale -->
        <div class="svc-panel" data-tab-panel="overview" data-tabs-for="bills">
          <div class="stats-row" data-overview-stats></div>
          <div class="chart-grid">
            <article class="card chart-card">
              <div class="chart-card__head">
                <div>
                  <h2 class="chart-card__title" data-trend-title>Consommation d'électricité</h2>
                  <p class="chart-card__sub">12 derniers mois — comparaison avec le mois précédent au survol</p>
                </div>
                <div class="segmented" role="group" aria-label="Énergie affichée" data-trend-switch>
                  <button type="button" aria-pressed="true" data-trend="electricite">Électricité</button>
                  <button type="button" aria-pressed="false" data-trend="eau">Eau</button>
                </div>
              </div>
              <div class="chart-card__body" data-trend-chart></div>
              <div class="chart-legend" data-trend-legend></div>
            </article>

            <article class="card chart-card">
              <div class="chart-card__head">
                <div>
                  <h2 class="chart-card__title">Dépenses par facture</h2>
                  <p class="chart-card__sub">Montants TTC des 6 dernières factures</p>
                </div>
              </div>
              <div class="chart-card__body" data-spend-chart></div>
              <div class="chart-legend">
                <span class="chart-legend__item"><span class="chart-legend__swatch"></span>Électricité (STEG)</span>
                <span class="chart-legend__item"><span class="chart-legend__swatch chart-legend__swatch--water"></span>Eau (SONEDE)</span>
              </div>
            </article>
          </div>

          <article class="card">
            <div class="card__header">
              <div>
                <h2 class="card__title">Dernières factures</h2>
                <p class="svc-card-sub">Comparaison avec la facture précédente du même fournisseur</p>
              </div>
              <button class="text-link" type="button" data-goto-tab="bills">Toutes mes factures <span data-icon="arrow-right"></span></button>
            </div>
            <div class="table-wrap"><table class="data-table svc-table" data-recent-table></table></div>
          </article>
        </div>

        <!-- Mes factures -->
        <div class="svc-panel" data-tab-panel="bills" data-tabs-for="bills" hidden>
          <div class="svc-layout">
            <article class="card">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Factures enregistrées</h2>
                  <p class="svc-card-sub" data-bill-caption></p>
                </div>
                <div class="svc-actions">
                  <label class="sr-only" for="bill-filter">Fournisseur</label>
                  <select class="select" id="bill-filter" data-bill-filter style="width:auto;min-height:38px;padding-block:7px;font-size:13px;">
                    <option value="">Tous les fournisseurs</option>
                    <option value="electricite">Électricité (STEG)</option>
                    <option value="eau">Eau (SONEDE)</option>
                  </select>
                </div>
              </div>
              <div class="table-wrap"><table class="data-table svc-table" data-bill-table></table></div>
            </article>

            <aside class="svc-stack" id="import">
              <article class="card">
                <div class="card__header"><h2 class="card__title">Importer une facture</h2></div>
                <form class="card__body form-grid" data-import-form novalidate>
                  <label class="svc-dropzone" for="bill-file" tabindex="0" data-dropzone>
                    <span data-icon="upload"></span>
                    <strong>Déposez votre facture ici</strong>
                    <span>ou cliquez pour choisir un fichier</span>
                    <span class="field__hint">PDF, JPG ou PNG · 5 Mo maximum</span>
                  </label>
                  <input class="sr-only" id="bill-file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" data-required="Choisissez un fichier PDF ou une image de votre facture." data-accept="pdf,jpg,jpeg,png" data-max-mb="5">
                  <p class="field__error" id="bill-file-error" data-error-for="file"></p>
                  <p class="field__hint" data-file-name></p>
                  <div class="field">
                    <label for="import-type">Type de facture</label>
                    <select class="select" id="import-type" name="type" data-required="Précisez le type de facture.">
                      <option value="">Choisir…</option>
                      <option value="electricite">Électricité (STEG)</option>
                      <option value="eau">Eau (SONEDE)</option>
                    </select>
                    <p class="field__error" id="import-type-error" data-error-for="type"></p>
                  </div>
                  <button class="btn btn--primary btn--block" type="submit"><span data-icon="sparkles"></span>Lire la facture</button>
                </form>
              </article>
              <article class="card">
                <div class="card__body">
                  <h2 class="svc-section-title">Pas de fichier sous la main ?</h2>
                  <p class="text-muted" style="font-size:12.5px;line-height:1.55;">Saisissez les informations principales de votre facture : période, consommation et montant.</p>
                  <button class="btn btn--outline btn--block" type="button" data-svc-open="bill-modal" style="margin-top:12px;"><span data-icon="pencil"></span>Saisie manuelle</button>
                </div>
              </article>
            </aside>
          </div>
        </div>

        <!-- Analyse IA -->
        <div class="svc-panel" data-tab-panel="analysis" data-tabs-for="bills" hidden>
          <div class="svc-toolbar">
            <div class="svc-toolbar__group">
              <label class="filter-bar__label" for="analysis-bill">Facture analysée</label>
              <select class="select" id="analysis-bill" data-analysis-bill></select>
            </div>
            <button class="btn btn--outline btn--sm" type="button" data-goto-tab="assistant"><span data-icon="message-circle"></span>Poser une question à l'assistant</button>
          </div>
          <div data-analysis></div>
        </div>

        <!-- Assistant -->
        <div class="svc-panel" data-tab-panel="assistant" data-tabs-for="bills" hidden>
          <div class="svc-layout">
            <article class="card svc-chat">
              <div class="card__header">
                <div style="display:flex;align-items:center;gap:11px;">
                  <span class="svc-icon svc-icon--navy" data-icon="bot"></span>
                  <div>
                    <h2 class="card__title" style="font-size:16px;">Assistant WASLA</h2>
                    <p class="svc-card-sub">Démonstration · réponses générées à partir de vos factures enregistrées</p>
                  </div>
                </div>
                <button class="text-link" type="button" data-chat-clear>Effacer l'historique</button>
              </div>
              <div class="svc-chat__log" data-chat-log aria-live="polite" aria-label="Conversation avec l'assistant"></div>
              <div class="svc-chat__suggestions" data-chat-suggestions aria-label="Questions suggérées"></div>
              <form class="svc-chat__form" data-chat-form novalidate>
                <label class="sr-only" for="chat-input">Votre question</label>
                <input class="input" id="chat-input" name="q" autocomplete="off" placeholder="Posez une question sur vos factures…" data-max="300">
                <button class="btn btn--secondary" type="submit" aria-label="Envoyer"><span data-icon="send"></span><span class="svc-btn-label">Envoyer</span></button>
              </form>
            </article>
            <aside class="svc-stack">
              <article class="card">
                <div class="card__header"><h2 class="card__title" style="font-size:15px;">Ce que l'assistant sait faire</h2></div>
                <div class="card__body">
                  <ul class="svc-bullets">
                    <li><span data-icon="check"></span>Expliquer les lignes d'une facture</li>
                    <li><span data-icon="check"></span>Comparer deux périodes et chiffrer une hausse</li>
                    <li><span data-icon="check"></span>Proposer des causes possibles et des économies</li>
                    <li><span data-icon="check"></span>Estimer la consommation d'un appareil</li>
                  </ul>
                  <p class="field__hint" style="margin-top:14px;">Les réponses sont des hypothèses produites par des règles simples à partir de vos données : elles ne remplacent pas le service client STEG ou SONEDE.</p>
                </div>
              </article>
            </aside>
          </div>
        </div>
      </div>
    </section>

    <!-- Saisie / vérification d'une facture -->
    <div class="modal svc-modal" id="bill-modal" role="dialog" aria-modal="true" aria-labelledby="bill-modal-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="bill-modal-title">Ajouter une facture</h2>
            <p data-bill-modal-sub>Saisie manuelle des informations principales.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid" data-bill-form novalidate>
          <p class="svc-notice svc-notice--warning" data-extract-note hidden style="margin:0;"><span data-icon="sparkles"></span><span><strong>Valeurs proposées par la lecture simulée.</strong> Vérifiez-les avec votre facture papier avant d'enregistrer.</span></p>
          <div class="form-row">
            <div class="field">
              <label for="bill-type">Fournisseur</label>
              <select class="select" id="bill-type" name="type" data-required="Choisissez le fournisseur.">
                <option value="">Choisir…</option>
                <option value="electricite">STEG — électricité</option>
                <option value="eau">SONEDE — eau</option>
              </select>
              <p class="field__error" id="bill-type-error" data-error-for="type"></p>
            </div>
            <div class="field">
              <label for="bill-ref">Référence de la facture</label>
              <input class="input" id="bill-ref" name="reference" placeholder="ex. STG-482338" data-required="Indiquez la référence." data-min="4" data-max="24">
              <p class="field__error" id="bill-ref-error" data-error-for="reference"></p>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="bill-start">Début de période</label>
              <input class="input" id="bill-start" name="periodStart" type="date" data-required="Date de début requise.">
              <p class="field__error" id="bill-start-error" data-error-for="periodStart"></p>
            </div>
            <div class="field">
              <label for="bill-end">Fin de période</label>
              <input class="input" id="bill-end" name="periodEnd" type="date" data-required="Date de fin requise.">
              <p class="field__error" id="bill-end-error" data-error-for="periodEnd"></p>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="bill-qty">Consommation <span data-unit>(kWh ou m³)</span></label>
              <input class="input" id="bill-qty" name="quantity" type="text" inputmode="numeric" data-required="Indiquez la consommation." data-num-min="1" data-num-max="99999" data-integer>
              <p class="field__error" id="bill-qty-error" data-error-for="quantity"></p>
            </div>
            <div class="field">
              <label for="bill-amount">Montant TTC (TND)</label>
              <input class="input" id="bill-amount" name="amount" type="text" inputmode="decimal" data-required="Indiquez le montant." data-num-min="0.1" data-num-max="99999">
              <p class="field__error" id="bill-amount-error" data-error-for="amount"></p>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="bill-due">Date limite de paiement</label>
              <input class="input" id="bill-due" name="dueDate" type="date">
              <p class="field__error" id="bill-due-error" data-error-for="dueDate"></p>
            </div>
            <div class="field">
              <label>Statut</label>
              <div class="svc-radio-row">
                <label class="svc-radio"><input type="radio" name="status" value="a-payer" checked><span>À payer</span></label>
                <label class="svc-radio"><input type="radio" name="status" value="payee"><span>Payée</span></label>
              </div>
            </div>
          </div>
          <input type="hidden" name="source" value="manual">
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--primary btn--sm" type="submit"><span data-icon="check"></span>Enregistrer la facture</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Détail d'une facture -->
    <div class="modal svc-modal" id="bill-detail" role="dialog" aria-modal="true" aria-labelledby="bill-detail-title" hidden>
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
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Factures & IA — WASLA';
$description = 'Suivez vos factures d\'eau et d\'électricité, comprenez vos consommations et obtenez des conseils pour économiser.';
$currentPage = 'factures';
$pageCss     = ['pages', 'services'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/demo-data.js', 'js/services/core.js', 'js/services/factures.js'];
require __DIR__ . '/../../layout.php';
