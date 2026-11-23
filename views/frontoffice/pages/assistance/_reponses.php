<?php
/**
 * Partiel : réponses d'une demande + formulaire de réponse — Module 4 (Bechir).
 * Inclus par relais.php et fournisseurs.php.
 *
 * Variables attendues :
 *   $demande      DemandeAssistanceModel
 *   $reps         ReponseModel[] réponses de cette demande
 *   $user         ?UserModel     utilisateur connecté
 *   $ctrl         DemandeAssistanceController (contrôle des droits uniquement, aucun SQL ici)
 *   $retour       'relais' | 'fournisseurs' (page où revenir après une action)
 *   $repondues    int[] demandes auxquelles $user a déjà répondu
 *   $installations array<int, string> installations solaires de $user (propriétaire)
 *   $replyTo, $replyErrors, $replyOld  état du formulaire de réponse après une erreur
 */
$isOwner = $ctrl->isOwner($demande, $user);
// Une seule réponse peut être acceptée par demande
$hasAccepted = in_array('acceptee', array_map(fn ($r) => $r->getStatut(), $reps), true);
$tabParam = $retour === 'relais' ? (string) ($filters['tab'] ?? '') : '';
?>
<?php if ($reps !== []): ?>
            <ul class="asst-replies" aria-label="Réponses">
<?php foreach ($reps as $rep):
    $repName = $rep->getAuteurRole() === 'fournisseur' && $rep->getAuteurOrganisation()
        ? $rep->getAuteurOrganisation()
        : $rep->getAuteurPrenom() . ' ' . mb_substr((string) $rep->getAuteurNom(), 0, 1) . '.';
    $badge = ['proposee' => 'watch', 'acceptee' => 'normal', 'refusee' => 'nodata'][$rep->getStatut()] ?? 'nodata';
?>
              <li class="asst-reply">
                <div class="asst-reply__head">
                  <strong data-no-i18n><?= e($repName) ?></strong>
                  <span class="svc-tag"><?= e(roleLabel((string) $rep->getAuteurRole())) ?></span>
                  <span class="status-badge status-badge--<?= $badge ?>"><?= e(ReponseModel::STATUTS[$rep->getStatut()] ?? $rep->getStatut()) ?></span>
                  <time class="asst-muted" datetime="<?= e($rep->getDateReponse()) ?>"><?= e(dateFr($rep->getDateReponse(), true)) ?></time>
                </div>
                <p class="asst-reply__text" data-no-i18n><?= nl2br(e($rep->getMessage())) ?></p>
<?php if ($rep->getInstallationAdresse() !== null): ?>
                <p class="asst-muted"><span data-icon="solar-panel"></span> Recharge solaire proposée<?= $isOwner || ($user && $user->getIdUtilisateur() === $rep->getIdUtilisateur()) ? ' : ' . e($rep->getInstallationAdresse()) : '' ?></p>
<?php endif; ?>
                <div class="svc-actions">
<?php if ($isOwner && $rep->getStatut() !== 'acceptee' && in_array($demande->getStatut(), ['ouverte', 'prise_en_charge'], true)): ?>
<?php if (!$hasAccepted): ?>
                  <form method="post" action="<?= url('reponse-statut') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_reponse" value="<?= $rep->getIdReponse() ?>">
                    <input type="hidden" name="statut" value="acceptee">
                    <input type="hidden" name="retour" value="<?= e($retour) ?>">
                    <input type="hidden" name="tab" value="<?= e($tabParam) ?>">
                    <button class="svc-icon-btn" type="submit"><span data-icon="circle-check"></span>Accepter</button>
                  </form>
<?php endif; ?>
<?php if ($rep->getStatut() === 'proposee'): ?>
                  <form method="post" action="<?= url('reponse-statut') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_reponse" value="<?= $rep->getIdReponse() ?>">
                    <input type="hidden" name="statut" value="refusee">
                    <input type="hidden" name="retour" value="<?= e($retour) ?>">
                    <input type="hidden" name="tab" value="<?= e($tabParam) ?>">
                    <button class="svc-icon-btn" type="submit"><span data-icon="x"></span>Refuser</button>
                  </form>
<?php endif; ?>
<?php endif; ?>
<?php if ($user && $user->getIdUtilisateur() === $rep->getIdUtilisateur() && $rep->getStatut() !== 'acceptee'): ?>
                  <form method="post" action="<?= url('reponse-supprimer') ?>" data-confirm="Retirer votre réponse ?">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_reponse" value="<?= $rep->getIdReponse() ?>">
                    <input type="hidden" name="retour" value="<?= e($retour) ?>">
                    <input type="hidden" name="tab" value="<?= e($tabParam) ?>">
                    <button class="svc-icon-btn svc-icon-btn--danger" type="submit"><span data-icon="trash"></span>Retirer</button>
                  </form>
<?php endif; ?>
                </div>
              </li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
<?php
$reason = $ctrl->whyCannotReply($demande, $user);
$already = in_array($demande->getIdDemande(), $repondues, true);
if ($reason === null && !$already):
    $formErrors = $replyTo === $demande->getIdDemande() ? $replyErrors : [];
    $formOld = $replyTo === $demande->getIdDemande() ? $replyOld : ['message' => '', 'id_installation' => ''];
    $fid = 'reply-' . $demande->getIdDemande();
?>
            <details class="asst-reply-box"<?= $formErrors !== [] ? ' open' : '' ?>>
              <summary class="svc-icon-btn"><span data-icon="message-circle"></span><?= $demande->getSens() === 'offre' ? 'Je suis intéressé(e)' : 'Répondre' ?></summary>
              <form class="form-grid" method="post" action="<?= url($retour) ?>" data-validate>
                <?= csrfField() ?>
                <input type="hidden" name="form" value="reponse">
                <input type="hidden" name="id_demande" value="<?= $demande->getIdDemande() ?>">
<?php if (isset($formErrors['form'])): ?>
                <p class="flash flash--error"><?= e($formErrors['form']) ?></p>
<?php endif; ?>
                <div class="field">
                  <label for="<?= $fid ?>-message">Votre message *</label>
                  <textarea class="textarea<?= invalidClass($formErrors, 'message') ?>" id="<?= $fid ?>-message" name="message" rows="3"
                            placeholder="<?= $demande->getIdFournisseur() === null ? 'Présentez votre aide, sans numéro de téléphone ni adresse exacte.' : 'Disponibilité, délai, estimation…' ?>"
                            data-rules="<?= e(DemandeAssistanceController::reponseRules()['message']) ?>"><?= e($formOld['message']) ?></textarea>
                  <?= fieldError($formErrors, 'message') ?>
                </div>
<?php if ($installations !== []): ?>
                <div class="field">
                  <label for="<?= $fid ?>-installation">Proposer une recharge sur mon installation solaire</label>
                  <select class="select<?= invalidClass($formErrors, 'id_installation') ?>" id="<?= $fid ?>-installation" name="id_installation" data-rules="int">
                    <option value="">Non</option>
<?php foreach ($installations as $idInst => $label): ?>
                    <option value="<?= $idInst ?>"<?= (string) $idInst === $formOld['id_installation'] ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= fieldError($formErrors, 'id_installation') ?>
                </div>
<?php endif; ?>
                <div class="svc-actions" style="justify-content:flex-end;">
                  <button class="btn btn--primary btn--sm" type="submit"><span data-icon="send"></span>Envoyer</button>
                </div>
              </form>
            </details>
<?php elseif ($already && !$isOwner): ?>
            <p class="asst-muted"><span data-icon="check"></span> Vous avez répondu à cette demande.</p>
<?php elseif ($user === null && in_array($demande->getStatut(), ['ouverte', 'prise_en_charge'], true)): ?>
            <p class="asst-muted"><a class="text-link" href="<?= url('connexion') ?>">Connectez-vous</a> pour répondre.</p>
<?php endif; ?>
