
  <footer class="site-footer">
    <div class="container">
      <div class="footer-main">
        <div class="footer-brand">
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
          <p>Une plateforme intelligente pour surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.</p>
        </div>
        <div>
          <h2 class="footer-heading">Plateforme</h2>
          <div class="footer-links">
            <a href="<?= url('accueil') ?>">Accueil</a>
            <a href="<?= url('carte') ?>">Carte des réseaux</a>
            <a href="<?= url('analyses') ?>">Analyses</a>
            <a href="<?= url('services') ?>">Services</a>
          </div>
        </div>
        <div>
          <h2 class="footer-heading">Services</h2>
          <div class="footer-links">
            <a href="<?= url('services', [], 'alertes') ?>">Alertes personnalisées</a>
            <a href="<?= url('services', [], 'factures') ?>">Factures &amp; IA</a>
            <a href="<?= url('services', [], 'fournisseurs') ?>">Fournisseurs</a>
            <a href="<?= url('services', [], 'refuge') ?>">Zones refuge</a>
          </div>
        </div>
        <div>
          <h2 class="footer-heading">À propos</h2>
          <div class="footer-links">
            <a href="<?= url('a-propos') ?>">Notre mission</a>
            <a href="<?= url('a-propos', [], 'engagements') ?>">Engagements</a>
            <?php if (AuthController::isAuthenticated()): ?><a href="<?= url('mon-compte') ?>">Mon espace</a><?php else: ?><a href="<?= url('connexion') ?>">Connexion</a><?php endif; ?>
            <a href="mailto:contact@wasla.tn">Contact</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <span data-year>2026</span> WASLA — Connecter. Anticiper. Protéger.</span>
        <span class="footer-legal">
          <span data-icon="leaf"></span>
          Prototype de démonstration — données illustratives
        </span>
        <div class="footer-bottom__links">
          <a href="<?= url('a-propos', [], 'mentions') ?>">Mentions légales</a>
          <a href="<?= url('a-propos', [], 'confidentialite') ?>">Confidentialité</a>
          <a href="<?= url('a-propos', [], 'accessibilite') ?>">Accessibilité</a>
        </div>
      </div>
    </div>
  </footer>


  <!-- Back to Top -->
  <a href="#" class="back-to-top" data-back-to-top aria-label="Retour en haut" hidden>
    <span data-icon="arrow-up"></span>
  </a>
