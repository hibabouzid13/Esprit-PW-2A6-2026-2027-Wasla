<?php
/**
 * index.php — point d'entrée UNIQUE de WASLA : index.php?action=<clé>
 *
 * Ordre imposé par le workshop « Sessions et Sécurité » (7.5) :
 *   1. configuration sécurisée de la session + session_start()
 *   2. expiration de la session après inactivité
 *   3. en-têtes HTTP de sécurité
 *   4. chargement des classes
 *   5. routage
 *
 * Le routage est réparti en un fichier par module (routes/common.php, routes/module1.php…) :
 * chaque membre ajoute ses actions dans SON fichier, et personne ne modifie index.php,
 * ce qui évite les conflits Git lors des fusions.
 */

declare(strict_types=1);

date_default_timezone_set('Africa/Tunis');

/** Durée maximale d'inactivité d'une session (secondes) : 30 minutes. */
const SESSION_LIFETIME = 1800;

// ---------------------------------------------------------------------
// 1. Session sécurisée (les ini_set doivent précéder session_start)
// ---------------------------------------------------------------------
ini_set('session.use_strict_mode', '1');   // refuse les identifiants de session inventés
ini_set('session.use_only_cookies', '1');  // jamais d'identifiant de session dans l'URL
ini_set('session.cookie_httponly', '1');   // cookie invisible pour JavaScript (vol via XSS)
ini_set('session.cookie_samesite', 'Lax'); // limite le CSRF
// ini_set('session.cookie_secure', '1');  // à activer si le site est servi en HTTPS
session_start();

// ---------------------------------------------------------------------
// 2. Expiration après 30 minutes d'inactivité
// ---------------------------------------------------------------------
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    $wasLoggedIn = !empty($_SESSION['user_id']);
    $_SESSION = [];
    session_destroy();
    session_start();
    if ($wasLoggedIn) {
        $_SESSION['flash'][] = ['type' => 'info', 'message' => 'Votre session a expiré après 30 minutes d\'inactivité. Veuillez vous reconnecter.'];
    }
}
$_SESSION['last_activity'] = time();

// ---------------------------------------------------------------------
// 3. En-têtes HTTP de sécurité
// ---------------------------------------------------------------------
header('X-Frame-Options: DENY');                                // anti clickjacking
header('X-Content-Type-Options: nosniff');                      // pas de « devinette » du type MIME
header('Referrer-Policy: strict-origin-when-cross-origin');
// CSP : scripts uniquement depuis nos fichiers .js (aucun <script> en ligne, aucun onclick="…")
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

// ---------------------------------------------------------------------
// 4. Chargement des classes
// ---------------------------------------------------------------------
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Chargement automatique : new FactureController() charge controllers/FactureController.php,
// FactureModel charge models/FactureModel.php. Aucun require à ajouter ici par les modules.
spl_autoload_register(function (string $class): void {
    foreach (['controllers', 'models'] as $dir) {
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// ---------------------------------------------------------------------
// 5. Routage
// ---------------------------------------------------------------------
// Chaque fichier routes/*.php retourne un tableau 'action' => function () { … }
$routes = [];
foreach (glob(__DIR__ . '/routes/*.php') ?: [] as $routeFile) {
    foreach (require $routeFile as $key => $handler) {
        if (isset($routes[$key])) {
            // Deux modules ont choisi la même clé : on le signale au lieu d'écraser en silence
            http_response_code(500);
            die('Erreur de routage : l\'action « ' . htmlspecialchars((string) $key) . ' » est déclarée deux fois (' . basename($routeFile) . ').');
        }
        $routes[$key] = $handler;
    }
}
$GLOBALS['WASLA_ROUTES'] = $routes;

$action = $_GET['action'] ?? 'accueil';
if (!is_string($action) || !isset($routes[$action])) {
    http_response_code(404);
    $errorCode = 404;
    $errorTitle = 'Page introuvable';
    $errorMessage = "La page demandée n'existe pas ou n'est pas encore disponible.";
    require __DIR__ . '/views/frontoffice/pages/erreurs/erreur.php';
    exit;
}

// Protection CSRF centralisée : AUCUN formulaire POST n'est traité sans jeton valide.
// (Chaque formulaire doit contenir le champ caché produit par csrfField().)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !AuthController::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    AuthController::flash('error', 'Jeton de sécurité invalide ou expiré. Veuillez réessayer.');
    header('Location: ' . url($action));
    exit;
}

$routes[$action]();
