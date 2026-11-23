<?php
/**
 * Vue « Mon profil entreprise » — Front Office — espace ENTREPRISE — Module 3 (Louay).
 * Variables reçues : $user (UserModel connecté), $errors / $old (formulaire profil),
 *                    $pwdErrors (mot de passe), $delErrors (suppression du compte),
 *                    $gouvernorats (ZoneModel[]), $secteurs (string[]).
 *
 * Trois formulaires POST séparés (data-validate, mêmes règles et messages qu'en PHP) :
 *   profil-entreprise            informations (jamais le rôle, le statut ni la vérification)
 *   mot-de-passe-entreprise      mot de passe actuel + nouveau
 *   supprimer-compte-entreprise  mot de passe ; suppression, ou désactivation s'il a des données
 */
$verifie = $user->isCompteVerifie();
$attrs = static fn (string $field): string => EntrepriseController::messageAttrs($field);
ob_start();
?>
  <main id="main">
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= url('mon-compte') ?>">Mon espace</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <a href="<?= url('mes-refuges') ?>">Mes refuges</a>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-dir"><path d="m9 18 6-6-6-6"/></svg>
          <span aria-current="page">Mon profil</span>
        </nav>
        <div class="page-hero__row">
          <div>
            <p class="eyebrow">Espace entreprise</p>
            <h1>Mon profil entreprise</h1>
            <p>Statut du compte :
              <span class="status-badge status-badge--<?= $verifie ? 'normal' : 'watch' ?>"><?= $verifie ? 'Vérifié' : 'En attente de vérification' ?></span>
            </p>
          </div>
          <div class="page-hero__actions">
            <a class="btn btn--outline" href="<?= url('mes-refuges') ?>"><span data-icon="home"></span> Mes refuges</a>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section page-section--soft">
      <div class="container" style="max-width:860px;">

        <!-- ── Informations ── -->
        <article class="card">
          <div class="card__header"><h2 class="card__title">Informations de l'entreprise</h2></div>
          <div class="card__body">
<?php if ($errors !== []): ?>
            <div class="form-alert form-alert--error" role="alert" style="margin-top:0;"><?= e($errors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
            <form class="form-grid" method="post" action="<?= url('profil-entreprise') ?>" data-validate>
              <?= csrfField() ?>
              <div class="form-row">
                <div class="field">
                  <label for="pf-org">Raison sociale *</label>
                  <input class="input<?= invalidClass($errors, 'nom_organisation') ?>" id="pf-org" name="nom_organisation" type="text" autocomplete="organization"
                         value="<?= e($old['nom_organisation']) ?>" data-rules="required|min:2|max:100"<?= $attrs('nom_organisation') ?>>
                  <?= fieldError($errors, 'nom_organisation') ?>
                </div>
                <div class="field">
                  <label for="pf-mf">Matricule fiscal *</label>
                  <input class="input<?= invalidClass($errors, 'matricule_fiscal') ?>" id="pf-mf" name="matricule_fiscal" type="text"
                         value="<?= e($old['matricule_fiscal']) ?>" data-rules="required|max:20" data-extra-rules="matricule"<?= $attrs('matricule_fiscal') ?>>
                  <p class="field__hint">Le modifier remet votre compte en attente de vérification.</p>
                  <?= fieldError($errors, 'matricule_fiscal') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="pf-secteur">Secteur d'activité *</label>
                  <select class="select<?= invalidClass($errors, 'secteur_activite') ?>" id="pf-secteur" name="secteur_activite" data-rules="required|max:60"<?= $attrs('secteur_activite') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($secteurs as $secteur): ?>
                    <option value="<?= e($secteur) ?>"<?= $secteur === $old['secteur_activite'] ? ' selected' : '' ?>><?= e($secteur) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'secteur_activite') ?>
                </div>
                <div class="field">
                  <label for="pf-zone">Gouvernorat *</label>
                  <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="pf-zone" name="id_zone" data-rules="required|int"<?= $attrs('id_zone') ?>>
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= (string) $zone->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'id_zone') ?>
                </div>
              </div>

              <div class="field">
                <label for="pf-adresse">Adresse *</label>
                <input class="input<?= invalidClass($errors, 'adresse') ?>" id="pf-adresse" name="adresse" type="text" autocomplete="street-address"
                       value="<?= e($old['adresse']) ?>" data-rules="required|min:5|max:255"<?= $attrs('adresse') ?>>
                <?= fieldError($errors, 'adresse') ?>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="pf-prenom">Prénom du responsable *</label>
                  <input class="input<?= invalidClass($errors, 'prenom') ?>" id="pf-prenom" name="prenom" type="text" autocomplete="given-name"
                         value="<?= e($old['prenom']) ?>" data-rules="required|name"<?= $attrs('prenom') ?>>
                  <?= fieldError($errors, 'prenom') ?>
                </div>
                <div class="field">
                  <label for="pf-nom">Nom du responsable *</label>
                  <input class="input<?= invalidClass($errors, 'nom') ?>" id="pf-nom" name="nom" type="text" autocomplete="family-name"
                         value="<?= e($old['nom']) ?>" data-rules="required|name"<?= $attrs('nom') ?>>
                  <?= fieldError($errors, 'nom') ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label for="pf-email">Adresse e-mail *</label>
                  <input class="input<?= invalidClass($errors, 'email') ?>" id="pf-email" name="email" type="text" inputmode="email" autocomplete="email"
                         value="<?= e($old['email']) ?>" data-rules="required|email"<?= $attrs('email') ?>>
                  <?= fieldError($errors, 'email') ?>
                </div>
                <div class="field">
                  <label for="pf-tel">Téléphone *</label>
                  <input class="input<?= invalidClass($errors, 'telephone') ?>" id="pf-tel" name="telephone" type="text" inputmode="tel" autocomplete="tel"
                         value="<?= e($old['telephone']) ?>" data-rules="required|phone"<?= $attrs('telephone') ?>>
                  <?= fieldError($errors, 'telephone') ?>
                </div>
              </div>

              <div class="field">
                <label for="pf-desc">Présentation <span class="field__optional">(facultatif)</span></label>
                <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="pf-desc" name="description" rows="3" data-rules="max:500"><?= e($old['description']) ?></textarea>
                <?= fieldError($errors, 'description') ?>
              </div>

              <div class="svc-actions" style="justify-content:flex-end;">
                <button class="btn btn--primary btn--sm" type="submit" data-loading="Enregistrement…">Enregistrer le profil</button>
              </div>
            </form>
          </div>
        </article>

        <!-- ── Mot de passe ── -->
        <article class="card" id="mot-de-passe" style="margin-top:20px;">
          <div class="card__header"><h2 class="card__title">Changer le mot de passe</h2></div>
          <div class="card__body">
<?php if ($pwdErrors !== []): ?>
            <div class="form-alert form-alert--error" role="alert" style="margin-top:0;"><?= e($pwdErrors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
            <form class="form-grid" method="post" action="<?= url('mot-de-passe-entreprise') ?>" data-validate>
              <?= csrfField() ?>
              <div class="field">
                <label for="pw-actuel">Mot de passe actuel *</label>
                <input class="input<?= invalidClass($pwdErrors, 'mot_de_passe_actuel') ?>" id="pw-actuel" name="mot_de_passe_actuel" type="password" autocomplete="current-password"
                       data-rules="required"<?= $attrs('mot_de_passe_actuel') ?>>
                <?= fieldError($pwdErrors, 'mot_de_passe_actuel') ?>
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="pw-new">Nouveau mot de passe *</label>
                  <input class="input<?= invalidClass($pwdErrors, 'password') ?>" id="pw-new" name="password" type="password" autocomplete="new-password"
                         data-rules="required|password"<?= $attrs('password') ?>>
                  <p class="field__hint">8 caractères min., une lettre et un chiffre.</p>
                  <?= fieldError($pwdErrors, 'password') ?>
                </div>
                <div class="field">
                  <label for="pw-confirm">Confirmation *</label>
                  <input class="input<?= invalidClass($pwdErrors, 'password_confirm') ?>" id="pw-confirm" name="password_confirm" type="password" autocomplete="new-password"
                         data-rules="required|match:password"<?= $attrs('password_confirm') ?>>
                  <?= fieldError($pwdErrors, 'password_confirm') ?>
                </div>
              </div>
              <div class="svc-actions" style="justify-content:flex-end;">
                <button class="btn btn--primary btn--sm" type="submit" data-loading="Modification…">Changer le mot de passe</button>
              </div>
            </form>
          </div>
        </article>

        <!-- ── Suppression du compte ── -->
        <article class="card" id="supprimer" style="margin-top:20px;">
          <div class="card__header"><h2 class="card__title">Supprimer mon compte</h2></div>
          <div class="card__body">
            <p>Un compte sans refuge ni réservation est supprimé définitivement. Sinon, il est <strong>désactivé</strong> : vous ne pourrez plus vous connecter,
               vos refuges disparaissent de la carte, les réservations en cours sont annulées et l'historique est conservé.</p>
<?php if ($delErrors !== []): ?>
            <div class="form-alert form-alert--error" role="alert"><?= e($delErrors['form'] ?? 'Veuillez corriger les champs indiqués.') ?></div>
<?php endif; ?>
            <form class="form-grid" method="post" action="<?= url('supprimer-compte-entreprise') ?>" data-validate
                  data-confirm-apres="Supprimer (ou désactiver) votre compte entreprise ? Vous serez déconnecté.">
              <?= csrfField() ?>
              <div class="field">
                <label for="del-mdp">Mot de passe *</label>
                <input class="input<?= invalidClass($delErrors, 'mot_de_passe_actuel') ?>" id="del-mdp" name="mot_de_passe_actuel" type="password" autocomplete="current-password"
                       data-rules="required"<?= $attrs('mot_de_passe_actuel') ?>>
                <?= fieldError($delErrors, 'mot_de_passe_actuel') ?>
              </div>
              <div class="svc-actions" style="justify-content:flex-end;">
                <button class="btn btn--outline btn--sm" type="submit">Supprimer mon compte</button>
              </div>
            </form>
          </div>
        </article>
      </div>
    </section>
  </main>
<?php
$content     = ob_get_clean();
$title       = 'Mon profil entreprise — WASLA';
$description = 'Profil, mot de passe et suppression du compte entreprise.';
$currentPage = 'mon-compte';
$pageCss     = ['pages', 'services'];
$pageScripts = ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js', 'js/services/refuge-forms.js'];
require __DIR__ . '/../../layout.php';
