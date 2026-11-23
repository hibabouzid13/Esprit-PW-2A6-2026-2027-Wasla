<?php
/**
 * Vue : Service « Priorité santé » — Front Office — Module 4 (Bechir).
 * Service 06 — Priorité santé. Chaque enregistrement est envoyé au serveur (action « sante », POST) et devient une
 *    demande d'assistance de catégorie « sante », traitée au Back Office. Suivi et coordination : sante.js (démonstration).
 *    L'onglet Coordination n'est proposé qu'aux comptes autorisés (admin, STEG, SONEDE).
 */
ob_start();
?>
  <main id="main" data-service="sante">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Priorité santé</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 06 · Besoins vitaux</p>
            <h1>Priorité santé</h1>
            <p>Signalez une personne dont la santé dépend de l'eau ou de l'électricité : elle sera identifiée en priorité par les équipes d'intervention lors des coupures.</p>
          </div>
          <div class="page-hero__actions">
            <button class="btn btn--outline" type="button" data-health-tab="mine">
              <span data-icon="clock"></span>
              Suivre mes demandes
            </button>
            <button class="btn btn--primary" type="button" data-health-tab="form">
              <span data-icon="user-plus"></span>
              Enregistrer une personne
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice svc-notice--danger"><span data-icon="circle-alert"></span><span><strong>WASLA ne remplace pas les services d'urgence.</strong> En cas d'urgence vitale, appelez immédiatement le <a href="tel:190"><strong>190</strong></a> (SAMU) ou le <a href="tel:198"><strong>198</strong></a> (Protection civile).</span></p>

        <div class="svc-intro">
          <article class="card">
            <div class="card__body">
              <h2 class="svc-section-title">Qui peut être enregistré ?</h2>
              <p class="text-muted" style="font-size:13px;line-height:1.6;margin-bottom:16px;">Toute personne pour qui une coupure prolongée représente un danger. Vous pouvez faire la démarche pour vous-même ou pour un proche.</p>
              <div class="svc-situations">
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="activity"></span><span><strong>Dialyse à domicile</strong>Besoin d'eau et d'électricité pendant les séances.</span></div>
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="wind"></span><span><strong>Oxygénothérapie</strong>Concentrateur d'oxygène électrique.</span></div>
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="heart-pulse"></span><span><strong>Appareil vital</strong>Respirateur, pompe, aspirateur de mucosités.</span></div>
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="pill"></span><span><strong>Médicaments au froid</strong>Insuline, vaccins, traitements thermosensibles.</span></div>
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="accessibility"></span><span><strong>Mobilité réduite</strong>Étage élevé, ascenseur indispensable.</span></div>
                <div class="svc-situation"><span class="svc-icon svc-icon--sm" data-icon="heart"></span><span><strong>Autre besoin vital</strong>Nourrisson, personne âgée isolée…</span></div>
              </div>
            </div>
          </article>
          <article class="card">
            <div class="card__body">
              <h2 class="svc-section-title">Vos données sont protégées</h2>
              <ul class="svc-bullets">
                <li><span data-icon="lock"></span>Les informations de santé ne sont jamais affichées publiquement.</li>
                <li><span data-icon="shield-check"></span>Seules les équipes habilitées (STEG, SONEDE, Protection civile) y accèdent, et uniquement pendant une coupure qui vous concerne.</li>
                <li><span data-icon="eye-off"></span>Dans les listes, le nom et le téléphone sont partiellement masqués.</li>
                <li><span data-icon="trash"></span>Vous pouvez modifier ou retirer une demande à tout moment.</li>
              </ul>
              <p class="svc-notice svc-notice--warning" style="margin:16px 0 0;"><span data-icon="info"></span><span><strong>Transmission :</strong> chaque enregistrement est envoyé à l'équipe d'assistance WASLA (connexion requise), qui le vérifie et le prend en charge. Le suivi détaillé ci-dessous reste une démonstration.</span></p>
            </div>
          </article>
        </div>

        <div class="summary-grid">
          <div class="summary-card"><span class="summary-card__icon" data-icon="users"></span><div><p class="summary-card__value" data-hstat="registered">–</p><p class="summary-card__label">Personnes enregistrées</p></div></div>
          <div class="summary-card"><span class="summary-card__icon summary-card__icon--gray" data-icon="clock"></span><div><p class="summary-card__value" data-hstat="attente">–</p><p class="summary-card__label">Demandes en attente</p></div></div>
          <div class="summary-card"><span class="summary-card__icon summary-card__icon--amber" data-icon="activity"></span><div><p class="summary-card__value" data-hstat="encours">–</p><p class="summary-card__label">En cours de traitement</p></div></div>
          <div class="summary-card"><span class="summary-card__icon summary-card__icon--green" data-icon="circle-check"></span><div><p class="summary-card__value" data-hstat="resolue">–</p><p class="summary-card__label">Demandes résolues</p></div></div>
        </div>
        <p class="field__hint" style="margin:-8px 0 16px;">Indicateurs nationaux de démonstration, auxquels s'ajoutent les demandes saisies dans ce navigateur.</p>

        <div class="svc-toolbar">
          <div class="tabs svc-tabs" data-svc-tabs="health" aria-label="Sections du service">
            <button class="tab is-active" type="button" data-tab="form">Nouvel enregistrement</button>
            <button class="tab" type="button" data-tab="mine">Mes demandes <span class="svc-count" data-my-count>0</span></button>
            <button class="tab" type="button" data-tab="coord"><span class="svc-inline-icon" data-icon="lock"></span>Coordination</button>
          </div>
        </div>

        <!-- Enregistrement -->
        <div class="svc-panel" data-tab-panel="form" data-tabs-for="health">
          <article class="card">
            <div class="card__header">
              <div>
                <h2 class="card__title" data-form-title>Enregistrer une personne prioritaire</h2>
                <p class="svc-card-sub">Les champs marqués * sont obligatoires. Comptez environ 3 minutes.</p>
              </div>
              <button class="btn btn--ghost btn--sm" type="button" data-cancel-edit hidden>Annuler la modification</button>
            </div>
            <form class="card__body" data-health-form data-endpoint="<?= e(url('sante')) ?>" novalidate>
              <?= csrfField() ?>
              <fieldset class="svc-fieldset">
                <legend><span>1</span>Personne concernée</legend>
                <div class="form-grid">
                  <div class="field">
                    <label>Pour qui faites-vous cette demande ? *</label>
                    <div class="svc-radio-row">
                      <label class="svc-radio"><input type="radio" name="forWhom" value="moi" data-required="Précisez pour qui est la demande."><span>Pour moi</span></label>
                      <label class="svc-radio"><input type="radio" name="forWhom" value="proche"><span>Pour un proche</span></label>
                    </div>
                    <p class="field__error" id="h-for-error" data-error-for="forWhom"></p>
                  </div>
                  <div class="form-row">
                    <div class="field">
                      <label for="h-first">Prénom *</label>
                      <input class="input" id="h-first" name="firstName" autocomplete="off" data-required="Indiquez le prénom." data-pattern="name" data-max="60">
                      <p class="field__error" id="h-first-error" data-error-for="firstName"></p>
                    </div>
                    <div class="field">
                      <label for="h-last">Nom *</label>
                      <input class="input" id="h-last" name="lastName" autocomplete="off" data-required="Indiquez le nom." data-pattern="name" data-max="60">
                      <p class="field__error" id="h-last-error" data-error-for="lastName"></p>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="field">
                      <label for="h-birth">Année de naissance *</label>
                      <input class="input" id="h-birth" name="birthYear" type="text" inputmode="numeric" placeholder="ex. 1952" data-required="Indiquez l'année de naissance." data-num-min="1915" data-num-max="2026" data-integer>
                      <p class="field__error" id="h-birth-error" data-error-for="birthYear"></p>
                    </div>
                    <div class="field svc-conditional" data-when-for="proche" hidden style="padding:0;border:0;background:none;">
                      <label for="h-relation">Votre lien avec cette personne *</label>
                      <select class="select" id="h-relation" name="relation" data-required="Précisez votre lien.">
                        <option value="">Choisir…</option>
                        <option>Enfant</option><option>Parent</option><option>Conjoint(e)</option><option>Voisin(e)</option><option>Aidant professionnel</option><option>Autre</option>
                      </select>
                      <p class="field__error" id="h-relation-error" data-error-for="relation"></p>
                    </div>
                  </div>
                </div>
              </fieldset>

              <fieldset class="svc-fieldset">
                <legend><span>2</span>Coordonnées et localisation</legend>
                <div class="form-grid">
                  <div class="form-row">
                    <div class="field">
                      <label for="h-phone">Téléphone de la personne *</label>
                      <input class="input" id="h-phone" name="phone" type="text" inputmode="tel" inputmode="tel" placeholder="ex. 22 123 456" data-required="Indiquez un numéro de téléphone." data-pattern="phone" data-max="17">
                      <p class="field__error" id="h-phone-error" data-error-for="phone"></p>
                    </div>
                    <div class="field">
                      <label for="h-email">E-mail <span class="field__optional">(facultatif)</span></label>
                      <input class="input" id="h-email" name="email" type="text" inputmode="email" data-pattern="email" data-max="120">
                      <p class="field__error" id="h-email-error" data-error-for="email"></p>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="field">
                      <label for="h-gov">Gouvernorat *</label>
                      <select class="select" id="h-gov" name="gov" data-required="Choisissez un gouvernorat."></select>
                      <p class="field__error" id="h-gov-error" data-error-for="gov"></p>
                    </div>
                    <div class="field">
                      <label for="h-area">Délégation / quartier *</label>
                      <input class="input" id="h-area" name="area" data-required="Indiquez le quartier." data-min="2" data-max="60">
                      <p class="field__error" id="h-area-error" data-error-for="area"></p>
                    </div>
                  </div>
                  <div class="field">
                    <label for="h-address">Adresse précise *</label>
                    <input class="input" id="h-address" name="address" placeholder="Rue, numéro, étage" data-required="L'adresse permet aux équipes de vous trouver rapidement." data-min="6" data-max="160">
                    <p class="field__hint"><span class="svc-inline-icon" data-icon="lock"></span> Visible uniquement par les équipes habilitées.</p>
                    <p class="field__error" id="h-address-error" data-error-for="address"></p>
                  </div>
                </div>
              </fieldset>

              <fieldset class="svc-fieldset">
                <legend><span>3</span>Besoin et priorité</legend>
                <div class="form-grid">
                  <div class="field">
                    <label for="h-need">Type de besoin *</label>
                    <select class="select" id="h-need" name="need" data-required="Choisissez le type de besoin."></select>
                    <p class="field__error" id="h-need-error" data-error-for="need"></p>
                  </div>

                  <div class="svc-conditional form-grid" data-when-need="dialyse" hidden>
                    <div class="form-row">
                      <div class="field">
                        <label for="h-sessions">Séances par semaine *</label>
                        <input class="input" id="h-sessions" name="sessions" type="text" inputmode="numeric" data-required="Indiquez le nombre de séances." data-num-min="1" data-num-max="14" data-integer>
                        <p class="field__error" id="h-sessions-error" data-error-for="sessions"></p>
                      </div>
                      <div class="field">
                        <label for="h-center">Centre de dialyse de rattachement</label>
                        <input class="input" id="h-center" name="center" data-max="80">
                      </div>
                    </div>
                  </div>
                  <div class="svc-conditional form-grid" data-when-need="oxygene respirateur" hidden>
                    <div class="form-row">
                      <div class="field">
                        <label for="h-autonomy">Autonomie de la batterie (heures) *</label>
                        <input class="input" id="h-autonomy" name="autonomy" type="text" inputmode="decimal" data-required="Indiquez l'autonomie (0 s'il n'y a pas de batterie)." data-num-min="0" data-num-max="72">
                        <p class="field__error" id="h-autonomy-error" data-error-for="autonomy"></p>
                      </div>
                      <div class="field">
                        <label for="h-device">Appareil utilisé</label>
                        <input class="input" id="h-device" name="device" placeholder="ex. Concentrateur 5 L/min" data-max="80">
                      </div>
                    </div>
                  </div>
                  <div class="svc-conditional form-grid" data-when-need="froid" hidden>
                    <div class="form-row">
                      <div class="field">
                        <label for="h-medication">Traitement concerné *</label>
                        <input class="input" id="h-medication" name="medication" placeholder="ex. Insuline" data-required="Indiquez le traitement." data-max="80">
                        <p class="field__error" id="h-medication-error" data-error-for="medication"></p>
                      </div>
                      <div class="field">
                        <label for="h-cold">Conservation sans froid (heures)</label>
                        <input class="input" id="h-cold" name="coldHours" type="text" inputmode="numeric" data-num-min="0" data-num-max="72" data-integer>
                      </div>
                    </div>
                  </div>
                  <div class="svc-conditional form-grid" data-when-need="mobilite" hidden>
                    <div class="form-row">
                      <div class="field">
                        <label for="h-floor">Étage *</label>
                        <input class="input" id="h-floor" name="floor" type="text" inputmode="numeric" data-required="Indiquez l'étage." data-num-min="0" data-num-max="40" data-integer>
                        <p class="field__error" id="h-floor-error" data-error-for="floor"></p>
                      </div>
                      <div class="field">
                        <label>Ascenseur dans l'immeuble</label>
                        <div class="svc-radio-row">
                          <label class="svc-radio"><input type="radio" name="lift" value="oui"><span>Oui</span></label>
                          <label class="svc-radio"><input type="radio" name="lift" value="non" checked><span>Non</span></label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="svc-conditional form-grid" data-when-need="autre" hidden>
                    <div class="field">
                      <label for="h-other">Précisez le besoin *</label>
                      <input class="input" id="h-other" name="otherNeed" data-required="Précisez le besoin." data-min="4" data-max="100">
                      <p class="field__error" id="h-other-error" data-error-for="otherNeed"></p>
                    </div>
                  </div>

                  <div class="field">
                    <label>Ressource indispensable *</label>
                    <div class="svc-radio-row">
                      <label class="svc-radio"><input type="radio" name="resource" value="eau" data-required="Choisissez la ressource indispensable."><span><span data-icon="droplet"></span>Eau</span></label>
                      <label class="svc-radio"><input type="radio" name="resource" value="electricite"><span><span data-icon="zap"></span>Électricité</span></label>
                      <label class="svc-radio"><input type="radio" name="resource" value="both"><span>Les deux</span></label>
                    </div>
                    <p class="field__error" id="h-resource-error" data-error-for="resource"></p>
                  </div>
                  <div class="field">
                    <label for="h-desc">Description de la situation *</label>
                    <textarea class="textarea" id="h-desc" name="description" placeholder="Que se passe-t-il en cas de coupure ? Combien de temps la personne peut-elle tenir ?" data-required="Décrivez la situation." data-min="20" data-max="600"></textarea>
                    <p class="field__error" id="h-desc-error" data-error-for="description"></p>
                  </div>
                  <div class="field">
                    <label>Degré de priorité *</label>
                    <div class="svc-radio-row" data-priority-row>
                      <label class="svc-radio"><input type="radio" name="priority" value="critique" data-required="Choisissez un degré de priorité."><span>Critique</span></label>
                      <label class="svc-radio"><input type="radio" name="priority" value="elevee"><span>Élevée</span></label>
                      <label class="svc-radio"><input type="radio" name="priority" value="moderee"><span>Modérée</span></label>
                    </div>
                    <p class="field__hint" data-priority-hint>La priorité sera vérifiée par l'équipe de coordination.</p>
                    <p class="field__error" id="h-priority-error" data-error-for="priority"></p>
                  </div>
                </div>
              </fieldset>

              <fieldset class="svc-fieldset">
                <legend><span>4</span>Proche ou aidant à prévenir</legend>
                <div class="form-grid">
                  <div class="form-row">
                    <div class="field">
                      <label for="h-cname">Nom de l'aidant *</label>
                      <input class="input" id="h-cname" name="caregiverName" data-required="Indiquez un proche ou un aidant." data-pattern="name" data-max="80">
                      <p class="field__error" id="h-cname-error" data-error-for="caregiverName"></p>
                    </div>
                    <div class="field">
                      <label for="h-cphone">Téléphone de l'aidant *</label>
                      <input class="input" id="h-cphone" name="caregiverPhone" type="text" inputmode="tel" inputmode="tel" placeholder="ex. 98 765 432" data-required="Indiquez le téléphone de l'aidant." data-pattern="phone" data-max="17">
                      <p class="field__error" id="h-cphone-error" data-error-for="caregiverPhone"></p>
                    </div>
                  </div>
                  <label class="checkbox"><input type="checkbox" name="consent" data-required="Votre consentement est nécessaire pour traiter ces données de santé."> J'accepte que ces informations, y compris de santé, soient utilisées uniquement par les équipes habilitées pour organiser une assistance prioritaire.</label>
                  <p class="field__error" id="h-consent-error" data-error-for="consent"></p>
                </div>
              </fieldset>

              <div class="svc-actions" style="justify-content:flex-end;margin-top:20px;">
                <button class="btn btn--outline" type="reset">Effacer</button>
                <button class="btn btn--primary" type="submit"><span data-icon="check"></span><span data-submit-label>Enregistrer la demande</span></button>
              </div>
            </form>
          </article>
        </div>

        <!-- Mes demandes -->
        <div class="svc-panel" data-tab-panel="mine" data-tabs-for="health" hidden>
          <div class="svc-stack" data-my-records></div>
        </div>

        <!-- Coordination (comptes autorisés) -->
        <div class="svc-panel" data-tab-panel="coord" data-tabs-for="health" hidden>
          <div data-coord></div>
        </div>
      </div>
    </section>

    <!-- Signaler une aggravation -->
    <div class="modal svc-modal" id="worse-modal" role="dialog" aria-modal="true" aria-labelledby="worse-title" hidden>
      <div class="modal__panel modal__panel--narrow">
        <div class="modal__head">
          <div>
            <h2 id="worse-title">Signaler une aggravation</h2>
            <p data-worse-sub></p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid" data-worse-form novalidate>
          <p class="svc-notice svc-notice--danger" style="margin:0;"><span data-icon="circle-alert"></span><span>Danger immédiat ? Appelez le <a href="tel:190"><strong>190</strong></a> sans attendre.</span></p>
          <div class="field">
            <label for="worse-text">Que s'est-il passé ? *</label>
            <textarea class="textarea" id="worse-text" name="details" style="min-height:100px;" data-required="Décrivez l'aggravation." data-min="10" data-autofocus data-max="400"></textarea>
            <p class="field__error" id="worse-text-error" data-error-for="details"></p>
          </div>
          <div class="field">
            <label for="worse-autonomy">Autonomie restante (heures)</label>
            <input class="input" id="worse-autonomy" name="autonomy" type="text" inputmode="decimal" data-num-min="0" data-num-max="72">
          </div>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--secondary btn--sm" type="submit"><span data-icon="triangle-alert"></span>Signaler</button>
          </div>
        </form>
      </div>
    </div>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Priorité santé — WASLA';
$description = 'Signalez une personne dont la santé dépend de l\'eau ou de l\'électricité pour qu\'elle soit prise en compte en priorité lors des coupures.';
$currentPage = 'sante';
$pageCss     = ['pages', 'services'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/demo-data.js', 'js/services/core.js', 'js/services/sante.js'];
require __DIR__ . '/../../layout.php';
