      <header class="admin-topbar">
        <button class="admin-icon-btn admin-menu-btn" type="button" data-sidebar-open aria-label="Ouvrir le menu" aria-expanded="false">
          <span data-icon="menu"></span>
        </button>
        <form class="admin-search" role="search" action="#" data-admin-search>
          <span class="admin-search__icon" data-icon="search"></span>
          <input type="search" placeholder="Rechercher un utilisateur, une entreprise…" aria-label="Rechercher">
        </form>
        <div class="admin-topbar__actions">
          <button class="admin-icon-btn" type="button" aria-label="Notifications, 3 non lues">
            <span data-icon="bell"></span>
            <span class="admin-icon-btn__badge">3</span>
          </button>
<?php $adminUser = AuthController::currentUser(); ?>
          <a class="admin-profile" href="<?= url(profilRoute($adminUser) ?? 'mon-compte') ?>" aria-label="Mon compte">
            <span class="admin-avatar"><?= e(initials($adminUser)) ?></span>
            <span class="admin-profile__name"><?= e($adminUser->getPrenom() . ' ' . $adminUser->getNom()) ?><small class="admin-profile__role"><?= e(roleLabel($adminUser->getRole())) ?></small></span>
          </a>
          <form method="post" action="<?= url('deconnexion') ?>">
            <?= csrfField() ?>
            <button class="admin-icon-btn" type="submit" aria-label="Se déconnecter" title="Se déconnecter">
              <span data-icon="log-out"></span>
            </button>
          </form>
        </div>
      </header>
