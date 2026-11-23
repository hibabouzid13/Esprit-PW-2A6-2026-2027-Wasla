<?php
/**
 * routes/module3.php — Module 3 : Refuge Zones (Louay)
 *
 * Seul Louay modifie ce fichier (dans la branche module3-refuges).
 *
 * Front Office
 *   refuges                  carte + liste des refuges (données de la base)          public
 *   refuge-reserver&id=N     formulaire de réservation d'une place                   citoyen…
 *   mes-reservations         réservations du citoyen + annulation                     citoyen…
 *   reservation-annuler      (POST) annulation par le citoyen
 *   inscription-entreprise   création d'un compte entreprise (en attente de validation)
 *   profil-entreprise        profil de l'entreprise (même non vérifiée)               entreprise
 *   mot-de-passe-entreprise  (POST) changement du mot de passe (l'actuel est demandé) entreprise
 *   supprimer-compte-entreprise (POST) suppression du compte, ou désactivation s'il a des données
 *   mes-refuges              refuges de l'entreprise + réservations reçues           entreprise
 *   refuge-ajouter           formulaire d'ajout                                       entreprise
 *   refuge-modifier&id=N     formulaire de modification                               entreprise
 *   refuge-supprimer         (POST) suppression (fermeture s'il a des réservations)   entreprise
 *   reservation-statut       (POST) confirmer / refuser / terminer une réservation    entreprise
 *
 * Back Office (admin)
 *   admin-refuges            tous les refuges, validation                             admin
 *   admin-refuge-action      (POST) valider / retirer / supprimer un refuge
 *   admin-entreprises        comptes entreprises (filtres + recherche)                admin
 *   admin-entreprise-voir&id=N  fiche : informations, refuges, données liées
 *   admin-entreprise-form    ajout (sans id) / modification (&id=N, sans mot de passe)
 *   admin-entreprise-action  (POST) valider / retirer la validation / suspendre / réactiver / supprimer (si aucune donnée)
 *
 * Toutes les routes protégées appellent requireRole() en premier, en GET comme en POST.
 */

declare(strict_types=1);

/** Identifiant entier lu dans $_GET ou $_POST (0 si absent ou invalide). */
$refugeId = static function (array $source, string $key = 'id'): int {
    $value = $source[$key] ?? '';
    return is_string($value) && ctype_digit($value) ? (int) $value : 0;
};

/**
 * Valeurs à réafficher dans un formulaire après une erreur.
 * Seuls les champs à choix multiples (services, besoins) sont des listes ; pour les autres,
 * une liste reçue devient '' (sinon e() lèverait une TypeError dans la vue).
 */
$refugeOld = static function (array $keys, array $source): array {
    $old = [];
    foreach ($keys as $key) {
        $value = $source[$key] ?? '';
        if (in_array($key, ['services', 'besoins'], true)) {
            $old[$key] = is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        } else {
            $old[$key] = is_string($value) ? trim($value) : '';
        }
    }
    return $old;
};

/** Texte reçu (chaîne vide si absent ou si c'est une liste). */
$refugeText = static function (array $source, string $key): string {
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

/** Affiche la page « Mon profil entreprise » (profil, mot de passe, suppression du compte). */
$renderProfil = static function (UserModel $user, array $errors = [], ?array $old = null, array $pwdErrors = [], array $delErrors = []): void {
    $old ??= [
        'nom_organisation' => (string) $user->getNomOrganisation(),
        'matricule_fiscal' => (string) $user->getMatriculeFiscal(),
        'secteur_activite' => (string) $user->getSecteurActivite(),
        'prenom'           => $user->getPrenom(),
        'nom'              => $user->getNom(),
        'email'            => $user->getEmail(),
        'telephone'        => (string) $user->getTelephone(),
        'id_zone'          => (string) ($user->getIdZone() ?? ''),
        'adresse'          => (string) $user->getAdresse(),
        'description'      => (string) $user->getDescription(),
    ];
    $gouvernorats = (new ZoneController())->getGouvernorats();
    $secteurs = EntrepriseController::SECTEURS;
    require __DIR__ . '/../views/frontoffice/pages/refuges/profil-entreprise.php';
};

return [

    // ================================================================
    // FRONT OFFICE — PUBLIC / CITOYEN
    // ================================================================

    // Carte, liste et fiches des refuges (assets/js/services/refuges.js lit les données de la base)
    'refuges' => function (): void {
        $controller = new RefugeZoneController();
        $refuges = $controller->getPublic();
        $mapData = $controller->toMapData($refuges);
        $user = AuthController::currentUser();
        $canReserve = $user !== null && in_array($user->getRole(), ReservationRefugeController::ROLES_RESERVATION, true);
        $myReservations = $canReserve ? (new ReservationRefugeController())->activeByUser($user->getIdUtilisateur()) : [];
        require __DIR__ . '/../views/frontoffice/pages/refuges/zones-refuge.php';
    },

    'refuge-reserver' => function () use ($refugeId, $refugeOld): void {
        AuthController::requireRole(ReservationRefugeController::ROLES_RESERVATION);
        $user = AuthController::currentUser();
        $id = $refugeId($_GET);
        $refuge = (new RefugeZoneController())->findPublic($id);
        if ($refuge === null) {
            AuthController::flash('error', 'Ce lieu est introuvable ou n\'est plus proposé.');
            header('Location: ' . url('refuges'));
            exit;
        }

        $errors = [];
        $old = [
            'nom_contact'      => $user->getPrenom() . ' ' . $user->getNom(),
            'telephone'        => (string) $user->getTelephone(),
            'nombre_personnes' => '1',
            'besoins'          => [],
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new ReservationRefugeController())->reserve($id, $_POST, $user);
            $old = $refugeOld(array_keys($old), $_POST);
            if ($errors === []) {
                AuthController::flash('success', 'Votre réservation au « ' . $refuge->getNom() . ' » est enregistrée. L\'établissement va la confirmer.');
                header('Location: ' . url('mes-reservations'));
                exit;
            }
        }
        require __DIR__ . '/../views/frontoffice/pages/refuges/reserver.php';
    },

    'mes-reservations' => function (): void {
        AuthController::requireRole(ReservationRefugeController::ROLES_RESERVATION);
        $reservations = (new ReservationRefugeController())->getByUser(AuthController::currentUser()->getIdUtilisateur());
        require __DIR__ . '/../views/frontoffice/pages/refuges/mes-reservations.php';
    },

    'reservation-annuler' => function () use ($refugeId): void {
        AuthController::requireRole(ReservationRefugeController::ROLES_RESERVATION);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ok = (new ReservationRefugeController())->cancelByUser($refugeId($_POST), AuthController::currentUser());
            AuthController::flash($ok ? 'success' : 'error', $ok ? 'Réservation annulée : les places ont été libérées.' : 'Cette réservation ne peut pas être annulée.');
        }
        header('Location: ' . url('mes-reservations'));
        exit;
    },

    // ================================================================
    // FRONT OFFICE — ENTREPRISE
    // ================================================================

    'inscription-entreprise' => function () use ($refugeOld): void {
        if (($user = AuthController::currentUser()) !== null) {
            header('Location: ' . url(AuthController::homeFor($user)));
            exit;
        }
        $errors = [];
        $keys = ['nom_organisation', 'matricule_fiscal', 'secteur_activite', 'prenom', 'nom', 'email', 'telephone', 'id_zone', 'adresse', 'description', 'terms'];
        $old = array_fill_keys($keys, '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new EntrepriseController())->register($_POST);
            $old = $refugeOld($keys, $_POST);
            if ($errors === []) {
                AuthController::flash('success', 'Compte entreprise créé. Vous pourrez publier vos refuges dès que l\'administrateur l\'aura validé.');
                header('Location: ' . url('connexion'));
                exit;
            }
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        $secteurs = EntrepriseController::SECTEURS;
        require __DIR__ . '/../views/frontoffice/pages/refuges/inscription-entreprise.php';
    },

    'profil-entreprise' => function () use ($refugeOld, $renderProfil): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();          // l'id vient de la session, jamais du formulaire
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $renderProfil($user);
            return;
        }
        $keys = ['nom_organisation', 'matricule_fiscal', 'secteur_activite', 'prenom', 'nom', 'email', 'telephone', 'id_zone', 'adresse', 'description'];
        [$errors, $reverifier] = (new EntrepriseController())->updateProfil($user, $_POST);
        if ($errors !== []) {
            $renderProfil($user, $errors, $refugeOld($keys, $_POST));
            return;
        }
        AuthController::flash('success', 'Profil mis à jour.');
        if ($reverifier) {
            AuthController::flash('info', "Votre matricule fiscal a changé : votre compte doit être vérifié de nouveau par l'administrateur. Vos refuges sont masqués de la carte en attendant.");
        }
        header('Location: ' . url('profil-entreprise'));
        exit;
    },

    'mot-de-passe-entreprise' => function () use ($renderProfil): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('profil-entreprise'));
            exit;
        }
        $errors = (new EntrepriseController())->changePassword($user, $_POST);
        if ($errors !== []) {
            $renderProfil($user, [], null, $errors);
            return;
        }
        session_regenerate_id(true);   // nouvel identifiant de session après un changement de mot de passe
        AuthController::flash('success', 'Mot de passe modifié.');
        header('Location: ' . url('profil-entreprise'));
        exit;
    },

    'supprimer-compte-entreprise' => function () use ($renderProfil): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('profil-entreprise'));
            exit;
        }
        [$errors, $result] = (new EntrepriseController())->deleteOwnAccount($user, $_POST);
        if ($errors !== []) {
            $renderProfil($user, [], null, [], $errors);
            return;
        }
        (new AuthController())->logout();
        session_start();
        AuthController::flash('success', $result === 'supprime'
            ? 'Votre compte entreprise a été supprimé.'
            : 'Votre compte a des données (refuges, réservations) : il a été désactivé et ces données sont conservées. Les réservations en cours ont été annulées.');
        header('Location: ' . url('connexion'));
        exit;
    },

    'mes-refuges' => function (): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();
        $refuges = (new RefugeZoneController())->getByEntreprise($user->getIdUtilisateur());
        $reservations = (new ReservationRefugeController())->getByEntreprise($user->getIdUtilisateur());
        require __DIR__ . '/../views/frontoffice/pages/refuges/mes-refuges.php';
    },

    'refuge-ajouter' => function () use ($refugeOld): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();
        if (!$user->isCompteVerifie()) {
            AuthController::flash('info', 'Votre compte entreprise doit être validé par l\'administrateur avant de publier un refuge.');
            header('Location: ' . url('mes-refuges'));
            exit;
        }
        $refuge = null;
        $errors = [];
        $keys = ['nom', 'adresse', 'id_zone', 'latitude', 'longitude', 'services', 'capacite', 'horaires', 'accessible_pmr'];
        $old = array_fill_keys($keys, '');
        $old['services'] = [];
        $old['id_zone'] = (string) ($user->getIdZone() ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new RefugeZoneController())->create($_POST, $user);
            $old = $refugeOld($keys, $_POST);
            if ($errors === []) {
                AuthController::flash('success', 'Refuge enregistré. Il apparaîtra sur la carte après validation par l\'administrateur.');
                header('Location: ' . url('mes-refuges'));
                exit;
            }
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/refuges/refuge-form.php';
    },

    'refuge-modifier' => function () use ($refugeId, $refugeOld): void {
        AuthController::requireRole(['entreprise']);
        $user = AuthController::currentUser();
        $controller = new RefugeZoneController();
        $id = $refugeId($_GET);
        $refuge = $controller->findOwned($id, $user->getIdUtilisateur());   // propriété vérifiée
        if ($refuge === null) {
            AuthController::flash('error', 'Refuge introuvable.');
            header('Location: ' . url('mes-refuges'));
            exit;
        }
        $errors = [];
        $old = [
            'nom'            => $refuge->getNom(),
            'adresse'        => $refuge->getAdresse(),
            'id_zone'        => (string) $refuge->getIdZone(),
            'latitude'       => $refuge->getLatitude() !== null ? rtrim(rtrim(number_format($refuge->getLatitude(), 3, '.', ''), '0'), '.') : '',
            'longitude'      => $refuge->getLongitude() !== null ? rtrim(rtrim(number_format($refuge->getLongitude(), 3, '.', ''), '0'), '.') : '',
            'services'       => $refuge->getServices(),
            'capacite'       => (string) $refuge->getCapacite(),
            'horaires'       => (string) $refuge->getHoraires(),
            'accessible_pmr' => $refuge->isAccessiblePmr() ? '1' : '',
            'statut'         => $refuge->getStatut() === 'ferme' ? 'ferme' : 'ouvert',
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $controller->update($id, $_POST, $user);
            $old = $refugeOld(array_keys($old), $_POST);
            if ($errors === []) {
                AuthController::flash('success', 'Refuge « ' . trim((string) $_POST['nom']) . ' » mis à jour.');
                header('Location: ' . url('mes-refuges'));
                exit;
            }
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/refuges/refuge-form.php';
    },

    'refuge-supprimer' => function () use ($refugeId): void {
        AuthController::requireRole(['entreprise']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = (new RefugeZoneController())->delete($refugeId($_POST), AuthController::currentUser());
            match ($result) {
                'supprime' => AuthController::flash('success', 'Refuge supprimé.'),
                'ferme'    => AuthController::flash('info', 'Ce refuge a des réservations : il a été fermé au lieu d\'être supprimé, pour conserver l\'historique des citoyens.'),
                default    => AuthController::flash('error', 'Refuge introuvable.'),
            };
        }
        header('Location: ' . url('mes-refuges'));
        exit;
    },

    // Permis aussi à une entreprise non vérifiée : ses refuges sont masqués (aucune nouvelle réservation),
    // mais elle doit pouvoir traiter les réservations déjà reçues. Une entreprise suspendue ne se connecte plus
    // et ses réservations en cours ont été annulées (EntrepriseController::setStatut()).
    'reservation-statut' => function () use ($refugeId, $refugeText): void {
        AuthController::requireRole(['entreprise']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $statut = $refugeText($_POST, 'statut');
            $ok = (new ReservationRefugeController())->setStatutByEntreprise($refugeId($_POST), $statut, AuthController::currentUser());
            $labels = ['confirmee' => 'Réservation confirmée.', 'annulee' => 'Réservation refusée : les places ont été libérées.', 'terminee' => 'Réservation terminée : les places ont été libérées.'];
            AuthController::flash($ok ? 'success' : 'error', $ok ? $labels[$statut] : 'Cette réservation ne peut plus être modifiée.');
        }
        header('Location: ' . url('mes-refuges', [], 'reservations'));
        exit;
    },

    // ================================================================
    // BACK OFFICE — ADMIN
    // ================================================================

    'admin-refuges' => function (): void {
        AuthController::requireRole(['admin']);
        $controller = new RefugeZoneController();
        $statut = is_string($_GET['statut'] ?? null) && in_array($_GET['statut'], RefugeZoneModel::STATUTS, true) ? $_GET['statut'] : '';
        $refuges = $controller->getAll($statut);
        $counts = $controller->countByStatut();
        require __DIR__ . '/../views/backoffice/pages/refuges/admin-refuges.php';
    },

    'admin-refuge-action' => function () use ($refugeId, $refugeText): void {
        AuthController::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new RefugeZoneController();
            $id = $refugeId($_POST);
            [$ok, $message] = match ($refugeText($_POST, 'op')) {
                'valider'   => [$controller->approve($id), 'Refuge validé : il est visible sur la carte publique.'],
                'retirer'   => [$controller->suspend($id), 'Refuge retiré de la carte (de nouveau en attente de validation).'],
                'supprimer' => match ($controller->adminDelete($id)) {
                    'supprime' => [true, 'Refuge supprimé.'],
                    'ferme'    => [true, 'Ce refuge a des réservations : il a été fermé au lieu d\'être supprimé.'],
                    default    => [false, ''],
                },
                default     => [false, ''],
            };
            AuthController::flash($ok ? 'success' : 'error', $ok ? $message : 'Action impossible sur ce refuge.');
        }
        header('Location: ' . url('admin-refuges'));
        exit;
    },

    'admin-entreprises' => function () use ($refugeText): void {
        AuthController::requireRole(['admin']);
        $controller = new EntrepriseController();
        $filtre = is_string($_GET['filtre'] ?? null) && in_array($_GET['filtre'], EntrepriseController::FILTRES, true) ? $_GET['filtre'] : '';
        $recherche = mb_substr($refugeText($_GET, 'q'), 0, 100);
        $entreprises = $controller->getAll($filtre, $recherche);
        $nbRefuges = $controller->countRefuges();
        $stats = $controller->stats();
        require __DIR__ . '/../views/backoffice/pages/refuges/admin-entreprises.php';
    },

    'admin-entreprise-voir' => function () use ($refugeId): void {
        AuthController::requireRole(['admin']);
        $controller = new EntrepriseController();
        $entreprise = $controller->findById($refugeId($_GET));
        if ($entreprise === null) {
            AuthController::flash('error', 'Compte entreprise introuvable.');
            header('Location: ' . url('admin-entreprises'));
            exit;
        }
        $id = $entreprise->getIdUtilisateur();
        $refuges = (new RefugeZoneController())->getByEntreprise($id);
        $reservations = (new ReservationRefugeController())->getByEntreprise($id);
        $donnees = $controller->donneesLiees($id);
        require __DIR__ . '/../views/backoffice/pages/refuges/admin-entreprise-voir.php';
    },

    'admin-entreprise-form' => function () use ($refugeId, $refugeOld): void {
        AuthController::requireRole(['admin']);
        $controller = new EntrepriseController();
        $id = $refugeId($_GET);
        $entreprise = $id > 0 ? $controller->findById($id) : null;
        if ($id > 0 && $entreprise === null) {
            AuthController::flash('error', 'Compte entreprise introuvable.');
            header('Location: ' . url('admin-entreprises'));
            exit;
        }
        $keys = ['nom_organisation', 'matricule_fiscal', 'secteur_activite', 'prenom', 'nom', 'email', 'telephone', 'id_zone', 'adresse', 'description', 'compte_verifie'];
        $old = $entreprise === null ? array_fill_keys($keys, '') : [
            'nom_organisation' => (string) $entreprise->getNomOrganisation(),
            'matricule_fiscal' => (string) $entreprise->getMatriculeFiscal(),
            'secteur_activite' => (string) $entreprise->getSecteurActivite(),
            'prenom'           => $entreprise->getPrenom(),
            'nom'              => $entreprise->getNom(),
            'email'            => $entreprise->getEmail(),
            'telephone'        => (string) $entreprise->getTelephone(),
            'id_zone'          => (string) ($entreprise->getIdZone() ?? ''),
            'adresse'          => (string) $entreprise->getAdresse(),
            'description'      => (string) $entreprise->getDescription(),
            'compte_verifie'   => $entreprise->isCompteVerifie() ? '1' : '',
        ];
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $controller->save($_POST, $entreprise?->getIdUtilisateur());
            $old = $refugeOld($keys, $_POST);
            if ($errors === []) {
                AuthController::flash('success', $entreprise === null ? 'Compte entreprise créé.' : 'Compte entreprise mis à jour.');
                header('Location: ' . url('admin-entreprises'));
                exit;
            }
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        $secteurs = EntrepriseController::SECTEURS;
        require __DIR__ . '/../views/backoffice/pages/refuges/admin-entreprise-form.php';
    },

    'admin-entreprise-action' => function () use ($refugeId, $refugeText): void {
        AuthController::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new EntrepriseController();
            $id = $refugeId($_POST);
            $op = $refugeText($_POST, 'op');
            if ($op === 'supprimer') {
                $error = $controller->delete($id);   // refusé si le compte a des données
                AuthController::flash($error === null ? 'success' : 'error', $error ?? 'Compte entreprise supprimé.');
            } else {
                [$ok, $message] = match ($op) {
                    'valider'    => [$controller->setVerifie($id, true), 'Compte entreprise validé : il peut publier des refuges.'],
                    'devalider'  => [$controller->setVerifie($id, false), 'Validation retirée : ses refuges sont masqués de la carte.'],
                    'suspendre'  => [$controller->setStatut($id, 'suspendu'), 'Compte suspendu : connexion impossible, refuges masqués, réservations en cours annulées.'],
                    'reactiver'  => [$controller->setStatut($id, 'actif'), 'Compte réactivé.'],
                    default      => [false, ''],
                };
                AuthController::flash($ok ? 'success' : 'error', $ok ? $message : 'Action impossible sur ce compte.');
            }
            // Retour vers la fiche si l'action y a été faite
            if ($refugeText($_POST, 'retour') === 'fiche' && $controller->findById($id) !== null) {
                header('Location: ' . url('admin-entreprise-voir', ['id' => $id]));
                exit;
            }
        }
        header('Location: ' . url('admin-entreprises'));
        exit;
    },
];
