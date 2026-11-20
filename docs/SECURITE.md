# WASLA — Sessions et sécurité

Ce document montre où chaque point du workshop **« Sessions et Sécurité »** est appliqué dans WASLA,
comment le tester pendant la validation, et répond aux questions de réflexion du workshop.

---

## 1. Le cycle de vie d'une session

```text
1. Visiteur        index.php → session_start()           PHP crée une session anonyme
                   (cookie PHPSESSID envoyé au navigateur ; données vides sur le serveur)
2. Connexion       AuthController::login()
                   password_verify() OK → session_regenerate_id(true)   ← NOUVEL identifiant
                   $_SESSION['user_id'], ['prenom'], ['role'], ['login_time'], ['last_activity']
3. Chaque page     index.php : expiration après 30 min sans activité, sinon last_activity = now
                   AuthController::currentUser() relit le compte EN BASE (rôle et statut à jour)
4. Déconnexion     AuthController::logout() : $_SESSION = [], cookie supprimé, session_destroy()
```

**Où sont stockées les informations ?**

| Côté | Ce qui est stocké | Où le voir |
|---|---|---|
| Serveur | Les vraies données : `user_id`, `prenom`, `role`, `login_time`, `last_activity`, `csrf_token`… | Fichier de session de PHP (`sess_<identifiant>` dans le dossier `tmp` de XAMPP/Laragon) |
| Navigateur | **Uniquement l'identifiant de session**, dans le cookie `PHPSESSID` | F12 → **Application** (ou *Stockage*) → **Cookies** → `http://localhost` |

Le navigateur ne connaît jamais le contenu de la session. L'identifiant est dans un **cookie** et non dans le
`localStorage` : un cookie `HttpOnly` est invisible pour JavaScript, alors que tout ce qui est dans le
`localStorage` peut être lu par n'importe quel script de la page (donc volé par une faille XSS).
WASLA n'utilise le `localStorage` que pour des préférences sans risque : thème clair/sombre, langue, données de démonstration.

La gestion des sessions est entièrement dans le code : rien n'est affiché à l'utilisateur. Pour la montrer
pendant la validation, on utilise les DevTools (cookie `PHPSESSID`) et le fichier de session côté serveur (voir les tests ci-dessous).

Le Front Office et le Back Office partagent **la même session** (même `index.php`). Ce qui change, c'est le contrôle
d'accès : `AuthController::requireRole(AuthController::BACKOFFICE_ROLES)` sur chaque page du Back Office.

---

## 2. Correspondance avec le workshop

| Partie du workshop | Dans WASLA | Fichier |
|---|---|---|
| 2.2 Connexion PDO, Singleton | `Database::getConnexion()`, `ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false` | `config/database.php` |
| 3.1 Model sans logique | `UserModel` : attributs privés, getters/setters, `fromArray()` | `models/UserModel.php` |
| 3.2 Contrôleur d'authentification | `AuthController` (tout le SQL, la validation, les sessions) | `controllers/AuthController.php` |
| 3.3 Point d'entrée unique | `index.php?action=…` | `index.php`, `routes/*.php` |
| 3.4 Layouts | `$content` via `ob_start()` puis layout | `views/frontoffice/layout.php`, `views/backoffice/layout.php` |
| 4 Inscription + hachage | `register()` : validation, rôle forcé `citoyen`, `password_hash()` | `AuthController::register()` |
| 5.1 Connexion / déconnexion | `login()` : `password_verify()`, `session_regenerate_id(true)` ; `logout()` | `AuthController` |
| 5.3 Espace protégé | `requireAuth()` puis *Mon espace* | `routes/common.php` (`mon-compte`) |
| 5.5 Configuration de la session | `use_strict_mode`, `use_only_cookies`, `cookie_httponly`, `cookie_samesite=Lax`, **avant** `session_start()` | `index.php` (bloc 1) |
| 5.5 Expiration | 30 min d'inactivité (`SESSION_LIFETIME`) | `index.php` (bloc 2) |
| 6.1 Contrôle des rôles | `requireRole([...])`, `hasRole()`, 403 sinon | `AuthController` |
| 6.2 / 6.3 Back Office | Tableau de bord : statistiques, **tous les comptes**, rôle de chacun | `views/backoffice/pages/dashboard.php` |
| 7.1 Hachage partout | Toute création de compte passe par `password_hash()` | `AuthController::register()` (et les CRUD des modules) |
| 7.2 Injection SQL | Requêtes préparées uniquement | tous les contrôleurs |
| 7.3 XSS | `e()` = `htmlspecialchars(…, ENT_QUOTES, 'UTF-8')` sur chaque sortie + validation des entrées | vues, `config/helpers.php` |
| 7.4 CSRF | Jeton par session (`random_bytes(32)`), champ caché `csrfField()`, vérifié avec `hash_equals()` dans `register()` / `login()` **et** pour tout POST dans `index.php` | `AuthController`, `index.php` |
| 7.5 En-têtes HTTP | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy` | `index.php` (bloc 3) |

**En plus du workshop :** blocage de 60 s après 5 mots de passe erronés ; un compte suspendu ou supprimé
est déconnecté à la requête suivante ; la déconnexion se fait par un formulaire POST avec jeton (un simple lien
pourrait être déclenché par un autre site) ; validation JavaScript **et** PHP de chaque formulaire, sans validation HTML5.

---

## 3. Tests à montrer pendant la validation

| Test (workshop) | Comment | Résultat attendu |
|---|---|---|
| Hachage (4) | phpMyAdmin → table `utilisateur` → colonne `mot_de_passe` | Valeurs commençant par `$2y$` |
| Connexion (5.4) | `citoyen@wasla.tn` / `Citoyen@2026` | Arrivée sur *Mon espace* |
| Mauvais mot de passe (5.4) | Bon e-mail, mauvais mot de passe | « Adresse e-mail ou mot de passe incorrect. » |
| Pas de session (5.4) | Déconnecté, taper `index.php?action=mon-compte` | Renvoi vers la connexion |
| Cookie (5.5) | F12 → Application → Cookies → `PHPSESSID` | Coche **HttpOnly** ; la valeur change après déconnexion/reconnexion |
| Données côté serveur (5.5) | Ouvrir le fichier `sess_<valeur du cookie>` dans le dossier `tmp` de XAMPP/Laragon | Contient `user_id`, `role`, `login_time`, `last_activity`, `csrf_token` |
| Déconnexion (5.5) | Se déconnecter, bouton Précédent, recharger | Retour à la connexion |
| Back Office (6.4) | `admin@wasla.tn` / `Admin@2026` | Tableau de bord avec tous les comptes |
| Accès refusé (6.4) | En citoyen, taper `index.php?action=dashboard` | **403 Accès refusé** |
| Injection SQL (7.2) | E-mail : `' OR '1'='1` | Connexion refusée |
| XSS (7.3) | Insérer un nom `<script>alert('test')</script>` dans phpMyAdmin, puis ouvrir *Mon espace* | Le texte s'affiche tel quel, aucune fenêtre |
| CSRF (7.4) | F12 → modifier la valeur du champ caché `csrf_token`, puis envoyer | « Jeton de sécurité invalide ou expiré. » |
| En-têtes (7.5) | F12 → Réseau → la page → En-têtes de réponse | Les 4 en-têtes de sécurité sont présents |
| Expiration (5.5) | Rester 30 min sans activité, puis cliquer | « Votre session a expiré… » et renvoi vers la connexion |

Compte piégé pour le test XSS (onglet SQL de phpMyAdmin ; mot de passe `Citoyen@2026`) :

```sql
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role)
VALUES ('Test', '<script>alert(''test'')</script>', 'xss@wasla.tn',
        '$2y$10$Hhg413MYiGCVp2nV5GTRe.xEFvkuYoJgOpi/s/z6aa4kIOyaLQU1i', 'citoyen');
```

---

## 4. Réponses aux questions de réflexion

**1. Pourquoi vérifier le rôle seulement à la connexion serait-il dangereux ?**
Les pages sensibles s'ouvrent aussi en tapant directement leur adresse. Si le rôle n'est vérifié qu'à la connexion, n'importe quel utilisateur connecté peut taper `index.php?action=dashboard`. De plus, un admin rétrogradé ou un compte suspendu garderait ses droits jusqu'à sa déconnexion. WASLA appelle `requireRole()` au début de chaque action sensible et relit le rôle en base à chaque requête.

**2. Quel fichier serait le plus tenté de mélanger logique et affichage ? Pourquoi est-ce risqué ?**
Les vues (et `index.php`). Une vue qui exécute du SQL ou décide des droits devient difficile à relire et à tester ; on y oublie facilement une requête préparée ou un contrôle d'accès, et la même logique finit copiée dans plusieurs pages. Dans WASLA, les vues ne font qu'afficher ; le SQL et les contrôles sont dans les contrôleurs.

**3. Pourquoi `password_hash()` plutôt que `md5()` ou `sha1()` ?**
`md5()` et `sha1()` sont conçus pour être très rapides et n'ajoutent pas de sel : un attaquant teste des milliards de mots de passe par seconde, et deux mots de passe identiques donnent le même hachage (tables précalculées). `password_hash()` utilise bcrypt, lent volontairement, avec un sel aléatoire différent pour chaque compte, et `password_verify()` compare de façon sûre.

**4. Que se passerait-il sans `session_regenerate_id(true)` après la connexion ?**
L'identifiant de session resterait celui d'avant la connexion. Un attaquant qui a réussi à imposer cet identifiant à la victime (fixation de session) se retrouverait connecté à son compte dès qu'elle se connecte. Le nouvel identifiant rend l'ancien inutilisable.

**5. Pourquoi les `ini_set()` doivent-elles être placées avant `session_start()` ?**
`session_start()` crée la session et envoie le cookie avec les réglages en vigueur à ce moment-là. Après, il est trop tard : le cookie est déjà parti sans `HttpOnly` / `SameSite`, et PHP signale que les réglages de session ne peuvent plus être modifiés.

**6. Si un attaquant copiait la valeur du cookie `PHPSESSID` dans son navigateur ?**
Il serait connecté au compte de la victime (vol de session) : le serveur ne reconnaît une session que par cet identifiant. C'est pourquoi le cookie est `HttpOnly` (un script XSS ne peut pas le lire), `SameSite=Lax`, que la session expire après 30 minutes d'inactivité, et que la déconnexion la détruit. En production, on ajoute HTTPS et `cookie_secure` pour qu'il ne circule jamais en clair.

**7. Si on supprimait `requireRole('admin')` en gardant le lien caché aux non-admins ?**
Oui, un utilisateur normal pourrait y accéder : cacher un lien n'est pas une protection. Il suffit de taper l'adresse (`index.php?action=dashboard`), de la deviner ou de la retrouver dans l'historique. Le contrôle d'accès doit toujours être fait côté serveur, dans le code de la page.
