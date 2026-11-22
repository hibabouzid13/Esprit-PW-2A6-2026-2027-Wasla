<?php
/**
 * routes/common.php — actions communes (branche main).
 *
 * Chaque entrée joue le rôle d'un « case » du switch du workshop :
 *   'action' => function () { contrôle d'accès → appel du contrôleur → require de la vue }
 *
 * Les variables créées dans la fonction (ex. $errors, $old) sont visibles dans la vue.
 */

declare(strict_types=1);

return [

    // ---------------- PAGES PUBLIQUES ----------------
    'accueil' => function (): void {
        require __DIR__ . '/../views/frontoffice/pages/accueil.php';
    },

    'a-propos' => function (): void {
        require __DIR__ . '/../views/frontoffice/pages/a-propos.php';
    },

    'services' => function (): void {
        require __DIR__ . '/../views/frontoffice/pages/services.php';
    },

    // ---------------- INSCRIPTION ----------------
    'inscription' => function (): void {
        if (($user = AuthController::currentUser()) !== null) {
            header('Location: ' . url(AuthController::homeFor($user)));
            exit;
        }
        $errors = [];
        $old = ['prenom' => '', 'nom' => '', 'email' => '', 'telephone' => '', 'id_zone' => '', 'terms' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new AuthController())->register($_POST);
            foreach (array_keys($old) as $key) {
                $old[$key] = trim(AuthController::texte($_POST[$key] ?? '') ?? '');
            }
            if ($errors === []) {
                AuthController::flash('success', 'Votre compte a été créé. Vous pouvez maintenant vous connecter.');
                header('Location: ' . url('connexion'));
                exit;
            }
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/auth/inscription.php';
    },

    // ---------------- CONNEXION ----------------
    'connexion' => function (): void {
        if (($user = AuthController::currentUser()) !== null) {
            header('Location: ' . url(AuthController::homeFor($user)));
            exit;
        }
        $errors = [];
        $old = ['email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new AuthController();
            $errors = $auth->login($_POST);
            $old['email'] = trim(AuthController::texte($_POST['email'] ?? '') ?? '');
            if ($errors === []) {
                $user = AuthController::currentUser();
                AuthController::flash('success', 'Bienvenue, ' . $user->getPrenom() . ' !');
                header('Location: ' . url(AuthController::homeFor($user)));   // redirection selon le rôle
                exit;
            }
        }
        require __DIR__ . '/../views/frontoffice/pages/auth/connexion.php';
    },

    // ---------------- DÉCONNEXION (POST + jeton CSRF) ----------------
    'deconnexion' => function (): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AuthController())->logout();
            session_start();
            AuthController::flash('success', 'Vous êtes déconnecté. À bientôt !');
        }
        header('Location: ' . url('connexion'));
        exit;
    },

    // ---------------- ESPACE PERSONNEL (Front Office, tout utilisateur connecté) ----------------
    'mon-compte' => function (): void {
        AuthController::requireAuth();
        $user = AuthController::currentUser();
        require __DIR__ . '/../views/frontoffice/pages/compte/mon-compte.php';
    },

    // ---------------- TABLEAU DE BORD (Back Office : admin, STEG, SONEDE) ----------------
    'dashboard' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $auth = new AuthController();
        $user = AuthController::currentUser();
        $totalUsers = $auth->countAllUsers();
        $countByRole = $auth->countByRole();
        $allUsers = $auth->getAllUsers();
        require __DIR__ . '/../views/backoffice/pages/dashboard.php';
    },
];
