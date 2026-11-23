<?php
/**
 * Partiel : actions sur une demande (modifier, changer le statut, supprimer) — Module 4 (Bechir).
 * Variables attendues : $demande, $user, $ctrl, $retour ('relais' | 'fournisseurs'), $filters (facultatif).
 */
$statutLabels = [
    'ouverte'         => ['Rouvrir', 'refresh'],
    'prise_en_charge' => ['Accepter l\'intervention', 'circle-check'],
    'resolue'         => ['Marquer résolue', 'circle-check'],
    'annulee'         => [$ctrl->isOwner($demande, $user) ? 'Annuler' : 'Refuser', 'x'],
];
$tabParam = $retour === 'relais' ? (string) ($filters['tab'] ?? '') : '';
?>
              <div class="svc-actions">
<?php if ($ctrl->canEdit($demande, $user)): ?>
                <a class="svc-icon-btn" href="<?= url('demande-modifier', ['id' => $demande->getIdDemande()]) ?>"><span data-icon="pencil"></span>Modifier</a>
<?php endif; ?>
<?php foreach ($ctrl->allowedStatuts($demande, $user) as $statut): ?>
                <form method="post" action="<?= url('demande-statut') ?>"<?= $statut === 'annulee' ? ' data-confirm="Confirmer ?"' : '' ?>>
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $demande->getIdDemande() ?>">
                  <input type="hidden" name="statut" value="<?= e($statut) ?>">
                  <input type="hidden" name="retour" value="<?= e($retour) ?>">
                  <input type="hidden" name="tab" value="<?= e($tabParam) ?>">
                  <button class="svc-icon-btn" type="submit"><span data-icon="<?= $statutLabels[$statut][1] ?>"></span><?= e($statutLabels[$statut][0]) ?></button>
                </form>
<?php endforeach; ?>
<?php if ($ctrl->canDelete($demande, $user)): ?>
                <form method="post" action="<?= url('demande-supprimer') ?>" data-confirm="Supprimer définitivement cette demande et ses réponses ?">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $demande->getIdDemande() ?>">
                  <input type="hidden" name="retour" value="<?= e($retour) ?>">
                  <input type="hidden" name="tab" value="<?= e($tabParam) ?>">
                  <button class="svc-icon-btn svc-icon-btn--danger" type="submit" aria-label="Supprimer la demande"><span data-icon="trash"></span>Supprimer</button>
                </form>
<?php endif; ?>
              </div>
