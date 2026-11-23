<?php
/**
 * Vue : Back Office : liste des interruptions — Back Office — Module 1 (Houssem).
 * Variable reçue : $interruptions (InterruptionModel[]).
 */
ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Interruptions &amp; alertes</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="zap"></span>
              <div>
                <h1>Interruptions</h1>
                <p>Coupures d'eau et d'électricité publiées par les sous-admins STEG et SONEDE</p>
              </div>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-interruption-form') ?>"><span data-icon="plus"></span> Publier une interruption</a>
          </div>
        </div>

        <section class="admin-card">
          <div class="admin-toolbar">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" placeholder="Rechercher (zone, cause…)" aria-label="Rechercher une interruption" data-table-search>
            </div>
            <select class="admin-select" aria-label="Filtrer par ressource" data-table-filter="ressource">
              <option value="">Toutes les ressources</option>
              <option value="electricite">Électricité</option>
              <option value="eau">Eau</option>
            </select>
            <select class="admin-select" aria-label="Filtrer par statut" data-table-filter="statut">
              <option value="">Tous les statuts</option>
              <option value="planifiee">Planifiée</option>
              <option value="en_cours">En cours</option>
              <option value="terminee">Terminée</option>
            </select>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table" data-admin-table>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Ressource</th>
                  <th scope="col">Zone</th>
                  <th scope="col">Début</th>
                  <th scope="col">Fin prévue</th>
                  <th scope="col">Cause</th>
                  <th scope="col">Statut</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php
    $typeBadges   = ['electricite' => ['Électricité', 'info'], 'eau' => ['Eau', 'ok']];
    $statutBadges = ['planifiee' => ['Planifiée', 'warn'], 'en_cours' => ['En cours', 'danger'], 'terminee' => ['Terminée', 'muted']];
    $fmt = static fn (?string $d): string => ($d && ($t = strtotime($d))) ? date('d/m/Y H:i', $t) : '—';
?>
<?php foreach ($interruptions as $i):
        [$typeLabel, $typeClass]     = $typeBadges[$i->getTypeRessource()] ?? [$i->getTypeRessource(), 'muted'];
        [$statutLabel, $statutClass] = $statutBadges[$i->getStatut()] ?? [$i->getStatut(), 'muted']; ?>
                <tr data-ressource="<?= e($i->getTypeRessource()) ?>" data-statut="<?= e($i->getStatut()) ?>">
                  <td class="admin-table__id">INT-<?= $i->getIdInterruption() ?></td>
                  <td><span class="admin-badge admin-badge--<?= $typeClass ?>"><?= e($typeLabel) ?></span></td>
                  <td class="admin-table__main"><?= e($i->getNomZone()) ?></td>
                  <td><?= e($fmt($i->getDateDebut())) ?></td>
                  <td><?= e($fmt($i->getDateFinPrevue())) ?><?php if ($i->getDateFinReelle()): ?><br><small>Fin réelle : <?= e($fmt($i->getDateFinReelle())) ?></small><?php endif; ?></td>
                  <td><?= e($i->getCause()) ?></td>
                  <td><span class="admin-badge admin-badge--<?= $statutClass ?>"><?= e($statutLabel) ?></span></td>
                  <td><div class="admin-actions">
                    <a class="admin-action" href="<?= url('admin-interruption-form', ['id' => $i->getIdInterruption()]) ?>" aria-label="Modifier"><span data-icon="pencil"></span></a>
<?php if ($i->getStatut() !== 'terminee'): ?>
                    <form method="post" action="<?= url('admin-interruption-cloturer') ?>" data-confirm="Clôturer cette interruption ? La fin réelle sera fixée à maintenant." style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_interruption" value="<?= $i->getIdInterruption() ?>">
                      <button class="admin-action" type="submit" aria-label="Clôturer"><span data-icon="lock"></span></button>
                    </form>
<?php endif; ?>
                    <form method="post" action="<?= url('admin-interruption-supprimer') ?>" data-confirm="Supprimer cette interruption ? Cette action est définitive." style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_interruption" value="<?= $i->getIdInterruption() ?>">
                      <button class="admin-action admin-action--danger" type="submit" aria-label="Supprimer"><span data-icon="trash"></span></button>
                    </form>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
<?php if ($interruptions): ?>
            <p class="admin-empty" data-table-empty hidden>Aucune interruption ne correspond à la recherche.</p>
<?php else: ?>
            <p class="admin-empty">Aucune interruption publiée pour le moment.</p>
<?php endif; ?>
          </div>

          <div class="admin-table-foot">
            <span><strong data-table-count><?= count($interruptions) ?></strong> interruption(s) affichée(s)</span>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Interruptions — WASLA Admin';
$currentPage = 'admin-interruptions';
require __DIR__ . '/../../layout.php';
