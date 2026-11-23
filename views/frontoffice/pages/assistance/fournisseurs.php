<?php
/**
 * Vue : Service « Fournisseurs & techniciens » — Front Office — Module 4 (Bechir).
 * Annuaire : utilisateurs de rôle « fournisseur » actifs et vérifiés par l'admin.
 * Demandes d'intervention : demande_assistance avec id_fournisseur ; réponses du fournisseur : reponse.
 *
 * Variables reçues (routes/module4.php → 'fournisseurs') :
 *   $fournisseurs, $tousFournisseurs (UserModel[]), $parSpecialite, $filters, $user, $ctrl,
 *   $mesDemandes (envoyées), $recues (reçues, si l'utilisateur est fournisseur), $reponses, $repondues,
 *   $gouvernorats, $errors, $old, $openModal, $replyTo, $replyErrors, $replyOld.
 */
$retour = 'fournisseurs';
$installations = [];
$statutBadge = ['ouverte' => 'watch', 'prise_en_charge' => 'normal', 'resolue' => 'normal', 'annulee' => 'nodata'];
$specIcons = ['electricien' => 'zap', 'plombier' => 'droplet', 'maintenance' => 'wrench', 'solaire' => 'solar-panel', 'hydraulique' => 'droplet'];
$filterParams = array_filter(['id_zone' => $filters['id_zone'], 'q' => $filters['q'], 'tri' => $filters['tri']], fn ($v) => $v !== '');
ob_start();
?>
  <main id="main" data-service="fournisseurs">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('accueil') ?>">Accueil</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('services') ?>">Services</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Fournisseurs</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Service 03 · Techniciens agréés</p>
            <h1>Fournisseurs &amp; techniciens</h1>
            <p>Trouvez un électricien, un plombier ou un installateur solaire vérifié par WASLA près de chez vous et suivez vos demandes d'intervention.</p>
          </div>
          <div class="page-hero__actions">
<?php if ($user !== null && $user->getRole() === 'fournisseur'): ?>
            <a class="btn btn--outline" href="<?= url('profil-fournisseur') ?>"><span data-icon="user"></span> Mon profil professionnel</a>
<?php endif; ?>
<?php if ($user === null): ?>
            <a class="btn btn--outline" href="<?= url('inscription-fournisseur') ?>"><span data-icon="wrench"></span> Vous êtes un professionnel ?</a>
<?php endif; ?>
<?php if ($user !== null): ?>
            <button class="btn btn--outline" type="button" data-scroll-to="mes-demandes">
              <span data-icon="clock"></span>
              Mes demandes
            </button>
            <button class="btn btn--primary" type="button" data-svc-open="request-modal">
              <span data-icon="wrench"></span>
              Demander une intervention
            </button>
<?php else: ?>
            <a class="btn btn--primary" href="<?= url('connexion') ?>"><span data-icon="user"></span> Se connecter pour demander une intervention</a>
<?php endif; ?>
          </div>
        </div>
      </div>
    </section>

<?php if ($user !== null && $user->getRole() === 'fournisseur'): ?>
    <section class="page-section" id="demandes-recues">
      <div class="container">
        <article class="card">
          <div class="card__header">
            <div>
              <h2 class="card__title">Demandes reçues</h2>
              <p class="svc-card-sub">Demandes d'intervention adressées à <span data-no-i18n><?= e($user->getNomOrganisation() ?? $user->getPrenom()) ?></span></p>
            </div>
            <span class="status-badge status-badge--normal"><?= count(array_filter($recues, fn ($d) => in_array($d->getStatut(), ['ouverte', 'prise_en_charge'], true))) ?> en cours</span>
          </div>
          <div class="card__body svc-stack">
<?php if ($recues === []): ?>
            <p class="asst-muted">Aucune demande pour le moment.</p>
<?php endif; ?>
<?php if (!$user->isCompteVerifie()): ?>
            <p class="svc-notice svc-notice--warning"><span data-icon="clock"></span><span>Votre compte n'est pas encore vérifié par l'administrateur : vous n'apparaissez pas dans l'annuaire et ne pouvez pas encore répondre aux demandes.</span></p>
<?php endif; ?>
<?php foreach ($recues as $demande): $reps = $reponses[$demande->getIdDemande()] ?? []; ?>
            <div class="asst-request" id="demande-<?= $demande->getIdDemande() ?>">
              <div class="svc-item__top">
                <strong class="svc-item__title" data-no-i18n><?= e($demande->getTitre()) ?></strong>
                <span class="status-badge status-badge--<?= $statutBadge[$demande->getStatut()] ?>"><?= e(DemandeAssistanceModel::STATUTS[$demande->getStatut()]) ?></span>
              </div>
              <p class="svc-post__text" data-no-i18n><?= nl2br(e($demande->getDescription())) ?></p>
              <div class="svc-meta">
                <span><span data-icon="user"></span><span data-no-i18n><?= e($demande->getAuteurPrenom() . ' ' . $demande->getAuteurNom()) ?></span></span>
                <span><span data-icon="phone"></span><span data-no-i18n><?= e($demande->getTelephone() ?? '—') ?></span></span>
                <span><span data-icon="map-pin"></span><span data-no-i18n><?= e(($demande->getQuartier() ? $demande->getQuartier() . ', ' : '') . $demande->getNomZone()) ?></span></span>
                <span><span data-icon="calendar"></span><?= e(dateFr($demande->getDateSouhaitee())) ?><?= $demande->getCreneau() ? ' · ' . e(DemandeAssistanceModel::CRENEAUX[$demande->getCreneau()]) : '' ?></span>
                <span class="svc-tag svc-tag--blue"><?= e(DemandeAssistanceModel::CATEGORIES[$demande->getCategorie()] ?? '') ?></span>
                <span class="svc-tag"><?= e(DemandeAssistanceModel::URGENCES[$demande->getUrgence()] ?? '') ?></span>
              </div>
<?php require __DIR__ . '/_actions-demande.php'; ?>
<?php require __DIR__ . '/_reponses.php'; ?>
            </div>
<?php endforeach; ?>
          </div>
        </article>
      </div>
    </section>
<?php endif; ?>

    <section class="page-section page-section--soft">
      <div class="container">
        <nav class="summary-grid svc-tiles" aria-label="Filtrer par spécialité">
<?php foreach (UserModel::SPECIALITES as $spec):
    $active = $filters['specialite'] === $spec;
    $params = $filterParams + ($active ? [] : ['specialite' => $spec]);
?>
          <a class="summary-card svc-tile<?= $active ? ' is-active' : '' ?>" href="<?= url('fournisseurs', $params) ?>"<?= $active ? ' aria-current="true"' : '' ?>>
            <span class="summary-card__icon" data-icon="<?= $specIcons[$spec] ?>"></span>
            <div>
              <p class="summary-card__value"><?= (int) ($parSpecialite[$spec] ?? 0) ?></p>
              <p class="summary-card__label"><?= e(specialiteLabel($spec)) ?></p>
            </div>
          </a>
<?php endforeach; ?>
        </nav>

        <form class="filter-bar" method="get" action="index.php">
          <input type="hidden" name="action" value="fournisseurs">
<?php if ($filters['specialite'] !== ''): ?>
          <input type="hidden" name="specialite" value="<?= e($filters['specialite']) ?>">
<?php endif; ?>
          <span class="filter-bar__label">Filtrer</span>
          <div class="filter-search">
            <span data-icon="search"></span>
            <label class="sr-only" for="pro-search">Rechercher un professionnel</label>
            <input id="pro-search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom, quartier…">
          </div>
          <label class="sr-only" for="pro-gov">Gouvernorat</label>
          <select class="select" id="pro-gov" name="id_zone" style="width:auto;min-height:34px;padding-block:6px;font-size:12px;">
            <option value="">Tous les gouvernorats</option>
<?php foreach ($gouvernorats as $g): ?>
            <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $filters['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
          </select>
          <label class="sr-only" for="pro-sort">Trier</label>
          <select class="select" id="pro-sort" name="tri" style="width:auto;min-height:34px;padding-block:6px;font-size:12px;">
            <option value="note">Mieux notés</option>
            <option value="nom"<?= $filters['tri'] === 'nom' ? ' selected' : '' ?>>Nom (A → Z)</option>
          </select>
          <button class="btn btn--secondary btn--sm" type="submit">Appliquer</button>
          <span class="filter-bar__spacer"></span>
          <a class="text-link" href="<?= url('fournisseurs') ?>">Réinitialiser les filtres</a>
        </form>

        <div class="svc-layout">
          <div class="svc-stack">
            <p class="text-muted" style="font-size:12.5px;"><?= count($fournisseurs) ?> professionnel<?= count($fournisseurs) > 1 ? 's' : '' ?> vérifié<?= count($fournisseurs) > 1 ? 's' : '' ?><?= $filters['specialite'] !== '' ? ' · ' . e(specialiteLabel($filters['specialite'])) : '' ?></p>
<?php if ($fournisseurs === []): ?>
            <article class="card"><div class="empty-state"><span data-icon="wrench"></span><strong>Aucun professionnel</strong><p>Aucun fournisseur vérifié ne correspond à ces filtres.</p></div></article>
<?php else: ?>
            <div class="svc-pro-grid">
<?php foreach ($fournisseurs as $pro): ?>
              <article class="card svc-pro">
                <div class="svc-pro__head">
                  <span class="svc-avatar svc-avatar--blue" aria-hidden="true" data-no-i18n><?= e(mb_strtoupper(mb_substr($pro->getNomOrganisation() ?? $pro->getNom(), 0, 2))) ?></span>
                  <div>
                    <h3 class="svc-pro__name" data-no-i18n><?= e($pro->getNomOrganisation() ?? ($pro->getPrenom() . ' ' . $pro->getNom())) ?></h3>
                    <p class="svc-pro__spec"><?= e(specialiteLabel($pro->getSpecialite())) ?> · <span data-no-i18n><?= e($pro->getPrenom() . ' ' . $pro->getNom()) ?></span></p>
                  </div>
                </div>
<?php if ($pro->getNoteMoyenne() !== null): ?>
                <div class="svc-pro__rating"><span data-icon="star"></span><strong><?= e(number_format($pro->getNoteMoyenne(), 1, ',', '')) ?></strong><span>/ 5</span></div>
<?php endif; ?>
<?php if ($pro->getDescription()): ?>
                <p class="asst-muted" data-no-i18n><?= e($pro->getDescription()) ?></p>
<?php endif; ?>
                <div class="svc-meta"><span><span data-icon="map-pin"></span><span data-no-i18n><?= e(($pro->getAdresse() ? $pro->getAdresse() . ' · ' : '') . ($pro->getNomZone() ?? '')) ?></span></span></div>
                <div class="svc-pro__badges">
                  <span class="svc-tag svc-tag--green"><span data-icon="shield-check"></span>Vérifié par WASLA</span>
                </div>
                <div class="svc-pro__actions">
<?php if ($user === null): ?>
                  <a class="btn btn--secondary btn--sm" href="<?= url('connexion') ?>">Se connecter pour contacter</a>
<?php elseif ($user->getIdUtilisateur() !== $pro->getIdUtilisateur()): ?>
                  <button class="btn btn--secondary btn--sm" type="button" data-svc-open="request-modal" data-pick-fournisseur="<?= $pro->getIdUtilisateur() ?>">Demander une intervention</button>
<?php else: ?>
                  <span class="svc-tag">Votre fiche</span>
<?php endif; ?>
                </div>
              </article>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>

          <aside class="svc-stack">
            <article class="card" id="mes-demandes">
              <div class="card__header">
                <div>
                  <h2 class="card__title">Mes demandes</h2>
                  <p class="svc-card-sub">Suivi de vos demandes d'intervention</p>
                </div>
                <span class="status-badge status-badge--normal"><?= count(array_filter($mesDemandes, fn ($d) => in_array($d->getStatut(), ['ouverte', 'prise_en_charge'], true))) ?> en cours</span>
              </div>
              <div class="svc-list">
<?php if ($user === null): ?>
                <p class="asst-muted" style="padding:16px;"><a class="text-link" href="<?= url('connexion') ?>">Connectez-vous</a> pour suivre vos demandes.</p>
<?php elseif ($mesDemandes === []): ?>
                <p class="asst-muted" style="padding:16px;">Vous n'avez envoyé aucune demande d'intervention.</p>
<?php endif; ?>
<?php foreach ($mesDemandes as $demande): $reps = $reponses[$demande->getIdDemande()] ?? []; ?>
                <div class="svc-item asst-item" id="demande-<?= $demande->getIdDemande() ?>">
                  <span class="svc-item__body">
                    <span class="svc-item__top"><span class="svc-item__title" style="font-size:13px;" data-no-i18n><?= e($demande->getTitre()) ?></span><span class="status-badge status-badge--<?= $statutBadge[$demande->getStatut()] ?>"><?= e(DemandeAssistanceModel::STATUTS[$demande->getStatut()]) ?></span></span>
                    <span class="svc-meta" style="margin-top:4px;"><span data-no-i18n><?= e($demande->getFournisseurNom() ?? 'Professionnel') ?></span><span><span data-icon="calendar"></span><?= e(dateFr($demande->getDateSouhaitee())) ?></span></span>
<?php require __DIR__ . '/_reponses.php'; ?>
<?php require __DIR__ . '/_actions-demande.php'; ?>
                  </span>
                </div>
<?php endforeach; ?>
              </div>
            </article>
            <article class="card">
              <div class="card__body">
                <h2 class="svc-section-title">Comment ça marche ?</h2>
                <ul class="svc-timeline">
                  <li class="is-done"><span class="svc-timeline__dot"><span data-icon="search"></span></span><div><strong>Choisissez un professionnel</strong><p>Tous les fournisseurs affichés sont vérifiés par l'administration.</p></div></li>
                  <li class="is-done"><span class="svc-timeline__dot"><span data-icon="send"></span></span><div><strong>Décrivez votre problème</strong><p>Précisez l'urgence et le créneau souhaité.</p></div></li>
                  <li class="is-done"><span class="svc-timeline__dot"><span data-icon="calendar"></span></span><div><strong>Suivez l'intervention</strong><p>Ouverte, prise en charge, puis résolue.</p></div></li>
                </ul>
              </div>
            </article>
          </aside>
        </div>
      </div>
    </section>

<?php if ($user !== null): ?>
    <!-- Demande d'intervention -->
    <div class="modal svc-modal" id="request-modal" role="dialog" aria-modal="true" aria-labelledby="request-modal-title" hidden<?= $openModal === 'request-modal' ? ' data-open-on-load' : '' ?>>
      <div class="modal__panel">
        <div class="modal__head">
          <div>
            <h2 id="request-modal-title">Demande d'intervention</h2>
            <p>Les champs marqués * sont obligatoires.</p>
          </div>
          <button class="modal__close" type="button" data-svc-close aria-label="Fermer"><span data-icon="x"></span></button>
        </div>
        <form class="svc-modal__body form-grid" method="post" action="<?= url('fournisseurs') ?>" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="form" value="demande">
<?php if (isset($errors['form'])): ?>
          <p class="flash flash--error"><?= e($errors['form']) ?></p>
<?php endif; ?>
<?php $rules = DemandeAssistanceController::fournisseurRules(true); ?>
          <div class="field">
            <label for="req-pro">Professionnel *</label>
            <select class="select<?= invalidClass($errors, 'id_fournisseur') ?>" id="req-pro" name="id_fournisseur" data-rules="<?= e($rules['id_fournisseur']) ?>">
              <option value="">Choisir…</option>
<?php foreach ($tousFournisseurs as $pro): if ($pro->getIdUtilisateur() === $user->getIdUtilisateur()) continue; ?>
              <option value="<?= $pro->getIdUtilisateur() ?>"<?= (string) $pro->getIdUtilisateur() === $old['id_fournisseur'] ? ' selected' : '' ?>><?= e(($pro->getNomOrganisation() ?? $pro->getNom()) . ' — ' . specialiteLabel($pro->getSpecialite()) . ' (' . ($pro->getNomZone() ?? '') . ')') ?></option>
<?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'id_fournisseur') ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="req-cat">Type de problème *</label>
              <select class="select<?= invalidClass($errors, 'categorie') ?>" id="req-cat" name="categorie" data-rules="<?= e($rules['categorie']) ?>">
                <option value="">Choisir…</option>
<?php foreach (DemandeAssistanceModel::CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $old['categorie'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'categorie') ?>
            </div>
            <div class="field">
              <label>Urgence *</label>
              <div class="svc-radio-row">
<?php foreach (DemandeAssistanceModel::URGENCES as $key => $label): ?>
                <label class="svc-radio"><input type="radio" name="urgence" value="<?= e($key) ?>" data-rules="<?= e($rules['urgence']) ?>"<?= $old['urgence'] === $key ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
<?php endforeach; ?>
              </div>
              <?= fieldError($errors, 'urgence') ?>
            </div>
          </div>
          <div class="field">
            <label for="req-title">Objet *</label>
            <input class="input<?= invalidClass($errors, 'titre') ?>" id="req-title" name="titre" value="<?= e($old['titre']) ?>" placeholder="ex. Fuite sous l'évier" data-rules="<?= e($rules['titre']) ?>">
            <?= fieldError($errors, 'titre') ?>
          </div>
          <div class="field">
            <label for="req-desc">Description du problème *</label>
            <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="req-desc" name="description" placeholder="Que se passe-t-il ? Depuis quand ? Quels appareils sont concernés ?" data-rules="<?= e($rules['description']) ?>"><?= e($old['description']) ?></textarea>
            <?= fieldError($errors, 'description') ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="req-gov">Gouvernorat *</label>
              <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="req-gov" name="id_zone" data-rules="<?= e($rules['id_zone']) ?>">
                <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $g): ?>
                <option value="<?= $g->getIdZone() ?>"<?= (string) $g->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($g->getNomZone()) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'id_zone') ?>
            </div>
            <div class="field">
              <label for="req-area">Quartier *</label>
              <input class="input<?= invalidClass($errors, 'quartier') ?>" id="req-area" name="quartier" value="<?= e($old['quartier']) ?>" placeholder="ex. El Menzah 6" data-rules="<?= e($rules['quartier']) ?>">
              <?= fieldError($errors, 'quartier') ?>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="req-date">Date souhaitée *</label>
              <input class="input<?= invalidClass($errors, 'date_souhaitee') ?>" id="req-date" name="date_souhaitee" type="date" value="<?= e($old['date_souhaitee']) ?>" data-rules="<?= e($rules['date_souhaitee']) ?>">
              <?= fieldError($errors, 'date_souhaitee') ?>
            </div>
            <div class="field">
              <label for="req-slot">Créneau</label>
              <select class="select<?= invalidClass($errors, 'creneau') ?>" id="req-slot" name="creneau" data-rules="<?= e($rules['creneau']) ?>">
                <option value="">Indifférent</option>
<?php foreach (DemandeAssistanceModel::CRENEAUX as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $old['creneau'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
              </select>
              <?= fieldError($errors, 'creneau') ?>
            </div>
          </div>
          <div class="field">
            <label for="req-phone">Téléphone de contact *</label>
            <input class="input<?= invalidClass($errors, 'telephone') ?>" id="req-phone" name="telephone" inputmode="tel" value="<?= e($old['telephone']) ?>" placeholder="ex. 22 123 456" data-rules="<?= e($rules['telephone']) ?>">
            <p class="field__hint">Communiqué uniquement au professionnel choisi.</p>
            <?= fieldError($errors, 'telephone') ?>
          </div>
          <label class="checkbox"><input type="checkbox" name="consentement" value="1" data-rules="<?= e($rules['consentement']) ?>"<?= $old['consentement'] !== '' ? ' checked' : '' ?>> J'accepte que ma demande et mon numéro soient transmis à ce professionnel.</label>
          <?= fieldError($errors, 'consentement') ?>
          <div class="svc-actions" style="justify-content:flex-end;">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--primary btn--sm" type="submit" data-loading="Envoi…"><span data-icon="send"></span>Envoyer la demande</button>
          </div>
        </form>
      </div>
    </div>
<?php endif; ?>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Fournisseurs & techniciens — WASLA';
$description = 'Trouvez un électricien, un plombier ou un installateur solaire vérifié près de chez vous et envoyez une demande d\'intervention.';
$currentPage = 'fournisseurs';
$pageCss     = ['pages', 'services', 'assistance'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/services/core.js', 'js/services/assistance.js'];
require __DIR__ . '/../../layout.php';
