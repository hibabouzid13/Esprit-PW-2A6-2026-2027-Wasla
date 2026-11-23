<?php
/**
 * Vue « Tableau de bord » — Back Office (admin, STEG, SONEDE).
 * Variables reçues : $user, $totalUsers, $countByRole (rôle => nombre),
 *                    $voitLesComptes (super admin), $allUsers (UserModel[], vide pour STEG et SONEDE).
 * Les indicateurs de consommation / coupures sont des données de démonstration
 * qui seront remplacées par les modules 1 et 2.
 */
ob_start();
?>
        <div class="admin-page-head">
          <div class="admin-page-head__title">
            <span class="admin-page-head__icon" data-icon="layers"></span>
            <div>
              <h1>Tableau de bord</h1>
              <p>Vue d'ensemble de la plateforme WASLA</p>
            </div>
          </div>
          <div class="admin-page-head__actions">
            <span class="admin-date"><span data-icon="calendar"></span> <?= date('d/m/Y') ?></span>
          </div>
        </div>

        <!-- KPI -->
        <section class="admin-kpis" aria-label="Indicateurs clés">
          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon" data-icon="zap"></span>
            <div>
              <p class="admin-kpi__label">Électricité consommée</p>
              <div class="admin-kpi__row">
                <p class="admin-kpi__value">1,24 <small>GWh</small></p>
                <div class="admin-kpi__spark" data-admin-spark="58,62,60,66,64,70,72,69,75,78,76,82" data-color="#005A9C"></div>
              </div>
            </div>
            <p class="admin-kpi__trend admin-trend--up"><span data-icon="arrow-up"></span> +4,3 % vs période précédente</p>
          </article>

          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--water" data-icon="droplet"></span>
            <div>
              <p class="admin-kpi__label">Eau consommée</p>
              <div class="admin-kpi__row">
                <p class="admin-kpi__value">842 <small>m³</small></p>
                <div class="admin-kpi__spark" data-admin-spark="54,58,56,61,59,64,66,63,69,72,70,76" data-color="#00A3D7"></div>
              </div>
            </div>
            <p class="admin-kpi__trend admin-trend--up"><span data-icon="arrow-up"></span> +2,8 % vs période précédente</p>
          </article>

          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--outage" data-icon="triangle-alert"></span>
            <div>
              <p class="admin-kpi__label">Coupures signalées</p>
              <div class="admin-kpi__row">
                <p class="admin-kpi__value">7</p>
                <div class="admin-kpi__spark" data-admin-spark="9,11,8,12,14,10,9,13,8,7,9,7" data-color="#00A875"></div>
              </div>
            </div>
            <p class="admin-kpi__trend admin-trend--down"><span data-icon="arrow-down"></span> −12 % vs période précédente</p>
          </article>

          <article class="admin-card admin-kpi">
            <span class="admin-kpi__icon admin-kpi__icon--ok" data-icon="circle-check"></span>
            <div>
              <p class="admin-kpi__label">Stabilité moyenne</p>
              <div class="admin-kpi__row">
                <p class="admin-kpi__value">96,4 <small>%</small></p>
                <div class="admin-kpi__spark" data-admin-spark="91,92,92,93,94,93,95,94,95,96,95,96" data-color="#00A875"></div>
              </div>
            </div>
            <p class="admin-kpi__trend admin-trend--up"><span data-icon="arrow-up"></span> +1,9 % vs période précédente</p>
          </article>
        </section>

        <!-- Graphiques -->
        <section class="admin-grid admin-grid--chart">
          <article class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="chart-bar"></span> Évolution de la consommation <small>(démonstration)</small></h2>
              <div class="admin-legend"><span>Électricité</span><span class="is-water">Eau</span></div>
            </div>
            <div class="admin-card__body">
              <div class="admin-segmented" role="tablist" aria-label="Période">
                <button class="is-active" type="button" role="tab" aria-selected="true" data-period="7j">7 jours</button>
                <button type="button" role="tab" aria-selected="false" data-period="30j">30 jours</button>
                <button type="button" role="tab" aria-selected="false" data-period="12m">12 mois</button>
              </div>
              <div data-line-chart></div>
            </div>
          </article>

          <article class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="landmark"></span> Consommation par gouvernorat</h2>
              <div class="admin-legend"><span>Électricité</span><span class="is-water">Eau</span></div>
            </div>
            <div class="admin-card__body">
              <div data-bar-chart></div>
            </div>
          </article>
        </section>

        <!-- Activités, utilisateurs, à traiter -->
        <section class="admin-grid admin-grid--three">
          <article class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="clock"></span> Activités récentes</h2>
            </div>
            <div class="admin-card__body">
              <ul class="admin-activity">
                <li><span class="admin-dot admin-dot--ok" data-icon="check"></span><span>Interruption publiée : STEG — Sfax Nord</span><time>il y a 5 min</time></li>
                <li><span class="admin-dot admin-dot--info" data-icon="user"></span><span>Compte citoyen créé : Ahmed Ben Salah</span><time>il y a 12 min</time></li>
                <li><span class="admin-dot admin-dot--warn" data-icon="triangle-alert"></span><span>Alerte coupure : gouvernorat de Sfax</span><time>il y a 20 min</time></li>
                <li><span class="admin-dot admin-dot--blue" data-icon="building-2"></span><span>Entreprise enregistrée : Café Ennour</span><time>il y a 1 h</time></li>
                <li><span class="admin-dot admin-dot--ok" data-icon="heart-pulse"></span><span>Demande d'assistance prise en charge</span><time>il y a 2 h</time></li>
              </ul>
            </div>
          </article>

          <article class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="users"></span> Utilisateurs du système</h2>
<?php if ($voitLesComptes && routeExists('admin-citoyens')): ?>
              <a class="admin-card__link" href="<?= url('admin-citoyens') ?>">Voir tous <span data-icon="arrow-right"></span></a>
<?php endif; ?>
            </div>
            <div class="admin-card__body">
              <p class="admin-users__value"><?= $totalUsers ?> <span>comptes</span></p>
              <ul class="admin-activity">
<?php foreach ($countByRole as $role => $n): if ($n === 0) continue; ?>
                <li><span class="admin-dot admin-dot--info" data-icon="user"></span><span><?= e(roleLabel($role)) ?></span><time><?= $n ?></time></li>
<?php endforeach; ?>
              </ul>
            </div>
          </article>

          <article class="admin-card">
            <div class="admin-card__head">
              <h2 class="admin-card__title"><span data-icon="shield-check"></span> À traiter</h2>
            </div>
            <div class="admin-card__body">
              <div class="admin-todo">
                <a href="<?= pageUrl('admin-signalements') ?>"<?= routeExists('admin-signalements') ? '' : ' aria-disabled="true"' ?>>
                  <span class="admin-todo__icon" data-icon="map-pin"></span>
                  <span><strong>Signalements à modérer</strong><small>Interruptions &amp; alertes</small></span>
                  <span class="admin-count">6</span>
                </a>
                <a href="<?= pageUrl('admin-refuges') ?>"<?= routeExists('admin-refuges') ? '' : ' aria-disabled="true"' ?>>
                  <span class="admin-todo__icon" data-icon="home"></span>
                  <span><strong>Refuge Zones à valider</strong><small>Refuge Zones</small></span>
                  <span class="admin-count">3</span>
                </a>
                <a href="<?= pageUrl('admin-demandes') ?>"<?= routeExists('admin-demandes') ? '' : ' aria-disabled="true"' ?>>
                  <span class="admin-todo__icon" data-icon="heart-pulse"></span>
                  <span><strong>Demandes d'assistance ouvertes</strong><small>Assistance</small></span>
                  <span class="admin-count">4</span>
                </a>
              </div>
            </div>
          </article>
        </section>

<?php if ($voitLesComptes): ?>
        <!-- Tous les comptes (données réelles de la table utilisateur) : super admin uniquement -->
        <section class="admin-card">
          <div class="admin-card__head">
            <h2 class="admin-card__title"><span data-icon="users"></span> Tous les comptes (<?= count($allUsers) ?>)</h2>
          </div>
          <div class="admin-card__body admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr><th>ID</th><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Gouvernorat</th><th>Statut</th><th>Inscription</th></tr>
              </thead>
              <tbody>
<?php foreach ($allUsers as $u): ?>
                <tr>
                  <td><?= $u->getIdUtilisateur() ?></td>
                  <td><?= e($u->getNomOrganisation() ?? ($u->getPrenom() . ' ' . $u->getNom())) ?></td>
                  <td><?= e($u->getEmail()) ?></td>
                  <td><?= e(roleLabel($u->getRole())) ?></td>
                  <td><?= e($u->getNomZone() ?? '—') ?></td>
                  <td><?= e($u->getStatutCompte() === 'actif' ? 'Actif' : 'Suspendu') ?></td>
                  <td><?= e(dateFr($u->getDateInscription())) ?></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php endif; ?>
<?php
$content     = ob_get_clean();
$title       = 'Tableau de bord — WASLA Admin';
$currentPage = 'dashboard';
$pageScripts = ['js/data.js', 'js/charts.js'];
require __DIR__ . '/../layout.php';
