<?php
/**
 * Vue : Service « Relais de voisinage » — Front Office — Module 4 (Bechir).
 * Publications enregistrées dans demande_assistance (id_fournisseur NULL), réponses dans reponse.
 *
 * Variables reçues (routes/module4.php → 'relais') :
 *   $demandes (DemandeAssistanceModel[]), $reponses (id_demande => ReponseModel[]), $counts, $filters,
 *   $user (?UserModel), $ctrl, $repondues, $installations, $gouvernorats (ZoneModel[]),
 *   $errors, $old, $openModal (formulaire de publication), $replyTo, $replyErrors, $replyOld.
 */
$retour = 'relais';
$tabs = ['all' => 'Toutes', 'demande' => "Demandes d'aide", 'offre' => "Offres d'aide", 'mine' => 'Mes publications'];
$activeTab = isset($tabs[$filters['tab']]) ? $filters['tab'] : 'all';
$filterParams = array_filter([
    'categorie' => $filters['categorie'], 'id_zone' => $filters['id_zone'], 'q' => $filters['q'],
    'resolues' => $filters['resolues'], 'tri' => $filters['tri'],
], fn ($v) => $v !== '');
$urgenceBadge = ['haute' => 'outage', 'moyenne' => 'watch', 'faible' => 'normal'];
$statutBadge = ['ouverte' => 'svc-tag', 'prise_en_charge' => 'svc-tag svc-tag--blue', 'resolue' => 'svc-tag svc-tag--green', 'annulee' => 'svc-tag'];
ob_start();
?>
  <main id="main" data-service="relais">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Relais de voisinage</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 05 · Entraide locale</p>
            <h1>Relais de voisinage</h1>
            <p>Pendant une coupure, un voisin a souvent ce qui vous manque. Demandez de l'aide, proposez la vôtre : bénévoles, fournisseurs et propriétaires de panneaux solaires de votre quartier vous répondent.</p>
          </div>
          <div class="page-hero__actions">
<?php if ($user !== null): ?>
            <button class="btn btn--outline" type="button" data-svc-open="post-modal" data-post-sens="offre">
              <span data-icon="handshake"></span>
              Proposer mon aide
            </button>
            <button class="btn btn--primary" type="button" data-svc-open="post-modal" data-post-sens="demande">
              <span data-icon="plus"></span>
              Demander de l'aide
            </button>
<?php else: ?>
            <a class="btn btn--primary" href="<?= url('connexion') ?>"><span data-icon="user"></span> Se connecter pour publier</a>
<?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container">
        <p class="svc-notice"><span data-icon="shield-check"></span><span><strong>Confidentialité :</strong> seuls le prénom, l'initiale du nom et le quartier sont affichés, jamais d'adresse exacte ni de numéro de téléphone.</span></p>

        <div class="svc-toolbar">
          <nav class="tabs svc-tabs" aria-label="Type de publications">
<?php foreach ($tabs as $key => $label):
    if ($key === 'mine' && $user === null) continue;
    $params = $filterParams + ($key === 'all' ? [] : ['tab' => $key]);
?>
            <a class="tab<?= $activeTab === $key ? ' is-active' : '' ?>" href="<?= url('relais', $params) ?>"<?= $activeTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="svc-count"><?= (int) $counts[$key] ?></span></a>
<?php endforeach; ?>
          </nav>
        </div>

        <div class="svc-layout--three svc-layout" style="margin-top:0;">
          <aside class="svc-stack">
            <form class="card svc-filter-card" method="get" action="index.php">
              <input type="hidden" name="action" value="relais">
<?php if ($activeTab !== 'all'): ?>
              <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
<?php endif; ?>
              <div class="card__header"><h2 class="card__title" style="font-size:15px;">Filtres</h2><a class="text-link" href="<?= url('relais', $activeTab === 'all' ? [] : ['tab' => $activeTab]) ?>">Réinitialiser</a></div>
              <div class="card__body form-grid" style="gap:12px;">
                <div class="field">
                  <label for="f-q">Recherche</label>
                  <input class="input" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Titre, quartier…">
                </div>
                <div class="field">
                  <label for="f-zone">Gouvernorat</label>
                  <select class="select" id="f-zone" name="id_zone">
                    <option value="">Tous</option>
<?php foreach ($gouvernorats as $g): ?>
                    <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $filters['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label for="f-cat">Catégorie</label>
                  <select class="select" id="f-cat" name="categorie">
                    <option value="">Toutes</option>
<?php foreach (DemandeAssistanceModel::CATEGORIES as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $key === $filters['categorie'] ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label for="f-tri">Trier</label>
                  <select class="select" id="f-tri" name="tri">
                    <option value="recent">Plus récentes</option>
                    <option value="urgent"<?= $filters['tri'] === 'urgent' ? ' selected' : '' ?>>Plus urgentes</option>
                  </select>
                </div>
<?php if ($activeTab !== 'mine'): ?>
                <label class="checkbox"><input type="checkbox" name="resolues" value="1"<?= $filters['resolues'] !== '' ? ' checked' : '' ?>> Afficher les demandes résolues</label>
<?php endif; ?>
                <button class="btn btn--secondary btn--sm" type="submit"><span data-icon="search"></span>Filtrer</button>
              </div>
            </form>
          </aside>

          <div class="svc-stack" aria-live="polite">
<?php if ($demandes === []): ?>
            <article class="card">
              <div class="empty-state">
                <span data-icon="<?= $activeTab === 'mine' ? 'user' : 'handshake' ?>"></span>
                <strong><?= $activeTab === 'mine' ? "Vous n'avez encore rien publié" : 'Aucune publication' ?></strong>
                <p><?= $activeTab === 'mine' ? 'Publiez une demande ou une offre : elle apparaîtra ici.' : 'Aucune publication ne correspond à vos filtres pour le moment.' ?></p>
              </div>
            </article>
<?php endif; ?>
<?php foreach ($demandes as $demande):
    $isRequest = $demande->getSens() === 'demande';
    $isMine = $ctrl->isOwner($demande, $user);
    $reps = $reponses[$demande->getIdDemande()] ?? [];
    $author = $isMine ? 'Vous' : $demande->getAuteurPrenom() . ' ' . mb_substr((string) $demande->getAuteurNom(), 0, 1) . '.';
?>
            <article class="card svc-post svc-post--<?= $isRequest ? 'request' : 'offer' ?><?= in_array($demande->getStatut(), ['resolue', 'annulee'], true) ? ' is-resolved' : '' ?>" id="demande-<?= $demande->getIdDemande() ?>">
              <div class="svc-post__head">
                <span class="svc-avatar svc-avatar--sm svc-avatar--<?= $isRequest ? 'amber' : 'green' ?>" aria-hidden="true" data-no-i18n><?= e(mb_strtoupper(mb_substr((string) $demande->getAuteurPrenom(), 0, 1))) ?></span>
                <div class="svc-post__author">
                  <strong data-no-i18n><?= e($author) ?></strong>
                  <span><time datetime="<?= e($demande->getDateCreation()) ?>"><?= e(dateFr($demande->getDateCreation(), true)) ?></time></span>
                </div>
                <span class="status-badge status-badge--<?= $isRequest ? 'watch' : 'normal' ?>"><?= e(DemandeAssistanceModel::SENS[$demande->getSens()]) ?></span>
              </div>
              <h3 class="svc-post__title" data-no-i18n><?= e($demande->getTitre()) ?></h3>
              <p class="svc-post__text" data-no-i18n><?= nl2br(e($demande->getDescription())) ?></p>
              <div class="svc-meta">
                <span class="svc-tag svc-tag--blue"><?= e(DemandeAssistanceModel::TOUTES_CATEGORIES[$demande->getCategorie()] ?? $demande->getCategorie()) ?></span>
                <span><span data-icon="map-pin"></span><span data-no-i18n><?= e(($demande->getQuartier() ? $demande->getQuartier() . ', ' : '') . $demande->getNomZone()) ?></span></span>
<?php if ($isRequest): ?>
                <span class="status-badge status-badge--<?= $urgenceBadge[$demande->getUrgence()] ?? 'watch' ?>"><?= e(DemandeAssistanceModel::URGENCES[$demande->getUrgence()] ?? '') ?></span>
<?php elseif ($demande->getCreneau() !== null): ?>
                <span><span data-icon="clock"></span><?= e(DemandeAssistanceModel::CRENEAUX[$demande->getCreneau()] ?? '') ?></span>
<?php endif; ?>
                <span class="<?= $statutBadge[$demande->getStatut()] ?? 'svc-tag' ?>"><?= e(DemandeAssistanceModel::STATUTS[$demande->getStatut()] ?? '') ?></span>
<?php if ($reps !== []): ?>
                <span><span data-icon="message-circle"></span><?= count($reps) ?> réponse<?= count($reps) > 1 ? 's' : '' ?></span>
<?php endif; ?>
              </div>
<?php require __DIR__ . '/_reponses.php'; ?>
<?php if ($isMine || ($user !== null && $user->getRole() === 'admin')): ?>
              <div class="svc-post__foot">
<?php require __DIR__ . '/_actions-demande.php'; ?>
              </div>
<?php endif; ?>
            </article>
<?php endforeach; ?>
          </div>

          <aside class="svc-stack svc-aside-right">
<?php if ($user !== null): ?>
            <article class="card">
              <div class="card__body">
                <h2 class="svc-section-title" style="font-size:14px;">Votre rôle</h2>
                <p class="asst-muted"><?= e(roleLabel($user->getRole())) ?> —
<?php if (in_array($user->getRole(), DemandeAssistanceController::HELPER_ROLES, true)): ?>
                  vous pouvez répondre aux demandes d'aide<?= $user->getRole() === 'proprietaire' ? ' et proposer une recharge sur votre installation solaire' : '' ?>.
<?php else: ?>
                  vous pouvez publier des demandes et répondre aux offres d'aide. Les bénévoles, fournisseurs et propriétaires solaires répondent à vos demandes.
<?php endif; ?>
                </p>
              </div>
            </article>
<?php endif; ?>
            <article class="card">
              <div class="card__body">
                <h2 class="svc-section-title" style="font-size:14px;">Règles de la communauté</h2>
                <ul class="svc-bullets">
                  <li><span data-icon="check"></span>Ne publiez jamais d'adresse exacte ni de numéro de téléphone.</li>
                  <li><span data-icon="check"></span>Rencontrez-vous dans un lieu public ou un point refuge.</li>
                  <li><span data-icon="check"></span>Marquez votre demande « résolue » dès que vous avez reçu de l'aide.</li>
                  <li><span data-icon="check"></span>En cas d'urgence vitale, appelez le 190 (SAMU) ou le 198 (Protection civile).</li>
                </ul>
              </div>
            </article>
          </aside>
        </div>
      </div>
    </section>

<?php if ($user !== null): ?>
    <!-- Nouvelle publication -->
    <div class="modal svc-modal" id="post-modal" role="dialog" aria-modal="true" aria-labelledby="post-modal-title" hidden<?= $openModal === 'post-modal' ? ' data-open-on-load' : '' ?>>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="post-modal-title">Nouvelle publication</h2>
            <p>Votre adresse exacte n'est jamais affichée : seul le quartier est visible.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid" method="post" action="<?= url('relais') ?>" data-validate data-sens-form>
          <?= csrfField() ?>
          <input type="hidden" name="form" value="publication">
<?php if (isset($errors['form'])): ?>
          <p class="flash flash--error"><?= e($errors['form']) ?></p>
<?php endif; ?>
<?php $rules = DemandeAssistanceController::relaisRules(true); ?>
          <div class="field">
            <label>Type de publication *</label>
            <div class="svc-radio-row">
<?php foreach (DemandeAssistanceModel::SENS as $key => $label): ?>
              <label class="svc-radio"><input type="radio" name="sens" value="<?= e($key) ?>" data-rules="<?= e($rules['sens']) ?>"<?= $old['sens'] === $key ? ' checked' : '' ?>><span><span data-icon="<?= $key === 'demande' ? 'circle-alert' : 'handshake' ?>"></span><?= $key === 'demande' ? "J'ai besoin d'aide" : 'Je propose mon aide' ?></span></label>
<?php endforeach; ?>
            </div>
            <?= fieldError($errors, 'sens') ?>
          </div>
          <div class="field">
            <label for="post-category">Catégorie *</label>
            <select class="select<?= invalidClass($errors, 'categorie') ?>" id="post-category" name="categorie" data-rules="<?= e($rules['categorie']) ?>">
              <option value="">Choisir…</option>
<?php foreach (DemandeAssistanceModel::CATEGORIES as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $old['categorie'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'categorie') ?>
          </div>
          <div class="field">
            <label for="post-title">Titre *</label>
            <input class="input<?= invalidClass($errors, 'titre') ?>" id="post-title" name="titre" value="<?= e($old['titre']) ?>" placeholder="ex. Besoin de 2 bidons d'eau potable" data-rules="<?= e($rules['titre']) ?>">
            <?= fieldError($errors, 'titre') ?>
          </div>
          <div class="field">
            <label for="post-text">Description *</label>
            <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="post-text" name="description" placeholder="Décrivez le besoin ou l'aide proposée, sans adresse exacte ni numéro de téléphone." data-rules="<?= e($rules['description']) ?>"><?= e($old['description']) ?></textarea>
            <?= fieldError($errors, 'description') ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="post-gov">Gouvernorat *</label>
              <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="post-gov" name="id_zone" data-rules="<?= e($rules['id_zone']) ?>">
                <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === ($old['id_zone'] !== '' ? $old['id_zone'] : (string) $user->getIdZone()) ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'id_zone') ?>
            </div>
            <div class="field">
              <label for="post-area">Quartier (approximatif) *</label>
              <input class="input<?= invalidClass($errors, 'quartier') ?>" id="post-area" name="quartier" value="<?= e($old['quartier']) ?>" placeholder="ex. El Menzah 6" data-rules="<?= e($rules['quartier']) ?>">
              <?= fieldError($errors, 'quartier') ?>
            </div>
          </div>
          <div class="field" data-when-sens="demande">
            <label>Niveau d'urgence *</label>
            <div class="svc-radio-row">
<?php foreach (DemandeAssistanceModel::URGENCES as $key => $label): ?>
              <label class="svc-radio"><input type="radio" name="urgence" value="<?= e($key) ?>" data-rules="<?= e($rules['urgence']) ?>"<?= $old['urgence'] === $key ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
<?php endforeach; ?>
            </div>
            <?= fieldError($errors, 'urgence') ?>
          </div>
          <div class="field" data-when-sens="offre">
            <label for="post-slot">Disponibilité</label>
            <select class="select<?= invalidClass($errors, 'creneau') ?>" id="post-slot" name="creneau" data-rules="<?= e($rules['creneau']) ?>">
              <option value="">À convenir</option>
<?php foreach (DemandeAssistanceModel::CRENEAUX as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $old['creneau'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'creneau') ?>
          </div>
          <label class="checkbox"><input type="checkbox" name="regles" value="1" data-rules="<?= e($rules['regles']) ?>"<?= $old['regles'] !== '' ? ' checked' : '' ?>> Je respecte les règles de la communauté et je n'indique pas d'adresse exacte.</label>
          <?= fieldError($errors, 'regles') ?>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--primary btn--sm" type="submit" data-loading="Publication…"><span data-icon="send"></span>Publier</button>
          </div>
        </form>
      </div>
    </div>
<?php endif; ?>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Relais de voisinage — WASLA';
$description = 'Demandez ou proposez de l\'aide près de chez vous : eau potable, recharge, hébergement, transport, assistance aux personnes âgées.';
$currentPage = 'relais';
$pageCss     = ['pages', 'services', 'assistance'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/services/assistance.js'];
require __DIR__ . '/../../layout.php';
