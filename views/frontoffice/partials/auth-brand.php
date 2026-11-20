<?php /* Panneau gauche des pages connexion / inscription. Variables : $brandTitle (HTML sûr), $brandText. */ ?>
        <aside class="login-brand">
          <a class="brand" href="<?= url('accueil') ?>" aria-label="WASLA — Accueil">
            <span class="brand__mark">
              <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <path d="M32 3.5C32 3.5 10.5 25.5 10.5 40.5C10.5 51.8 20.1 61.5 32 61.5C43.9 61.5 53.5 51.8 53.5 40.5C53.5 25.5 32 3.5 32 3.5Z" fill="#FFFFFF" stroke="#00A3D7" stroke-width="4.5" stroke-linejoin="round"/>
                <path d="M32 12.5C32 12.5 18.5 29.5 18.5 39.5C18.5 47 24.5 53 32 53C39.5 53 45.5 47 45.5 39.5C45.5 29.5 32 12.5 32 12.5Z" fill="#003B5C"/>
                <path d="M36.2 19.5C36.2 19.5 27 31 27 39.2C27 44.2 30.8 48 35.8 48C41 48 45.2 43.8 45.2 38.6C45.2 30.5 40.4 24.5 36.2 19.5Z" fill="#73D13D"/>
                <path d="M32.2 21.5L24.6 39.2H29.4L27.2 49.2L36.8 31.8H31.9L34.2 21.5H32.2Z" fill="#EAF4FB"/>
              </svg>
            </span>
            <span class="brand__text">
              <span class="brand__name">WASLA</span>
              <span class="brand__tagline">Connecter. Anticiper. <span class="accent">Protéger.</span></span>
            </span>
          </a>

          <div class="login-brand__body">
            <h2><?= $brandTitle ?></h2>
            <p><?= e($brandText) ?></p>
          </div>

          <div class="login-brand__stats">
            <div class="login-brand__stat"><strong>24</strong><span>Gouvernorats</span></div>
            <div class="login-brand__stat"><strong>98%</strong><span>Réseau stable</span></div>
            <div class="login-brand__stat"><strong>+1M</strong><span>Citoyens</span></div>
          </div>
        </aside>
