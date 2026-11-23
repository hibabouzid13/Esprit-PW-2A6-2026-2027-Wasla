<?php
/**
 * Partiel : champs d'un compte fournisseur (inscription et profil) — Module 4 (Bechir).
 * Variables attendues : $errors, $old, $gouvernorats, $rules (FournisseurController::rules(...)).
 * Ni le rôle, ni le statut, ni la vérification ne sont des champs : ils sont écrits par le contrôleur.
 */
?>
              <div class="form-row">
                <div class="field">
                  <label for="f-org">Nom commercial *</label>
                  <input class="input<?= invalidClass($errors, 'nom_organisation') ?>" id="f-org" name="nom_organisation" value="<?= e($old['nom_organisation']) ?>" placeholder="ex. Trabelsi Plomberie" data-rules="<?= e($rules['nom_organisation']) ?>">
                  <?= fieldError($errors, 'nom_organisation') ?>
                </div>
                <div class="field">
                  <label for="f-spec">Spécialité *</label>
                  <select class="select<?= invalidClass($errors, 'specialite') ?>" id="f-spec" name="specialite" data-rules="<?= e($rules['specialite']) ?>">
                    <option value="">Choisir…</option>
<?php foreach (UserModel::SPECIALITES as $spec): ?>
                    <option value="<?= e($spec) ?>"<?= $old['specialite'] === $spec ? ' selected' : '' ?>><?= e(specialiteLabel($spec)) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'specialite') ?>
                </div>
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="f-prenom">Prénom *</label>
                  <input class="input<?= invalidClass($errors, 'prenom') ?>" id="f-prenom" name="prenom" autocomplete="given-name" value="<?= e($old['prenom']) ?>" data-rules="<?= e($rules['prenom']) ?>">
                  <?= fieldError($errors, 'prenom') ?>
                </div>
                <div class="field">
                  <label for="f-nom">Nom *</label>
                  <input class="input<?= invalidClass($errors, 'nom') ?>" id="f-nom" name="nom" autocomplete="family-name" value="<?= e($old['nom']) ?>" data-rules="<?= e($rules['nom']) ?>">
                  <?= fieldError($errors, 'nom') ?>
                </div>
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="f-email">Adresse e-mail *</label>
                  <input class="input<?= invalidClass($errors, 'email') ?>" id="f-email" name="email" inputmode="email" autocomplete="email" placeholder="vous@exemple.tn" value="<?= e($old['email']) ?>" data-rules="<?= e($rules['email']) ?>">
                  <?= fieldError($errors, 'email') ?>
                </div>
                <div class="field">
                  <label for="f-tel">Téléphone *</label>
                  <input class="input<?= invalidClass($errors, 'telephone') ?>" id="f-tel" name="telephone" inputmode="tel" autocomplete="tel" placeholder="22 123 456" value="<?= e($old['telephone']) ?>" data-rules="<?= e($rules['telephone']) ?>">
                  <?= fieldError($errors, 'telephone') ?>
                </div>
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="f-zone">Gouvernorat *</label>
                  <select class="select<?= invalidClass($errors, 'id_zone') ?>" id="f-zone" name="id_zone" data-rules="<?= e($rules['id_zone']) ?>">
                    <option value="">Choisir…</option>
<?php foreach ($gouvernorats as $zone): ?>
                    <option value="<?= $zone->getIdZone() ?>"<?= (string) $zone->getIdZone() === $old['id_zone'] ? ' selected' : '' ?>><?= e($zone->getNomZone()) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($errors, 'id_zone') ?>
                </div>
                <div class="field">
                  <label for="f-adresse">Adresse <span class="field__optional">(facultatif)</span></label>
                  <input class="input<?= invalidClass($errors, 'adresse') ?>" id="f-adresse" name="adresse" autocomplete="street-address" value="<?= e($old['adresse']) ?>" data-rules="<?= e($rules['adresse']) ?>">
                  <?= fieldError($errors, 'adresse') ?>
                </div>
              </div>
              <div class="field">
                <label for="f-desc">Description de vos services <span class="field__optional">(facultatif, 500 caractères max.)</span></label>
                <textarea class="textarea<?= invalidClass($errors, 'description') ?>" id="f-desc" name="description" rows="3" data-rules="<?= e($rules['description']) ?>"><?= e($old['description']) ?></textarea>
                <?= fieldError($errors, 'description') ?>
              </div>
