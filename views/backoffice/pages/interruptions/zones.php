<?php
/**
 * Vue : Back Office : liste des zones géographiques — Module 1 (Houssem). Super admin uniquement.
 * Variable reçue : $zones (array<int, array{zone: ZoneModel, parent: ?string, delegations: int}>).
 */
$typeLabels = ['gouvernorat' => ['Gouvernorat', 'info'], 'delegation' => ['Délégation', 'muted']];
$nbGouv = count(array_filter($zones, fn ($r) => $r['zone']->getTypeZone() === 'gouvernorat'));

ob_start();
?>
        <div class="admin-page-head">
          <div>
            <nav class="admin-breadcrumb" aria-label="Fil d'Ariane">
              <a href="<?= url('dashboard') ?>">Tableau de bord</a><span>/</span><span>Interruptions &amp; alertes</span><span>/</span><span>Zones géographiques</span>
            </nav>
            <div class="admin-page-head__title">
              <span class="admin-page-head__icon" data-icon="map-pin"></span>
              <div>
                <h1>Zones géographiques</h1>
                <p>Gouvernorats et délégations utilisés par les interruptions, les signalements et les comptes</p>
              </div>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <a class="admin-btn admin-btn--primary" href="<?= url('admin-zone-form') ?>"><span data-icon="plus"></span> Ajouter une zone</a>
          </div>
        </div>

        <section class="admin-card">
          <div class="admin-toolbar">
            <div class="admin-search">
              <span class="admin-search__icon" data-icon="search"></span>
              <input type="search" placeholder="Rechercher une zone…" aria-label="Rechercher une zone" data-table-search>
            </div>
            <select class="admin-select" aria-label="Filtrer par type" data-table-filter="type">
              <option value="">Tous les types</option>
              <option value="gouvernorat">Gouvernorats</option>
              <option value="delegation">Délégations</option>
            </select>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table" data-admin-table>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Nom</th>
                  <th scope="col">Type</th>
                  <th scope="col">Gouvernorat parent</th>
                  <th scope="col">Délégations</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($zones as $row):
        $z = $row['zone'];
        [$typeLabel, $typeClass] = $typeLabels[$z->getTypeZone()] ?? [$z->getTypeZone(), 'muted']; ?>
                <tr data-type="<?= e($z->getTypeZone()) ?>">
                  <td class="admin-table__id"><?= $z->getIdZone() ?></td>
                  <td class="admin-table__main"><?= e($z->getNomZone()) ?></td>
                  <td><span class="admin-badge admin-badge--<?= $typeClass ?>"><?= e($typeLabel) ?></span></td>
                  <td><?= e($row['parent'] ?? '—') ?></td>
                  <td><?= $z->getTypeZone() === 'gouvernorat' ? $row['delegations'] : '—' ?></td>
                  <td><div class="admin-actions">
                    <a class="admin-action" href="<?= url('admin-zone-form', ['id' => $z->getIdZone()]) ?>" aria-label="Modifier"><span data-icon="pencil"></span></a>
                    <form method="post" action="<?= url('admin-zone-supprimer') ?>" data-confirm="Supprimer cette zone ? La suppression est refusée si la zone est encore utilisée." style="display:contents;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_zone" value="<?= $z->getIdZone() ?>">
                      <button class="admin-action admin-action--danger" type="submit" aria-label="Supprimer"><span data-icon="trash"></span></button>
                    </form>
                  </div></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
<?php if ($zones): ?>
            <p class="admin-empty" data-table-empty hidden>Aucune zone ne correspond à la recherche.</p>
<?php else: ?>
            <p class="admin-empty">Aucune zone enregistrée.</p>
<?php endif; ?>
          </div>

          <div class="admin-table-foot">
            <span><strong data-table-count><?= count($zones) ?></strong> zone(s) affichée(s) · <?= $nbGouv ?> gouvernorat(s)</span>
          </div>
        </section>
<?php
$content     = ob_get_clean();
$title       = 'Zones géographiques — WASLA Admin';
$currentPage = 'admin-zones';
require __DIR__ . '/../../layout.php';
