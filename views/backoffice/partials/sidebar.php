<?php
/**
 * Sidebar du Back Office (dynamique).
 *
 * - L'élément actif et le groupe ouvert dépendent de $currentPage (fourni par le contrôleur).
 * - Un lien ne devient cliquable que lorsque sa route existe dans app/routes.php :
 *   il suffit d'ajouter la route pour activer l'entrée du menu.
 * - Chaque membre modifie UNIQUEMENT le groupe de son module.
 */
$currentPage = $currentPage ?? 'dashboard';

$groups = [
    // ── Module 1 — Houssem ──
    [
        'label' => 'Interruptions & alertes',
        'icon'  => 'triangle-alert',
        'links' => [
            ['admin-interruptions', 'Interruptions', ['admin-interruption-form']],
            ['admin-signalements', 'Signalements'],
            ['admin-alertes', 'Alertes', ['admin-alerte-form']],
            ['admin-zones', 'Zones géographiques', ['admin-zone-form']],
            ['admin-sous-admins', 'Comptes sous-admins'],
        ],
    ],
    // ── Module 2 — Hiba ──
    [
        'label' => 'Factures & consommation',
        'icon'  => 'file-text',
        'links' => [
            ['admin-factures', 'Factures', ['admin-facture-form']],
            ['admin-consommations', 'Données de consommation'],
            ['admin-citoyens', 'Comptes citoyens'],
        ],
    ],
    // ── Module 3 — Louay ──
    [
        'label' => 'Refuge Zones',
        'icon'  => 'home',
        'links' => [
            ['admin-refuges', 'Refuges à valider'],
            ['admin-entreprises', 'Comptes entreprises'],
        ],
    ],
    // ── Module 4 — Bechir ──
    [
        'label' => 'Assistance',
        'icon'  => 'heart-pulse',
        'links' => [
            ['admin-demandes', "Demandes d'assistance"],
            ['admin-fournisseurs', 'Comptes fournisseurs'],
            ['admin-acteurs', 'Bénévoles & propriétaires solaires'],
        ],
    ],
];
?>
    <aside class="admin-sidebar" aria-label="Menu d'administration">
      <a class="admin-brand" href="<?= url('dashboard') ?>" aria-label="WASLA Admin — Tableau de bord">
        <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <path d="M32 3.5C32 3.5 10.5 25.5 10.5 40.5C10.5 51.8 20.1 61.5 32 61.5C43.9 61.5 53.5 51.8 53.5 40.5C53.5 25.5 32 3.5 32 3.5Z" fill="#FFFFFF" stroke="#00A3D7" stroke-width="4.5" stroke-linejoin="round"/>
          <path d="M32 12.5C32 12.5 18.5 29.5 18.5 39.5C18.5 47 24.5 53 32 53C39.5 53 45.5 47 45.5 39.5C45.5 29.5 32 12.5 32 12.5Z" fill="#003B5C"/>
          <path d="M36.2 19.5C36.2 19.5 27 31 27 39.2C27 44.2 30.8 48 35.8 48C41 48 45.2 43.8 45.2 38.6C45.2 30.5 40.4 24.5 36.2 19.5Z" fill="#73D13D"/>
          <path d="M32.2 21.5L24.6 39.2H29.4L27.2 49.2L36.8 31.8H31.9L34.2 21.5H32.2Z" fill="#EAF4FB"/>
        </svg>
        <span>
          <span class="admin-brand__name">WASLA</span>
          <span class="admin-brand__tagline">Connecter. Anticiper. <span class="accent">Protéger.</span></span>
        </span>
      </a>

      <nav class="admin-nav">
        <a class="admin-nav__link <?= isActive('dashboard', $currentPage) ?>" href="<?= url('dashboard') ?>" <?= ariaCurrent('dashboard', $currentPage) ?>>
          <span class="admin-nav__icon" data-icon="home"></span> Tableau de bord
        </a>

        <p class="admin-nav__label">Modules</p>

<?php foreach ($groups as $group):
    // Toutes les pages du groupe (liens + pages liées comme les formulaires)
    $groupPages = [];
    foreach ($group['links'] as $link) {
        $groupPages = array_merge($groupPages, [$link[0]], $link[2] ?? []);
    }
    $open = isOpen($groupPages, $currentPage);
?>
        <div class="admin-nav__group <?= $open ?>" data-nav-group>
          <button class="admin-nav__toggle" type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>">
            <span class="admin-nav__icon" data-icon="<?= e($group['icon']) ?>"></span> <?= e($group['label']) ?>
            <span class="admin-nav__chevron" data-icon="chevron-down"></span>
          </button>
          <div class="admin-nav__sub">
<?php foreach ($group['links'] as $link):
    [$key, $label] = $link;
    $active = in_array($currentPage, array_merge([$key], $link[2] ?? []), true);
?>
            <a class="<?= $active ? 'is-active' : '' ?>" href="<?= pageUrl($key) ?>"<?= $active ? ' aria-current="page"' : '' ?><?= routeExists($key) ? '' : ' aria-disabled="true" title="Page pas encore créée"' ?>><?= e($label) ?></a>
<?php endforeach; ?>
          </div>
        </div>

<?php endforeach; ?>
        <p class="admin-nav__label">Site</p>
        <a class="admin-nav__link" href="<?= url('accueil') ?>">
          <span class="admin-nav__icon" data-icon="external-link"></span> Voir le site public
        </a>
      </nav>

      <div class="admin-sidebar__foot">
        <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <path d="M32 3.5C32 3.5 10.5 25.5 10.5 40.5C10.5 51.8 20.1 61.5 32 61.5C43.9 61.5 53.5 51.8 53.5 40.5C53.5 25.5 32 3.5 32 3.5Z" fill="#00A3D7"/>
          <path d="M40 30c-6 1.5-9 6-9 12 5-1 9-5 9-12Z" fill="#73D13D"/>
        </svg>
        <strong>WASLA</strong>
        <span>Une Tunisie plus durable</span>
      </div>
    </aside>
