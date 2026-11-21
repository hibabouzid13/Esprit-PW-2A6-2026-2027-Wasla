<?php
/**
 * Vue : Service « Factures & IA » — Front Office — Module 2 (Hiba).
 * Variables reçues : $bills (factures du citoyen connecté, FactureController::pourJs()),
 *                    $old (formulaire vide de la fenêtre « Ajouter une facture »).
 *
 * Toutes les factures viennent de la base de données. Elles sont passées au JavaScript
 * (assets/js/services/factures.js) dans l'attribut data-bills, en JSON échappé par e() :
 * aucun script en ligne (CSP). Les graphiques, l'analyse et l'assistant sont calculés
 * à partir de ces factures ; l'analyse et l'assistant restent des règles automatiques (simulation).
 * La fenêtre « Ajouter une facture » applique les mêmes contrôles que le CRUD
 * (validation.js + factures-crud.js, puis FactureController::create() en PHP).
 */
ob_start();
$errors = [];
?>
  <main id="main" data-service="factures" data-bills="<?= e(json_encode($bills, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
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
            <p>Suivez vos factures STEG et SONEDE, vos consommations et vos dépenses, et comprenez chaque variation grâce à l'analyse automatique et à l'assistant.</p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('mes-factures') ?>">
              <span data-icon="file-text"></span>
              Gérer mes factures
            </a>
            <button class="btn btn--primary" type="button" data-svc-open="bill-modal">
              <span data-icon="plus"></span>
              Ajouter une facture
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice"><span data-icon="info"></span><span>Les factures affichées sont <strong>celles enregistrées dans votre compte</strong>. L'analyse et l'assistant sont <strong>simulés</strong> (règles automatiques, aucun modèle d'IA connecté) et la décomposition des montants utilise des tarifs de référence indicatifs. Recherche, filtres et modification : <a href="<?= url('mes-factures') ?>">Mes factures</a>.</span></p>

        <div class="analysis-toolbar">
          <div class="tabs svc-tabs" data-svc-tabs="bills" aria-label="Sections du service">
            <button class="tab is-active" type="button" data-tab="overview">Vue générale</button>
            <button class="tab" type="button" data-tab="bills">Mes factures <span class="svc-count" data-bill-count>0</span></button>
            <button class="tab" type="button" data-tab="analysis">Analyse</button>
            <button class="tab" type="button" data-tab="assistant">Assistant</button>
          </div>
        </div>

        <!-- Aucune facture : un seul état vide, à la place du contenu des onglets -->
        <article class="card bill-empty-card" data-bills-empty hidden>
          <div class="empty-state">
            <span class="svc-icon svc-icon--gray" data-icon="file-text"></span>
            <strong>Aucune facture enregistrée</strong>
            <p>Ajoutez votre première facture STEG ou SONEDE : graphiques, analyse et assistant seront calculés à partir de vos factures.</p>
            <button class="btn btn--primary btn--sm" type="button" data-svc-open="bill-modal"><span data-icon="plus"></span>Ajouter une facture</button>
          </div>
        </article>

        <!-- Vue générale -->
        <div class="svc-panel" data-tab-panel="overview" data-tabs-for="bills">
          <div class="stats-row" data-overview-stats></div>
          <div class="chart-grid">
            <article class="card chart-card">
              <div class="chart-card__head">
                <div>
                  <h2 class="chart-card__title" data-trend-title>Consommation d'électricité</h2>
                  <p class="chart-card__sub">Vos factures réparties par mois (au prorata des jours), sur 12 mois</p>
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
                  <p class="chart-card__sub">Montants de vos 6 dernières factures</p>
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
            <div class="table-wrap bill-table-wrap"><table class="data-table svc-table bill-list-table" data-recent-table></table></div>
          </article>
        </div>

        <!-- Mes factures -->
        <div class="svc-panel" data-tab-panel="bills" data-tabs-for="bills" hidden>
          <article class="card">
            <div class="card__header">
              <div>
                <h2 class="card__title">Factures enregistrées</h2>
                <p class="svc-card-sub" data-bill-caption></p>
              </div>
              <div class="svc-actions">
                <label class="sr-only" for="bill-filter">Fournisseur</label>
                <select class="select bill-filter-select" id="bill-filter" data-bill-filter>
                  <option value="">Tous les fournisseurs</option>
                  <option value="electricite">Électricité (STEG)</option>
                  <option value="eau">Eau (SONEDE)</option>
                </select>
                <button class="btn btn--primary btn--sm" type="button" data-svc-open="bill-modal"><span data-icon="plus"></span>Ajouter</button>
              </div>
            </div>
            <div class="table-wrap bill-table-wrap"><table class="data-table svc-table bill-list-table" data-bill-table></table></div>
          </article>
        </div>

        <!-- Analyse -->
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
                <div class="bill-chat-head">
                  <span class="svc-icon svc-icon--navy" data-icon="bot"></span>
                  <div>
                    <h2 class="card__title bill-chat-title">Assistant WASLA</h2>
                    <p class="svc-card-sub">Démonstration · réponses calculées à partir de vos factures enregistrées</p>
                  </div>
                </div>
                <button class="text-link" type="button" data-chat-clear>Effacer la conversation</button>
              </div>
              <div class="svc-chat__log" data-chat-log aria-live="polite" aria-label="Conversation avec l'assistant"></div>
              <div class="svc-chat__suggestions" data-chat-suggestions aria-label="Questions suggérées"></div>
              <form class="svc-chat__form" data-chat-form>
                <label class="sr-only" for="chat-input">Votre question</label>
                <input class="input" id="chat-input" name="q" autocomplete="off" placeholder="Posez une question sur vos factures…">
                <button class="btn btn--secondary" type="submit" aria-label="Envoyer"><span data-icon="send"></span><span class="svc-btn-label">Envoyer</span></button>
              </form>
            </article>
            <aside class="svc-stack">
              <article class="card">
                <div class="card__header"><h2 class="card__title bill-aside-title">Ce que l'assistant sait faire</h2></div>
                <div class="card__body">
                  <ul class="svc-bullets">
                    <li><span data-icon="check"></span>Expliquer les lignes d'une facture</li>
                    <li><span data-icon="check"></span>Comparer deux périodes et chiffrer une hausse</li>
                    <li><span data-icon="check"></span>Proposer des causes possibles et des économies</li>
                    <li><span data-icon="check"></span>Estimer la consommation d'un appareil</li>
                  </ul>
                  <p class="field__hint bill-aside-hint">Les réponses sont des hypothèses produites par des règles simples à partir de vos factures : elles ne remplacent pas le service client STEG ou SONEDE. La conversation n'est pas enregistrée.</p>
                </div>
              </article>
            </aside>
          </div>
        </div>
      </div>
    </section>

    <!-- Ajouter une facture : mêmes champs et mêmes contrôles que le formulaire du CRUD.
         Sans JavaScript, le formulaire est envoyé normalement à facture-ajouter. -->
    <div class="modal svc-modal" id="bill-modal" role="dialog" aria-modal="true" aria-labelledby="bill-modal-title" hidden>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="bill-modal-title">Ajouter une facture</h2>
            <p>Recopiez les informations principales de votre facture STEG ou SONEDE.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid bill-modal-form" method="post" action="<?= url('facture-ajouter') ?>"
              data-json-action="<?= url('facture-ajouter-json') ?>" data-validate data-bill-crud-form>
          <?= csrfField() ?>

          <div class="form-row">
            <div class="field">
              <label for="m-type">Fournisseur</label>
              <select class="select" id="m-type" name="type_ressource" data-rules="required|in:electricite,eau" data-message-required="Choisissez le fournisseur.">
                <option value="">Choisir…</option>
<?php foreach (FactureModel::TYPES as $t): ?>
                <option value="<?= $t ?>"><?= e(FactureController::typeLabel($t)) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'type_ressource') ?>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="m-abo">Référence d'abonnement</label>
              <input class="input" id="m-abo" name="reference_abonnement" type="text" inputmode="numeric" autocomplete="off"
                     placeholder="Choisissez d'abord le fournisseur" aria-describedby="m-abo-hint"
                     data-rules="required" data-message-required="Indiquez la référence d'abonnement (en haut de la facture).">
              <p class="field__hint" id="m-abo-hint" data-bill-abo-hint>Choisissez le fournisseur pour voir le format attendu.</p>
              <?= fieldError($errors, 'reference_abonnement') ?>
            </div>
            <div class="field">
              <label for="m-paie">Référence de paiement (talon) <span class="field__optional">(facultatif)</span></label>
              <input class="input" id="m-paie" name="reference_paiement" type="text" inputmode="numeric" autocomplete="off"
                     placeholder="Choisissez d'abord le fournisseur" aria-describedby="m-paie-hint">
              <p class="field__hint" id="m-paie-hint" data-bill-paie-hint>Facultatif : talon en bas de la facture.</p>
              <?= fieldError($errors, 'reference_paiement') ?>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="m-debut">Début de la période</label>
              <input class="input" id="m-debut" name="periode_debut" type="date" data-rules="required|date" data-message-required="Indiquez le début de la période facturée.">
              <?= fieldError($errors, 'periode_debut') ?>
            </div>
            <div class="field">
              <label for="m-fin">Fin de la période</label>
              <input class="input" id="m-fin" name="periode_fin" type="date" data-rules="required|date" data-message-required="Indiquez la fin de la période facturée.">
              <?= fieldError($errors, 'periode_fin') ?>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="m-qte">Consommation (<span data-bill-unit>kWh</span>)</label>
              <input class="input" id="m-qte" name="quantite" type="text" autocomplete="off" placeholder="ex. 455 ou 455 kWh"
                     data-rules="required|max:20" data-message-required="Indiquez la consommation facturée." data-message-max="Valeur trop longue.">
              <?= fieldError($errors, 'quantite') ?>
            </div>
            <div class="field">
              <label for="m-montant">Montant (TND)</label>
              <input class="input" id="m-montant" name="montant" type="text" autocomplete="off" placeholder="ex. 500 ou 500 TND"
                     data-rules="required|max:20" data-message-required="Indiquez le montant à payer." data-message-max="Valeur trop longue.">
              <?= fieldError($errors, 'montant') ?>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="m-echeance">Date limite de paiement <span class="field__optional">(facultatif)</span></label>
              <input class="input" id="m-echeance" name="date_echeance" type="date" data-rules="date">
              <?= fieldError($errors, 'date_echeance') ?>
            </div>
            <fieldset class="field svc-fieldset">
              <legend>Statut</legend>
              <div class="svc-radio-row">
<?php foreach (FactureModel::STATUTS as $s): ?>
                <label class="svc-radio"><input type="radio" name="statut" value="<?= $s ?>"<?= $old['statut'] === $s ? ' checked' : '' ?>
                       data-rules="required|in:a_payer,payee" data-message-required="Choisissez le statut."><span><?= e(FactureController::statutLabel($s)) ?></span></label>
<?php endforeach; ?>
              </div>
              <?= fieldError($errors, 'statut') ?>
            </fieldset>
          </div>

          <div class="svc-actions bill-modal__actions">
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
$description = 'Suivez vos factures d\'eau et d\'électricité, comprenez vos consommations et obtenez des conseils pour économiser.';
$currentPage = 'factures';
$pageCss     = ['pages', 'services', 'factures'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/factures-crud.js', 'js/services/factures.js'];
require __DIR__ . '/../../layout.php';
