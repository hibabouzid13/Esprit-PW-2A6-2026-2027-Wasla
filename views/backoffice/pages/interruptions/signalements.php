<?php
/**
 * Vue : Back Office : liste et traitement des signalements citoyens — Module 1 (Houssem).
 * Variables reçues :
 *   $signalements         array<int, array{signalement: SignalementModel, auteur: string, email: string, confirmations: int}>
 *   $interruptionsParCle  array<string, InterruptionModel[]>  clé « id_zone|type_ressource »
 *   $rowErrors            array<int, array<string,string>>    erreurs du dernier traitement, par id de signalement
 */
$typeBadges   = ['electricite' => ['Électricité', 'info'], 'eau' => ['Eau', 'ok']];
$statutBadges = ['en_attente' => ['En attente', 'warn'], 'confirme' => ['Confirmé', 'ok'], 'rejete' => ['Rejeté', 'danger']];
$fmt = static fn (?string $d): string => ($d && ($t = strtotime($d))) ? date('d/m/Y H:i', $t) : '—';
$nbAttente = count(array_filter($signalements, fn ($r) => $r['signalement']->getStatut() === 'en_attente'));

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Interruptions &amp; alertes</span><span>/</span><span>Signalements</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="triangle-alert"></span>
              <div>
                <h1>Signalements</h1>
                <p>Coupures signalées par les utilisateurs — <?= $nbAttente ?> en attente de traitement</p>
              </div>
            </div>
          </div>
        </div>

        <section class="admin-card">
          <div class="admin-toolbar">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" placeholder="Rechercher (zone, adresse, auteur…)" aria-label="Rechercher un signalement" data-table-search>
            </div>
            <select class="admin-select" aria-label="Filtrer par ressource" data-table-filter="ressource">
              <option value="">Toutes les ressources</option>
              <option value="electricite">Électricité</option>
              <option value="eau">Eau</option>
            </select>
            <select class="admin-select" aria-label="Filtrer par statut" data-table-filter="statut">
              <option value="">Tous les statuts</option>
              <option value="en_attente">En attente</option>
              <option value="confirme">Confirmé</option>
              <option value="rejete">Rejeté</option>
            </select>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table" data-admin-table>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Date</th>
                  <th scope="col">Auteur</th>
                  <th scope="col">Ressource</th>
                  <th scope="col">Zone / adresse</th>
                  <th scope="col">« Moi aussi »</th>
                  <th scope="col">Statut</th>
                  <th scope="col">Traitement</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($signalements as $row):
        $s  = $row['signalement'];
        $id = $s->getIdSignalement();
        [$typeLabel, $typeClass]     = $typeBadges[$s->getTypeRessource()] ?? [$s->getTypeRessource(), 'muted'];
        [$statutLabel, $statutClass] = $statutBadges[$s->getStatut()] ?? [$s->getStatut(), 'muted'];
        $choix  = $interruptionsParCle[$s->getIdZone() . '|' . $s->getTypeRessource()] ?? [];
        $errs   = $rowErrors[$id] ?? [];
        $formId = 'sig-form-' . $id; ?>
                <tr data-ressource="<?= e($s->getTypeRessource()) ?>" data-statut="<?= e($s->getStatut()) ?>">
                  <td class="admin-table__id">SIG-<?= $id ?></td>
                  <td><?= e($fmt($s->getDateSignalement())) ?></td>
                  <td><?= e($row['auteur']) ?><br><small><?= e($row['email']) ?></small></td>
                  <td><span class="admin-badge admin-badge--<?= $typeClass ?>"><?= e($typeLabel) ?></span></td>
                  <td class="admin-table__main"><?= e($s->getNomZone()) ?><br><small><?= e($s->getAdresse()) ?></small><?php if ($s->getMessage()): ?><br><small><em><?= e($s->getMessage()) ?></em></small><?php endif; ?></td>
                  <td><?= $row['confirmations'] ?></td>
                  <td><span class="admin-badge admin-badge--<?= $statutClass ?>"><?= e($statutLabel) ?></span><?php if ($s->getIdInterruption()): ?><br><small>→ <a href="<?= url('admin-interruption-form', ['id' => $s->getIdInterruption()]) ?>">INT-<?= $s->getIdInterruption() ?></a></small><?php endif; ?></td>
                  <td>
                    <form id="<?= $formId ?>" method="post" action="<?= url('admin-signalement-traiter') ?>" data-validate style="display:flex;flex-direction:column;gap:6px;min-width:190px;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_signalement" value="<?= $id ?>">
                      <select class="admin-select" name="statut" aria-label="Statut du signalement SIG-<?= $id ?>" data-rules="required|in:<?= implode(',', SignalementController::STATUTS) ?>"<?= isset($errs['statut']) ? ' aria-invalid="true"' : '' ?>>
<?php foreach ($statutBadges as $value => [$label]): ?>
                        <option value="<?= $value ?>"<?= $s->getStatut() === $value ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                      </select>
                      <select class="admin-select" name="id_interruption" aria-label="Interruption rattachée au signalement SIG-<?= $id ?>" data-rules="int"<?= isset($errs['id_interruption']) ? ' aria-invalid="true"' : '' ?>>
                        <option value="">— Aucune interruption —</option>
<?php foreach ($choix as $i): ?>
                        <option value="<?= $i->getIdInterruption() ?>"<?= $s->getIdInterruption() === $i->getIdInterruption() ? ' selected' : '' ?>>INT-<?= $i->getIdInterruption() ?> · <?= e($fmt($i->getDateDebut())) ?> · <?= e(mb_strimwidth($i->getCause(), 0, 28, '…')) ?></option>
<?php endforeach; ?>
                      </select>
                      <small class="admin-field__error" style="display:block;" data-error-for="statut"><?= e($errs['statut'] ?? '') ?></small>
                      <small class="admin-field__error" style="display:block;" data-error-for="id_interruption"><?= e($errs['id_interruption'] ?? '') ?></small>
                    </form>
                  </td>
                  <td><div class="admin-actions">
                    <button class="admin-action" type="submit" form="<?= $formId ?>" aria-label="Enregistrer le traitement de SIG-<?= $id ?>"><span data-icon="save"></span></button>
                    <form method="post" action="<?= url('admin-signalement-supprimer') ?>" data-confirm="Supprimer ce signalement ? Cette action est définitive." style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_signalement" value="<?= $id ?>">
                      <button class="admin-action admin-action--danger" type="submit" aria-label="Supprimer SIG-<?= $id ?>"><span data-icon="trash"></span></button>
                    </form>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
<?php if ($signalements): ?>
            <p class="admin-empty" data-table-empty hidden>Aucun signalement ne correspond à la recherche.</p>
<?php else: ?>
            <p class="admin-empty">Aucun signalement reçu pour le moment.</p>
<?php endif; ?>
          </div>

          <div class="admin-table-foot">
            <span><strong data-table-count><?= count($signalements) ?></strong> signalement(s) affiché(s)</span>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Signalements — WASLA Admin';
$currentPage = 'admin-signalements';
require __DIR__ . '/../../layout.php';
