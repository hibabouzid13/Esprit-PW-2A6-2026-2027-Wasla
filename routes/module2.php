<?php
/**
 * routes/module2.php — Module 2 : Analyse des factures & consommation (Hiba)
 *
 * Seul Hiba modifie ce fichier (dans la branche module2-factures).
 * Ajoutez une entrée par action. Exemple :
 *
 *   'factures' => function (): void {
 *       AuthController::requireRole(['citoyen']);  // contrôle d'accès côté serveur
 *       $items = (new FactureController())->getByUser();  // le contrôleur fait le SQL
 *       require __DIR__ . '/../views/frontoffice/pages/factures/liste.php';  // la vue affiche
 *   },
 *
 * Règles :
 *   - pages Back Office : clé qui commence par « admin- » + requireRole(...)
 *   - formulaire POST : la vue contient <?= csrfField() ?> (index.php vérifie le jeton)
 *   - clés uniques dans tout le projet (index.php signale les doublons)
 */

declare(strict_types=1);

return [
    // Page « Analyses & tendances » de la consommation
    'analyses' => function (): void {
        require __DIR__ . '/../views/frontoffice/pages/factures/analyses.php';
    },
    // Service « Factures & IA » : graphiques, analyse et assistant calculés sur les
    // factures du citoyen connecté (base de données uniquement, aucune donnée de démonstration)
    'factures' => function (): void {
        AuthController::requireRole(['citoyen']);
        $user = AuthController::currentUser();
        $factures = (new FactureController())->search(FactureController::scopeFor($user), FactureController::cleanFilters([]));
        $bills = array_map(fn (FactureModel $f) => FactureController::pourJs($f), $factures);
        $old = FactureController::emptyForm();
        require __DIR__ . '/../views/frontoffice/pages/factures/factures-ia.php';
    },

    // Ajout depuis la fenêtre « Ajouter une facture » de Factures & IA (fetch, réponse JSON).
    // Même contrôle que facture-ajouter : FactureController::create() ; jeton CSRF vérifié par index.php.
    'facture-ajouter-json' => function (): void {
        AuthController::requireRole(['citoyen']);
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'errors' => ['form' => 'Méthode non autorisée.']], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $user = AuthController::currentUser();
        $errors = (new FactureController())->create($_POST, $user->getIdUtilisateur(), FactureController::scopeFor($user));
        if ($errors === []) {
            AuthController::flash('success', 'Facture ajoutée.');
            echo json_encode(['ok' => true, 'redirect' => url('factures', ['onglet' => 'bills'])], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            // 200 + ok:false : erreurs de saisie normales (affichées sous les champs par factures.js)
            echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        }
        exit;
    },

    // =================================================================
    // CRUD FACTURE — Front Office (le citoyen gère SES factures)
    // =================================================================

    // Liste + recherche / filtres / tri
    'mes-factures' => function (): void {
        AuthController::requireRole(['citoyen']);
        $user = AuthController::currentUser();
        $scope = FactureController::scopeFor($user);
        $ctrl = new FactureController();
        $filters = FactureController::cleanFilters($_GET);
        $factures = $ctrl->search($scope, $filters);
        $summary = $ctrl->summary($scope);
        require __DIR__ . '/../views/frontoffice/pages/factures/mes-factures.php';
    },

    // Ajout
    'facture-ajouter' => function (): void {
        AuthController::requireRole(['citoyen']);
        $user = AuthController::currentUser();
        $errors = [];
        $old = FactureController::emptyForm();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new FactureController())->create($_POST, $user->getIdUtilisateur(), FactureController::scopeFor($user));
            if ($errors === []) {
                AuthController::flash('success', 'Facture ajoutée.');
                header('Location: ' . url('mes-factures'));
                exit;
            }
            $old = FactureController::postedForm($_POST);
        }
        $mode = 'ajout';
        $facture = null;
        require __DIR__ . '/../views/frontoffice/pages/factures/facture-form.php';
    },

    // Consultation (?id=…) : seulement une facture du citoyen connecté
    'facture-voir' => function (): void {
        AuthController::requireRole(['citoyen']);
        $scope = FactureController::scopeFor(AuthController::currentUser());
        $id = (int) ($_GET['id'] ?? 0);
        $ctrl = new FactureController();
        $facture = $id > 0 ? $ctrl->find($id, $scope) : null;
        if ($facture === null) {
            AuthController::flash('error', 'Facture introuvable.');
            header('Location: ' . url('mes-factures'));
            exit;
        }
        // Facture précédente du même fournisseur (comparaison) : même périmètre $scope
        $precedente = $ctrl->precedente($facture, $scope);
        require __DIR__ . '/../views/frontoffice/pages/factures/facture-voir.php';
    },

    // Modification (?id=…) : seulement une facture du citoyen connecté
    'facture-modifier' => function (): void {
        AuthController::requireRole(['citoyen']);
        $user = AuthController::currentUser();
        $scope = FactureController::scopeFor($user);
        $ctrl = new FactureController();
        $id = (int) ($_GET['id'] ?? 0);
        $facture = $id > 0 ? $ctrl->find($id, $scope) : null;
        if ($facture === null) {
            AuthController::flash('error', 'Facture introuvable.');
            header('Location: ' . url('mes-factures'));
            exit;
        }
        $errors = [];
        $old = FactureController::toForm($facture);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $ctrl->update($id, $_POST, $scope);
            if ($errors === []) {
                AuthController::flash('success', 'Facture modifiée.');
                header('Location: ' . url('facture-voir', ['id' => $id]));
                exit;
            }
            $old = FactureController::postedForm($_POST);
        }
        $mode = 'modification';
        require __DIR__ . '/../views/frontoffice/pages/factures/facture-form.php';
    },

    // Suppression : POST uniquement (jeton CSRF vérifié par index.php)
    'facture-supprimer' => function (): void {
        AuthController::requireRole(['citoyen']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $scope = FactureController::scopeFor(AuthController::currentUser());
            $ok = (new FactureController())->delete((int) ($_POST['id_facture'] ?? 0), $scope);
            AuthController::flash($ok ? 'success' : 'error', $ok ? 'Facture supprimée.' : 'Facture introuvable.');
        }
        // Retour vers la page d'origine (liste blanche : jamais une adresse fournie telle quelle)
        $retour = ($_POST['retour'] ?? '') === 'factures' ? url('factures', ['onglet' => 'bills']) : url('mes-factures');
        header('Location: ' . $retour);
        exit;
    },

    // =================================================================
    // CRUD FACTURE — Back Office (admin : toutes ; STEG : électricité ; SONEDE : eau)
    // =================================================================

    'admin-factures' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $user = AuthController::currentUser();
        $scope = FactureController::scopeFor($user);
        $ctrl = new FactureController();
        $filters = FactureController::cleanFilters($_GET);
        $factures = $ctrl->search($scope, $filters);
        $summary = $ctrl->summary($scope);
        require __DIR__ . '/../views/backoffice/pages/factures/liste.php';
    },

    // Ajout (sans ?id) ou modification (?id=…)
    'admin-facture-form' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $user = AuthController::currentUser();
        $scope = FactureController::scopeFor($user);
        $ctrl = new FactureController();
        $id = (int) ($_GET['id'] ?? 0);
        $facture = null;
        if ($id > 0) {
            $facture = $ctrl->find($id, $scope);
            if ($facture === null) {
                AuthController::flash('error', 'Facture introuvable.');
                header('Location: ' . url('admin-factures'));
                exit;
            }
        }
        $typeImpose = $scope['type'] ?? '';
        $errors = [];
        $old = $facture !== null ? FactureController::toForm($facture) : FactureController::emptyForm($typeImpose);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($facture !== null) {
                $errors = $ctrl->update($id, $_POST, $scope);
            } else {
                // Le titulaire doit être un compte citoyen actif
                $idCitoyen = (int) ($_POST['id_utilisateur'] ?? 0);
                $ids = array_map(fn ($c) => $c->getIdUtilisateur(), $ctrl->getCitoyens());
                $errors = in_array($idCitoyen, $ids, true)
                    ? $ctrl->create($_POST, $idCitoyen, $scope)
                    : ['id_utilisateur' => FactureController::MESSAGES['id_utilisateur']['required']];
            }
            if ($errors === []) {
                AuthController::flash('success', $facture !== null ? 'Facture modifiée.' : 'Facture ajoutée.');
                header('Location: ' . url('admin-factures'));
                exit;
            }
            $old = FactureController::postedForm($_POST) + $old;
            if ($facture !== null) {
                $old['id_utilisateur'] = (string) $facture->getIdUtilisateur();
            }
        }
        $citoyens = $facture === null ? $ctrl->getCitoyens() : [];
        require __DIR__ . '/../views/backoffice/pages/factures/form.php';
    },

    'admin-facture-supprimer' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $scope = FactureController::scopeFor(AuthController::currentUser());
            $ok = (new FactureController())->delete((int) ($_POST['id_facture'] ?? 0), $scope);
            AuthController::flash($ok ? 'success' : 'error', $ok ? 'Facture supprimée.' : 'Facture introuvable.');
        }
        header('Location: ' . url('admin-factures'));
        exit;
    },
];
