<?php
/**
 * routes/module4.php — Module 4 : Assistance d'urgence, solaire & fournisseurs (Bechir)
 *
 * Seul Bechir modifie ce fichier (dans la branche module4-assistance).
 *
 * Comptes fournisseurs (document « Gestion des utilisateurs », partie 5)
 *   inscription-fournisseur         visiteur        Create : compte actif, non vérifié
 *   profil-fournisseur              fournisseur     Read et Update de son profil
 *   mot-de-passe-fournisseur        fournisseur     POST : Update du mot de passe (l'actuel est exigé)
 *   supprimer-compte-fournisseur    fournisseur     POST : Delete, ou désactivation s'il a des données
 *   admin-fournisseurs              admin           liste, recherche, filtres spécialité / statut / vérification
 *   admin-fournisseur-voir          admin           fiche et données liées
 *   admin-fournisseur-form          admin           Create et Update (sans mot de passe en modification)
 *   admin-fournisseur-verifier      admin, POST     vérifier / retirer la vérification
 *   admin-fournisseur-statut        admin, POST     suspendre / réactiver
 *   admin-fournisseur-supprimer     admin, POST     Delete si aucune donnée
 *
 * Demandes d'assistance et réponses
 *   relais, fournisseurs            pages publiques ; les POST exigent un rôle du Front Office
 *   demande-modifier, demande-statut, demande-supprimer, reponse-statut, reponse-supprimer
 *   sante                           POST (JSON) : un enregistrement Priorité santé devient une demande « sante »
 *   admin-demandes, admin-demande   Back Office (admin)
 *
 * Lecture des entrées : un id ne contient que des chiffres ; une valeur reçue sous forme de liste
 * est remplacée par "" (aucun avertissement PHP, aucune valeur « Array » enregistrée).
 */

declare(strict_types=1);

/** Rôles du Front Office autorisés à agir sur les demandes, plus l'admin (modération). */
const M4_DEMANDE_ROLES = ['citoyen', 'benevole', 'fournisseur', 'proprietaire', 'entreprise', 'admin'];

/** $_POST nettoyé : chaque valeur qui n'est pas un texte devient "". */
$m4Post = function (): array {
    $clean = [];
    foreach ($_POST as $key => $value) {
        $clean[(string) $key] = is_string($value) ? $value : '';
    }
    return $clean;
};

/** Texte reçu (GET ou POST), "" si absent ou si c'est une liste. */
$m4Txt = function (array $source, string $key): string {
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

/** Identifiant reçu : null si absent, 0 s'il n'est pas composé uniquement de chiffres (→ « introuvable »). */
$m4Id = function (array $source, string $key): ?int {
    if (!array_key_exists($key, $source)) {
        return null;
    }
    $value = $source[$key];
    return is_string($value) && ctype_digit($value) && strlen($value) <= 10 ? (int) $value : 0;
};

/** Valeurs d'un formulaire renvoyées à la vue après une erreur (textes uniquement). */
$m4Old = function (array $keys) use ($m4Txt): array {
    $old = [];
    foreach ($keys as $key) {
        $old[$key] = $m4Txt($_POST, $key);
    }
    return $old;
};

/** Filtres GET d'une liste. */
$m4Filters = function (array $keys) use ($m4Txt): array {
    $filters = [];
    foreach ($keys as $key) {
        $filters[$key] = $m4Txt($_GET, $key);
    }
    return $filters;
};

/** Refuse toute méthode autre que POST pour les actions qui modifient la base. */
$m4RequirePost = function (string $fallback): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . url($fallback));
        exit;
    }
};

/** Retour après une action POST (liste blanche : pas de redirection ouverte). */
$m4Back = function (string $default) use ($m4Txt, $m4Id): void {
    $allowed = ['relais', 'fournisseurs', 'admin-demandes', 'admin-demande', 'admin-fournisseurs', 'admin-fournisseur-voir'];
    $retour = $m4Txt($_POST, 'retour');
    if (!in_array($retour, $allowed, true)) {
        $retour = $default;
    }
    $params = [];
    $id = (int) $m4Id($_POST, 'id');
    if (in_array($retour, ['admin-demande', 'admin-fournisseur-voir'], true)) {
        if ($id > 0) {
            $params['id'] = $id;
        } else {
            $retour = $retour === 'admin-demande' ? 'admin-demandes' : 'admin-fournisseurs';
        }
    }
    $tab = $m4Txt($_POST, 'tab');
    if ($retour === 'relais' && in_array($tab, ['demande', 'offre', 'mine'], true)) {
        $params['tab'] = $tab;
    }
    header('Location: ' . url($retour, $params));
    exit;
};

/** Formulaire de réponse envoyé depuis le Relais ou la page Fournisseurs. @return array{0: array, 1: int, 2: array} */
$m4HandleReponse = function (DemandeAssistanceController $ctrl, string $retour) use ($m4Old, $m4Id, $m4Post): array {
    AuthController::requireRole(DemandeAssistanceController::FRONT_ROLES);
    $user = AuthController::currentUser();
    $idDemande = (int) $m4Id($_POST, 'id_demande');
    $demande = $ctrl->findById($idDemande);
    if ($demande === null) {
        AuthController::flash('error', 'Demande introuvable.');
        header('Location: ' . url($retour));
        exit;
    }
    $errors = $ctrl->addReponse($demande, $m4Post(), $user);
    if (isset($errors['form'])) {
        // Refus global (propre demande, rôle, déjà répondu…) : le formulaire n'est pas affiché, on passe par un message flash
        AuthController::flash('error', $errors['form']);
        header('Location: ' . url($retour));
        exit;
    }
    if ($errors === []) {
        AuthController::flash('success', 'Votre réponse a été envoyée.');
        header('Location: ' . url($retour, [], $retour === 'relais' ? 'demande-' . $idDemande : 'demandes-recues'));
        exit;
    }
    return [$errors, $idDemande, $m4Old(['message', 'id_installation'])];
};

/** Page « Mon profil » du fournisseur, avec les erreurs éventuelles de chacun des trois formulaires. */
$m4Profil = function (array $errors = [], ?array $old = null, array $pwdErrors = [], array $delErrors = []): void {
    $user = AuthController::currentUser();
    $old ??= [
        'prenom'           => $user->getPrenom(),
        'nom'              => $user->getNom(),
        'email'            => $user->getEmail(),
        'telephone'        => (string) $user->getTelephone(),
        'id_zone'          => (string) ($user->getIdZone() ?? ''),
        'adresse'          => (string) $user->getAdresse(),
        'nom_organisation' => (string) $user->getNomOrganisation(),
        'specialite'       => (string) $user->getSpecialite(),
        'description'      => (string) $user->getDescription(),
    ];
    $gouvernorats = (new ZoneController())->getGouvernorats();
    require __DIR__ . '/../views/frontoffice/pages/fournisseurs/profil.php';
};

/** Champs du formulaire de compte fournisseur. */
const M4_FOURNISSEUR_FIELDS = ['prenom', 'nom', 'email', 'telephone', 'id_zone', 'adresse', 'nom_organisation', 'specialite', 'description'];

return [

    // ==================================================================
    // COMPTES FOURNISSEURS — FRONT OFFICE
    // ==================================================================

    'inscription-fournisseur' => function () use ($m4Old, $m4Post): void {
        if (($user = AuthController::currentUser()) !== null) {
            header('Location: ' . url(AuthController::homeFor($user)));
            exit;
        }
        $errors = [];
        $old = array_fill_keys(array_merge(M4_FOURNISSEUR_FIELDS, ['terms']), '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new FournisseurController())->register($m4Post());
            if ($errors === []) {
                AuthController::flash('success', "Votre compte fournisseur est créé. Connectez-vous : il apparaîtra dans l'annuaire après vérification par l'administrateur.");
                header('Location: ' . url('connexion'));
                exit;
            }
            $old = $m4Old(array_keys($old));
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/fournisseurs/inscription.php';
    },

    'profil-fournisseur' => function () use ($m4Old, $m4Post, $m4Profil): void {
        AuthController::requireRole(['fournisseur']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = (new FournisseurController())->updateProfil(AuthController::currentUser(), $m4Post());
            if ($errors === []) {
                AuthController::flash('success', 'Votre profil a été mis à jour.');
                header('Location: ' . url('profil-fournisseur'));
                exit;
            }
            $m4Profil($errors, $m4Old(M4_FOURNISSEUR_FIELDS));
            return;
        }
        $m4Profil();
    },

    'mot-de-passe-fournisseur' => function () use ($m4RequirePost, $m4Post, $m4Profil): void {
        AuthController::requireRole(['fournisseur']);
        $m4RequirePost('profil-fournisseur');
        $errors = (new FournisseurController())->changePassword(AuthController::currentUser(), $m4Post());
        if ($errors === []) {
            AuthController::flash('success', 'Votre mot de passe a été changé.');
            header('Location: ' . url('profil-fournisseur'));
            exit;
        }
        $m4Profil([], null, $errors);
    },

    'supprimer-compte-fournisseur' => function () use ($m4RequirePost, $m4Post, $m4Profil): void {
        AuthController::requireRole(['fournisseur']);
        $m4RequirePost('profil-fournisseur');
        $outcome = (new FournisseurController())->deleteOwn(AuthController::currentUser(), $m4Post());
        if ($outcome['result'] === null) {
            $m4Profil([], null, [], $outcome['errors']);
            return;
        }
        (new AuthController())->logout();
        session_start();
        AuthController::flash('success', $outcome['result'] === 'supprime'
            ? 'Votre compte a été supprimé.'
            : 'Votre compte a des demandes enregistrées : il a été désactivé et vos données sont conservées.');
        header('Location: ' . url('connexion'));
        exit;
    },

    // ==================================================================
    // COMPTES FOURNISSEURS — BACK OFFICE (super admin)
    // ==================================================================

    'admin-fournisseurs' => function () use ($m4Filters): void {
        AuthController::requireRole(['admin']);
        $filters = $m4Filters(['q', 'specialite', 'statut', 'verifie']);
        $fournisseurs = (new FournisseurController())->search($filters);
        require __DIR__ . '/../views/backoffice/pages/fournisseurs/liste.php';
    },

    'admin-fournisseur-voir' => function () use ($m4Id): void {
        AuthController::requireRole(['admin']);
        $ctrl = new FournisseurController();
        $fournisseur = $ctrl->find((int) $m4Id($_GET, 'id'));
        if ($fournisseur === null) {
            AuthController::flash('error', 'Fournisseur introuvable.');
            header('Location: ' . url('admin-fournisseurs'));
            exit;
        }
        $donnees = $ctrl->donneesLiees($fournisseur->getIdUtilisateur());
        $demandesRecues = (new DemandeAssistanceController())->getRecuesParFournisseur($fournisseur->getIdUtilisateur());
        require __DIR__ . '/../views/backoffice/pages/fournisseurs/voir.php';
    },

    'admin-fournisseur-form' => function () use ($m4Id, $m4Old, $m4Post): void {
        AuthController::requireRole(['admin']);
        $ctrl = new FournisseurController();
        $id = $m4Id($_GET, 'id');
        $fournisseur = null;
        if ($id !== null && ($fournisseur = $ctrl->find($id)) === null) {
            // Un id fourni mais introuvable donne « introuvable », jamais un ajout
            AuthController::flash('error', 'Fournisseur introuvable.');
            header('Location: ' . url('admin-fournisseurs'));
            exit;
        }
        $errors = [];
        $old = $fournisseur === null
            ? array_fill_keys(M4_FOURNISSEUR_FIELDS, '')
            : [
                'prenom'           => $fournisseur->getPrenom(),
                'nom'              => $fournisseur->getNom(),
                'email'            => $fournisseur->getEmail(),
                'telephone'        => (string) $fournisseur->getTelephone(),
                'id_zone'          => (string) ($fournisseur->getIdZone() ?? ''),
                'adresse'          => (string) $fournisseur->getAdresse(),
                'nom_organisation' => (string) $fournisseur->getNomOrganisation(),
                'specialite'       => (string) $fournisseur->getSpecialite(),
                'description'      => (string) $fournisseur->getDescription(),
            ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $fournisseur === null ? $ctrl->adminCreate($m4Post()) : $ctrl->adminUpdate($fournisseur, $m4Post());
            if ($errors === []) {
                AuthController::flash('success', $fournisseur === null
                    ? 'Fournisseur créé. Il reste non vérifié tant que vous n\'avez pas cliqué « Vérifier ».'
                    : 'Fournisseur modifié.');
                header('Location: ' . ($fournisseur === null ? url('admin-fournisseurs') : url('admin-fournisseur-voir', ['id' => $fournisseur->getIdUtilisateur()])));
                exit;
            }
            $old = $m4Old(M4_FOURNISSEUR_FIELDS);
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/backoffice/pages/fournisseurs/form.php';
    },

    'admin-fournisseur-verifier' => function () use ($m4RequirePost, $m4Id, $m4Txt, $m4Back): void {
        AuthController::requireRole(['admin']);
        $m4RequirePost('admin-fournisseurs');
        $verifie = $m4Txt($_POST, 'verifie') === '1';
        if ((new FournisseurController())->setVerifie((int) $m4Id($_POST, 'id'), $verifie)) {
            AuthController::flash('success', $verifie
                ? 'Fournisseur vérifié : il apparaît dans l\'annuaire et peut répondre aux demandes.'
                : 'Vérification retirée : le fournisseur n\'apparaît plus dans l\'annuaire.');
        } else {
            AuthController::flash('error', 'Fournisseur introuvable.');
        }
        $m4Back('admin-fournisseurs');
    },

    'admin-fournisseur-statut' => function () use ($m4RequirePost, $m4Id, $m4Txt, $m4Back): void {
        AuthController::requireRole(['admin']);
        $m4RequirePost('admin-fournisseurs');
        $statut = $m4Txt($_POST, 'statut');
        if ((new FournisseurController())->setStatut((int) $m4Id($_POST, 'id'), $statut)) {
            AuthController::flash('success', $statut === 'actif' ? 'Compte réactivé.' : 'Compte suspendu : il ne peut plus se connecter.');
        } else {
            AuthController::flash('error', 'Fournisseur introuvable ou statut invalide.');
        }
        $m4Back('admin-fournisseurs');
    },

    'admin-fournisseur-supprimer' => function () use ($m4RequirePost, $m4Id, $m4Back): void {
        AuthController::requireRole(['admin']);
        $m4RequirePost('admin-fournisseurs');
        $error = (new FournisseurController())->delete((int) $m4Id($_POST, 'id'));
        if ($error !== null) {
            AuthController::flash('error', $error);
            $m4Back('admin-fournisseurs');
        }
        AuthController::flash('success', 'Fournisseur supprimé.');
        header('Location: ' . url('admin-fournisseurs'));
        exit;
    },

    // ==================================================================
    // DEMANDES D'ASSISTANCE — FRONT OFFICE
    // ==================================================================

    'relais' => function () use ($m4Old, $m4Post, $m4Filters, $m4HandleReponse, $m4Txt): void {
        $ctrl = new DemandeAssistanceController();
        $errors = [];
        $old = ['sens' => 'demande', 'categorie' => '', 'titre' => '', 'description' => '', 'id_zone' => '',
                'quartier' => '', 'urgence' => '', 'creneau' => '', 'regles' => ''];
        $openModal = null;
        $replyErrors = [];
        $replyTo = 0;
        $replyOld = ['message' => '', 'id_installation' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($m4Txt($_POST, 'form') === 'reponse') {
                [$replyErrors, $replyTo, $replyOld] = $m4HandleReponse($ctrl, 'relais');
            } else {
                AuthController::requireRole(DemandeAssistanceController::FRONT_ROLES);
                $errors = $ctrl->createRelais($m4Post(), AuthController::currentUser());
                if ($errors === []) {
                    AuthController::flash('success', 'Votre publication est en ligne.');
                    header('Location: ' . url('relais', ['tab' => 'mine']));
                    exit;
                }
                $old = $m4Old(array_keys($old));
                $openModal = 'post-modal';
            }
        }

        $user = AuthController::currentUser();
        $filters = $m4Filters(['tab', 'categorie', 'id_zone', 'q', 'resolues', 'tri']);
        if ($filters['tab'] === 'mine' && $user === null) {
            $filters['tab'] = 'all';
        }
        if (!ctype_digit($filters['id_zone'])) {
            $filters['id_zone'] = '';
        }
        $demandes = $ctrl->getRelais($filters, $user?->getIdUtilisateur());
        $reponses = $ctrl->getReponsesFor(array_map(fn ($d) => $d->getIdDemande(), $demandes));
        $counts = $ctrl->countRelais($user?->getIdUtilisateur());
        $repondues = $user ? $ctrl->getDemandesRepondues($user->getIdUtilisateur()) : [];
        $installations = $user && $user->getRole() === 'proprietaire' ? $ctrl->getInstallations($user->getIdUtilisateur()) : [];
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/assistance/relais.php';
    },

    'fournisseurs' => function () use ($m4Old, $m4Post, $m4Filters, $m4HandleReponse, $m4Txt, $m4Id): void {
        $ctrl = new DemandeAssistanceController();
        $comptes = new FournisseurController();
        $errors = [];
        $pro = $m4Id($_GET, 'pro');
        $old = ['id_fournisseur' => $pro ? (string) $pro : '', 'categorie' => '', 'titre' => '', 'urgence' => '',
                'description' => '', 'id_zone' => '', 'quartier' => '', 'date_souhaitee' => '', 'creneau' => '',
                'telephone' => '', 'consentement' => ''];
        $openModal = $pro ? 'request-modal' : null;
        $replyErrors = [];
        $replyTo = 0;
        $replyOld = ['message' => '', 'id_installation' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($m4Txt($_POST, 'form') === 'reponse') {
                [$replyErrors, $replyTo, $replyOld] = $m4HandleReponse($ctrl, 'fournisseurs');
            } else {
                AuthController::requireRole(DemandeAssistanceController::FRONT_ROLES);
                $errors = $ctrl->createDemandeFournisseur($m4Post(), AuthController::currentUser());
                if ($errors === []) {
                    AuthController::flash('success', "Votre demande d'intervention a été envoyée au professionnel.");
                    header('Location: ' . url('fournisseurs', [], 'mes-demandes'));
                    exit;
                }
                $old = $m4Old(array_keys($old));
                $openModal = 'request-modal';
            }
        }

        $user = AuthController::currentUser();
        if ($user !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $old['telephone'] = (string) $user->getTelephone();
            $old['id_zone'] = (string) ($user->getIdZone() ?? '');
        }
        $filters = $m4Filters(['specialite', 'id_zone', 'q', 'tri']);
        $fournisseurs = $comptes->getPublics($filters);
        $tousFournisseurs = $comptes->getPublics();
        $parSpecialite = $comptes->countPublicsParSpecialite();
        $mesDemandes = $user ? $ctrl->getEnvoyeesAuxFournisseurs($user->getIdUtilisateur()) : [];
        $recues = $user && $user->getRole() === 'fournisseur' ? $ctrl->getRecuesParFournisseur($user->getIdUtilisateur()) : [];
        $reponses = $ctrl->getReponsesFor(array_map(fn ($d) => $d->getIdDemande(), array_merge($mesDemandes, $recues)));
        $repondues = $user ? $ctrl->getDemandesRepondues($user->getIdUtilisateur()) : [];
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/assistance/fournisseurs.php';
    },

    'demande-modifier' => function () use ($m4Old, $m4Post, $m4Id): void {
        AuthController::requireRole(DemandeAssistanceController::FRONT_ROLES);
        $user = AuthController::currentUser();
        $ctrl = new DemandeAssistanceController();
        $demande = $ctrl->findById((int) $m4Id($_GET, 'id'));
        if ($demande === null || !$ctrl->isOwner($demande, $user)) {
            AuthController::flash('error', 'Demande introuvable.');
            header('Location: ' . url('relais'));
            exit;
        }
        $retour = $demande->getIdFournisseur() === null ? 'relais' : 'fournisseurs';
        if (!$ctrl->canEdit($demande, $user)) {
            AuthController::flash('info', 'Seules les demandes encore ouvertes peuvent être modifiées.');
            header('Location: ' . url($retour));
            exit;
        }

        $errors = [];
        $old = [
            'sens'           => $demande->getSens(),
            'categorie'      => $demande->getCategorie(),
            'titre'          => $demande->getTitre(),
            'description'    => $demande->getDescription(),
            'id_zone'        => (string) $demande->getIdZone(),
            'quartier'       => (string) $demande->getQuartier(),
            'urgence'        => $demande->getUrgence(),
            'creneau'        => (string) $demande->getCreneau(),
            'date_souhaitee' => (string) $demande->getDateSouhaitee(),
            'telephone'      => (string) $demande->getTelephone(),
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $ctrl->update($demande, $m4Post(), $user);
            if ($errors === []) {
                AuthController::flash('success', 'Votre demande a été modifiée.');
                header('Location: ' . ($retour === 'relais' ? url('relais', ['tab' => 'mine']) : url('fournisseurs', [], 'mes-demandes')));
                exit;
            }
            $old = $m4Old(array_keys($old));
        }
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/frontoffice/pages/assistance/demande-form.php';
    },

    'demande-statut' => function () use ($m4Back, $m4RequirePost, $m4Id, $m4Txt): void {
        AuthController::requireRole(M4_DEMANDE_ROLES);
        $m4RequirePost('relais');
        $ctrl = new DemandeAssistanceController();
        $demande = $ctrl->findById((int) $m4Id($_POST, 'id'));
        $statut = $m4Txt($_POST, 'statut');
        if ($demande === null) {
            AuthController::flash('error', 'Demande introuvable.');
        } elseif (($error = $ctrl->changeStatut($demande, $statut, AuthController::currentUser())) !== null) {
            AuthController::flash('error', $error);
        } else {
            AuthController::flash('success', 'Statut mis à jour : ' . DemandeAssistanceModel::STATUTS[$statut] . '.');
        }
        $m4Back('relais');
    },

    'demande-supprimer' => function () use ($m4Back, $m4RequirePost, $m4Id, $m4Txt): void {
        AuthController::requireRole(M4_DEMANDE_ROLES);
        $m4RequirePost('relais');
        $ctrl = new DemandeAssistanceController();
        $demande = $ctrl->findById((int) $m4Id($_POST, 'id'));
        if ($demande !== null && $ctrl->delete($demande, AuthController::currentUser())) {
            AuthController::flash('success', 'La demande a été supprimée.');
            if ($m4Txt($_POST, 'retour') === 'admin-demande') {
                $_POST['retour'] = 'admin-demandes';
            }
        } else {
            AuthController::flash('error', 'Demande introuvable ou suppression non autorisée.');
        }
        $m4Back('relais');
    },

    'reponse-statut' => function () use ($m4Back, $m4RequirePost, $m4Id, $m4Txt): void {
        AuthController::requireRole(DemandeAssistanceController::FRONT_ROLES);
        $m4RequirePost('relais');
        $ctrl = new DemandeAssistanceController();
        $reponse = $ctrl->findReponse((int) $m4Id($_POST, 'id_reponse'));
        $statut = $m4Txt($_POST, 'statut');
        if ($reponse === null) {
            AuthController::flash('error', 'Réponse introuvable.');
        } elseif (($error = $ctrl->changeReponseStatut($reponse, $statut, AuthController::currentUser())) !== null) {
            AuthController::flash('error', $error);
        } else {
            AuthController::flash('success', $statut === 'acceptee' ? 'Réponse acceptée : la demande est prise en charge.' : 'Réponse refusée.');
        }
        $m4Back('relais');
    },

    'reponse-supprimer' => function () use ($m4Back, $m4RequirePost, $m4Id): void {
        AuthController::requireRole(M4_DEMANDE_ROLES);
        $m4RequirePost('relais');
        $ctrl = new DemandeAssistanceController();
        $reponse = $ctrl->findReponse((int) $m4Id($_POST, 'id_reponse'));
        if ($reponse === null) {
            AuthController::flash('error', 'Réponse introuvable.');
        } elseif (($error = $ctrl->deleteReponse($reponse, AuthController::currentUser())) !== null) {
            AuthController::flash('error', $error);
        } else {
            AuthController::flash('success', 'La réponse a été supprimée.');
        }
        $m4Back('relais');
    },

    // Priorité santé : chaque enregistrement (POST, appelé en fetch par sante.js) crée une demande d'assistance
    // de catégorie « sante », traitée dans le Back Office (Assistance › Demandes d'assistance).
    'sante' => function () use ($m4Post, $m4Id): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            $json = function (int $code, array $body): void {
                http_response_code($code);
                echo json_encode($body, JSON_UNESCAPED_UNICODE);
                exit;
            };
            $user = AuthController::currentUser();
            if ($user === null) {
                $json(401, ['ok' => false, 'message' => 'Connectez-vous pour enregistrer une personne prioritaire.', 'login' => url('connexion')]);
            }
            $ctrl = new DemandeAssistanceController();
            $existing = null;
            $id = $m4Id($_POST, 'id_demande');
            if ($id !== null && $id !== 0 && ($existing = $ctrl->findById($id)) === null) {
                $json(404, ['ok' => false, 'message' => 'Demande introuvable.']);
            }
            $result = $ctrl->saveSante($m4Post(), $user, $existing);
            if ($result['errors'] !== []) {
                $form = $result['errors']['form'] ?? null;
                unset($result['errors']['form']);
                $json(422, ['ok' => false, 'message' => $form ?? 'Certains champs sont invalides.', 'errors' => $result['errors']]);
            }
            $json(200, ['ok' => true, 'id' => $result['id']]);
        }
        require __DIR__ . '/../views/frontoffice/pages/assistance/sante.php';
    },

    // ==================================================================
    // DEMANDES D'ASSISTANCE — BACK OFFICE (admin)
    // ==================================================================

    'admin-demandes' => function () use ($m4Filters): void {
        AuthController::requireRole(['admin']);
        $ctrl = new DemandeAssistanceController();
        $filters = $m4Filters(['type', 'statut', 'categorie', 'id_zone', 'q']);
        if (!ctype_digit($filters['id_zone'])) {
            $filters['id_zone'] = '';
        }
        $demandes = $ctrl->getAll($filters);
        $countByStatut = $ctrl->countByStatut();
        $gouvernorats = (new ZoneController())->getGouvernorats();
        require __DIR__ . '/../views/backoffice/pages/assistance/demandes.php';
    },

    'admin-demande' => function () use ($m4Id): void {
        AuthController::requireRole(['admin']);
        $ctrl = new DemandeAssistanceController();
        $demande = $ctrl->findById((int) $m4Id($_GET, 'id'));
        if ($demande === null) {
            AuthController::flash('error', 'Demande introuvable.');
            header('Location: ' . url('admin-demandes'));
            exit;
        }
        $reponses = $ctrl->getReponses($demande->getIdDemande());
        $auteur = (new AuthController())->findById($demande->getIdUtilisateur());
        $statutsPossibles = $ctrl->allowedStatuts($demande, AuthController::currentUser());
        require __DIR__ . '/../views/backoffice/pages/assistance/demande.php';
    },
];
