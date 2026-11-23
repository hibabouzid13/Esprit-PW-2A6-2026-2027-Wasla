<?php
/**
 * routes/module1.php — Module 1 : Interruptions, signalements & alertes (Houssem)
 *
 * Seul Houssem modifie ce fichier (dans la branche module1-interruptions).
 * Ajoutez une entrée par action. Exemple :
 *
 *   'interruptions' => function (): void {
 *       AuthController::requireRole(['citoyen']);  // contrôle d'accès côté serveur
 *       $items = (new InterruptionController())->getAll();  // le contrôleur fait le SQL
 *       require __DIR__ . '/../views/frontoffice/pages/interruptions/liste.php';  // la vue affiche
 *   },
 *
 * Règles :
 *   - pages Back Office : clé qui commence par « admin- » + requireRole(...)
 *   - formulaire POST : la vue contient <?= csrfField() ?> (index.php vérifie le jeton)
 *   - clés uniques dans tout le projet (index.php signale les doublons)
 */

declare(strict_types=1);

if (!function_exists('m1_id')) {
    /**
     * Identifiant reçu par le formulaire ($_POST[$key]) ou par l'adresse (?id=N si $alsoGet).
     *   null → aucun id envoyé (ex. formulaire d'ajout)
     *   0    → id mal formé (liste, lettres…) : ne correspond à aucune ligne → « introuvable »
     *   N    → id composé uniquement de chiffres
     * (int) sur une liste donnerait 1 : on agirait sur la ligne n° 1. Ici, jamais.
     */
    function m1_id(string $key, bool $alsoGet = false): ?int
    {
        $raw = $_POST[$key] ?? ($alsoGet ? ($_GET['id'] ?? null) : null);
        if ($raw === null || $raw === '') {
            return null;
        }
        return is_string($raw) && ctype_digit($raw) && strlen($raw) <= 10 ? (int) $raw : 0;
    }
}

if (!function_exists('m1_old')) {
    /**
     * Saisie à réafficher après une erreur, champ par champ, en ne gardant que les textes
     * (un champ envoyé sous forme de liste devient '' : pas de TypeError dans e()).
     *
     * @param string[] $fields
     * @return array<string, string>
     */
    function m1_old(array $post, array $fields): array
    {
        $old = [];
        foreach ($fields as $f) {
            $old[$f] = is_string($post[$f] ?? null) ? trim($post[$f]) : '';
        }
        return $old;
    }
}

if (!function_exists('admin_interruption_action')) {
    /**
     * Clôture ou suppression d'une interruption (POST uniquement, CSRF vérifié par index.php).
     * Vérifie que l'interruption existe et que le rôle peut gérer sa ressource.
     */
    function admin_interruption_action(string $method, string $successMessage): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller   = new InterruptionController();
            $interruption = $controller->getById(m1_id('id_interruption') ?? 0);
            if ($interruption === null) {
                AuthController::flash('error', 'Interruption introuvable.');
            } elseif (!InterruptionController::canManage(AuthController::currentUser()->getRole(), $interruption->getTypeRessource())) {
                AuthController::forbidden('Vous ne pouvez pas gérer ce type d\'interruption.');
            } else {
                $controller->$method($interruption->getIdInterruption());
                AuthController::flash('success', $successMessage);
            }
        }
        header('Location: ' . url('admin-interruptions'));
        exit;
    }
}

if (!function_exists('admin_signalement_from_post')) {
    /**
     * Signalement visé par un formulaire POST du Back Office, après contrôle des droits.
     * Retourne null (avec un message) si ce n'est pas un POST ou si le signalement n'existe pas ;
     * 403 si le rôle ne gère pas cette ressource.
     */
    function admin_signalement_from_post(): ?SignalementModel
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }
        $signalement = (new SignalementController())->getById(m1_id('id_signalement') ?? 0);
        if ($signalement === null) {
            AuthController::flash('error', 'Signalement introuvable.');
            return null;
        }
        if (!InterruptionController::canManage(AuthController::currentUser()->getRole(), $signalement->getTypeRessource())) {
            AuthController::forbidden('Vous ne pouvez pas gérer ce type de signalement.');
        }
        return $signalement;
    }
}

return [
    // Carte des 24 gouvernorats + formulaire « Signaler une coupure »
    // Carte publique ; signalement + historique réservés aux utilisateurs connectés
    'carte' => function (): void {
        $user       = AuthController::currentUser();
        $canReport  = $user !== null && $user->getRole() === 'citoyen';   // seul le citoyen signale
        $historique = $canReport ? (new SignalementController())->getByUser($user->getIdUtilisateur()) : [];
        $zones      = $canReport ? (new ZoneController())->getGouvernorats() : [];
        $mapStatuses  = (new InterruptionController())->getMapStatuses();   // carte = données réelles
        $reportErrors = $_SESSION['report_errors'] ?? [];
        $reportOld    = $_SESSION['report_old'] ?? [];
        unset($_SESSION['report_errors'], $_SESSION['report_old']);
        require __DIR__ . '/../views/frontoffice/pages/interruptions/carte.php';
    },
    // Envoi d'un signalement (POST uniquement, utilisateur connecté obligatoire)
    'signaler' => function (): void {
        AuthController::requireRole(['citoyen']);   // visiteur → connexion ; autre rôle → 403
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('carte'));
            exit;
        }
        $user   = AuthController::currentUser();
        $errors = (new SignalementController())->create($_POST, $user->getIdUtilisateur());
        if ($errors) {
            $_SESSION['report_errors'] = $errors;
            $_SESSION['report_old'] = m1_old($_POST, ['zone', 'type', 'address', 'message']);
            AuthController::flash('error', 'Le signalement n\'a pas pu être envoyé : vérifiez le formulaire.');
        } else {
            AuthController::flash('success', 'Merci ! Votre signalement a bien été enregistré.');
        }
        header('Location: ' . url('carte', [], 'historique-signalements'));
        exit;
    },
    // Service « Alertes personnalisées »
    'alertes' => function (): void {
        $alertFeed = (new InterruptionController())->getAlertFeed();   // notifications = interruptions du Back Office
        require __DIR__ . '/../views/frontoffice/pages/interruptions/alertes.php';
    },
    // Back Office : liste des interruptions
    'admin-interruptions' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $role          = AuthController::currentUser()->getRole();
        $interruptions = (new InterruptionController())->getAll($role);
        require __DIR__ . '/../views/backoffice/pages/interruptions/liste.php';
    },
    // Back Office — formulaire de publication / modification (admin, STEG, SONEDE)
    //   GET  ?id=N → modification ; sans id → publication
    'admin-interruption-form' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $user       = AuthController::currentUser();
        $controller = new InterruptionController();
        $id         = m1_id('id_interruption', true);   // null = publication, sinon modification
        $interruption = null;
        if ($id !== null) {
            $interruption = $controller->getById($id);
            if ($interruption === null) {
                AuthController::flash('error', 'Interruption introuvable.');
                header('Location: ' . url('admin-interruptions'));
                exit;
            }
            if (!InterruptionController::canManage($user->getRole(), $interruption->getTypeRessource())) {
                AuthController::forbidden('Vous ne pouvez pas modifier ce type d\'interruption.');
            }
        }

        $errors = [];
        $old    = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $interruption !== null
                ? $controller->update($interruption, $_POST, $user->getRole())
                : $controller->create($_POST, $user->getIdUtilisateur(), $user->getRole());
            if (!$errors) {
                AuthController::flash('success', $interruption !== null ? 'Interruption modifiée.' : 'Interruption publiée.');
                header('Location: ' . url('admin-interruptions'));
                exit;
            }
            $old = m1_old($_POST, ['type_ressource', 'id_zone', 'statut', 'date_debut', 'date_fin_prevue', 'cause']);
        }

        $zones        = (new ZoneController())->getGouvernorats();
        $allowedTypes = InterruptionController::typesFor($user->getRole());
        require __DIR__ . '/../views/backoffice/pages/interruptions/form.php';
    },
    // Back Office — liste des signalements citoyens (filtrés par ressource selon le rôle)
    'admin-signalements' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $role         = AuthController::currentUser()->getRole();
        $signalements = (new SignalementController())->getAllForAdmin($role);
        // Interruptions groupées par « zone|ressource » pour proposer un rattachement cohérent
        $interruptionsParCle = [];
        foreach ((new InterruptionController())->getAll($role) as $i) {
            $interruptionsParCle[$i->getIdZone() . '|' . $i->getTypeRessource()][] = $i;
        }
        $rowErrors = $_SESSION['signalement_errors'] ?? [];
        unset($_SESSION['signalement_errors']);
        require __DIR__ . '/../views/backoffice/pages/interruptions/signalements.php';
    },
    // Back Office — traiter un signalement (POST : statut + interruption rattachée)
    'admin-signalement-traiter' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        if ($signalement = admin_signalement_from_post()) {
            $errors = (new SignalementController())->process($signalement, $_POST);
            if ($errors) {
                $_SESSION['signalement_errors'] = [$signalement->getIdSignalement() => $errors];
                AuthController::flash('error', 'SIG-' . $signalement->getIdSignalement() . ' : ' . implode(' ', $errors));
            } else {
                AuthController::flash('success', 'Signalement SIG-' . $signalement->getIdSignalement() . ' mis à jour.');
            }
        }
        header('Location: ' . url('admin-signalements'));
        exit;
    },
    // Back Office — supprimer un signalement (POST id)
    'admin-signalement-supprimer' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        if ($signalement = admin_signalement_from_post()) {
            (new SignalementController())->delete($signalement->getIdSignalement());
            AuthController::flash('success', 'Signalement supprimé.');
        }
        header('Location: ' . url('admin-signalements'));
        exit;
    },
    // ---------------- ZONES GÉOGRAPHIQUES (super admin uniquement) ----------------
    // Liste des gouvernorats et délégations
    'admin-zones' => function (): void {
        AuthController::requireRole(['admin']);
        $zones = (new ZoneAdminController())->getAll();
        require __DIR__ . '/../views/backoffice/pages/interruptions/zones.php';
    },
    // Ajout (sans id) ou modification (?id=N) d'une zone
    'admin-zone-form' => function (): void {
        AuthController::requireRole(['admin']);
        $controller = new ZoneAdminController();
        $id   = m1_id('id_zone', true);   // null = ajout, sinon modification
        $zone = null;
        if ($id !== null && ($zone = $controller->getById($id)) === null) {
            AuthController::flash('error', 'Zone introuvable.');
            header('Location: ' . url('admin-zones'));
            exit;
        }
        $errors = [];
        $old    = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $zone !== null ? $controller->update($zone, $_POST) : $controller->create($_POST);
            if (!$errors) {
                AuthController::flash('success', $zone !== null ? 'Zone modifiée.' : 'Zone ajoutée.');
                header('Location: ' . url('admin-zones'));
                exit;
            }
            $old = m1_old($_POST, ['nom_zone', 'type_zone', 'id_zone_parent']);
        }
        $gouvernorats = $controller->getGouvernorats();
        require __DIR__ . '/../views/backoffice/pages/interruptions/zone-form.php';
    },
    // Suppression d'une zone (POST id_zone), refusée si la zone est utilisée
    'admin-zone-supprimer' => function (): void {
        AuthController::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new ZoneAdminController();
            $zone = $controller->getById(m1_id('id_zone') ?? 0);
            if ($zone === null) {
                AuthController::flash('error', 'Zone introuvable.');
            } elseif (($erreur = $controller->delete($zone)) !== '') {
                AuthController::flash('error', $zone->getNomZone() . ' : ' . $erreur);
            } else {
                AuthController::flash('success', 'Zone supprimée : ' . $zone->getNomZone() . '.');
            }
        }
        header('Location: ' . url('admin-zones'));
        exit;
    },

    // ---------------- COMPTES SOUS-ADMINS STEG / SONEDE (super admin uniquement) ----------------
    // Liste (recherche + filtres rôle et statut dans la page)
    'admin-sous-admins' => function (): void {
        AuthController::requireRole(['admin']);
        $controller = new CompteController();
        $comptes    = $controller->getAll();
        $parStatut  = $controller->countByStatut();
        require __DIR__ . '/../views/backoffice/pages/comptes/sous-admins.php';
    },
    // Ajout (sans id) ou modification (?id=N) d'un sous-admin
    'admin-sous-admin-form' => function (): void {
        AuthController::requireRole(['admin']);
        $controller = new CompteController();
        $id     = m1_id('id_utilisateur', true);
        $compte = null;
        if ($id !== null && ($compte = $controller->getById($id)) === null) {
            AuthController::flash('error', 'Compte introuvable.');
            header('Location: ' . url('admin-sous-admins'));
            exit;
        }
        $errors = [];
        $old    = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $compte !== null ? $controller->update($compte, $_POST) : $controller->create($_POST);
            if (!$errors) {
                AuthController::flash('success', $compte !== null ? 'Compte modifié.' : 'Compte sous-admin créé.');
                header('Location: ' . url('admin-sous-admins'));
                exit;
            }
            // Jamais le mot de passe dans $old : il n'est pas réaffiché
            $old = m1_old($_POST, ['prenom', 'nom', 'email', 'telephone', 'id_zone', 'role', 'nom_organisation']);
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/backoffice/pages/comptes/sous-admin-form.php';
    },
    // Fiche d'un sous-admin (?id=N)
    'admin-sous-admin-voir' => function (): void {
        AuthController::requireRole(['admin']);
        $controller = new CompteController();
        $compte = $controller->getById(m1_id('id_utilisateur', true) ?? 0);
        if ($compte === null) {
            AuthController::flash('error', 'Compte introuvable.');
            header('Location: ' . url('admin-sous-admins'));
            exit;
        }
        $liens = $controller->donneesLiees($compte->getIdUtilisateur());   // pourquoi la suppression serait refusée
        require __DIR__ . '/../views/backoffice/pages/comptes/sous-admin-voir.php';
    },
    // Suspendre / réactiver (POST id_utilisateur + statut)
    'admin-sous-admin-statut' => function (): void {
        AuthController::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new CompteController();
            $compte = $controller->getById(m1_id('id_utilisateur') ?? 0);
            $statut = is_string($_POST['statut'] ?? null) ? $_POST['statut'] : '';
            if ($compte === null) {
                AuthController::flash('error', 'Compte introuvable.');
            } elseif (($erreur = $controller->setStatut($compte, $statut)) !== '') {
                AuthController::flash('error', $erreur);
            } else {
                AuthController::flash('success', ($statut === 'actif' ? 'Compte réactivé : ' : 'Compte suspendu : ')
                    . $compte->getPrenom() . ' ' . $compte->getNom() . '.');
            }
        }
        header('Location: ' . url('admin-sous-admins'));
        exit;
    },
    // Suppression sûre (POST id_utilisateur) : refusée si le compte a des données
    'admin-sous-admin-supprimer' => function (): void {
        AuthController::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erreur = (new CompteController())->delete(m1_id('id_utilisateur') ?? 0);
            AuthController::flash($erreur === null ? 'success' : 'error', $erreur ?? 'Compte supprimé.');
        }
        header('Location: ' . url('admin-sous-admins'));
        exit;
    },

    // ---------------- MON PROFIL (personnel du Back Office) ----------------
    // Modifier son propre profil : l'id vient de la session, jamais du formulaire
    'admin-profil' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        $moi    = AuthController::currentUser();
        $errors = [];
        $old    = [];
        $pwdErrors = $_SESSION['pwd_errors'] ?? [];
        unset($_SESSION['pwd_errors']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new CompteController())->updateProfil($moi, $_POST);
            if (!$errors) {
                AuthController::flash('success', 'Votre profil a été mis à jour.');
                header('Location: ' . url('admin-profil'));
                exit;
            }
            $old = m1_old($_POST, ['prenom', 'nom', 'email', 'telephone', 'id_zone']);
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/backoffice/pages/comptes/profil.php';
    },
    // Changer son propre mot de passe (POST) : l'actuel est demandé, puis la session est renouvelée
    'admin-mot-de-passe' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new CompteController())->changePassword(AuthController::currentUser(), $_POST);
            if ($errors) {
                $_SESSION['pwd_errors'] = $errors;
                AuthController::flash('error', 'Le mot de passe n\'a pas été changé : vérifiez le formulaire.');
            } else {
                AuthController::flash('success', 'Votre mot de passe a été changé.');
            }
        }
        header('Location: ' . url('admin-profil', [], 'mot-de-passe'));
        exit;
    },
    // Back Office — clôturer (POST id) : statut terminée + fin réelle = maintenant
    'admin-interruption-cloturer' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        admin_interruption_action('close', 'Interruption clôturée.');
    },
    // Back Office — supprimer (POST id)
    'admin-interruption-supprimer' => function (): void {
        AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
        admin_interruption_action('delete', 'Interruption supprimée.');
    },
];
