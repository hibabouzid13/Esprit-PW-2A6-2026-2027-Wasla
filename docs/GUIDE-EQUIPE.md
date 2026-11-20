# WASLA — Guide de travail de l'équipe

## 1. Les branches

```text
main                      ← base commune (ne JAMAIS travailler directement dessus)
 ├── module1-interruptions   Houssem
 ├── module2-factures        Hiba
 ├── module3-refuges         Louay
 └── module4-assistance      Bechir
```

Créer sa branche (une seule fois) :

```bash
git checkout main
git pull origin main
git checkout -b module2-factures
git push -u origin module2-factures
```

## 2. Quels fichiers chaque membre peut modifier

| Fichier / dossier | Qui |
|---|---|
| `routes/moduleN.php` | uniquement le responsable du module N |
| `controllers/<Entite>Controller.php`, `models/<Entite>Model.php` | le responsable de l'entité |
| `views/frontoffice/pages/<module>/`, `views/backoffice/pages/<module>/` | le responsable du module |
| `views/backoffice/partials/sidebar.php` | chacun **uniquement le bloc de son module** |
| `index.php`, `config/`, `Controller.php`, `AuthController.php`, layouts, `validation.js`, `database/wasla.sql` | **personne dans sa branche** : une modification commune passe par une pull request dédiée vers `main`, validée par l'équipe |

## 3. La base de données

**Un seul membre crée la base** (consigne du professeur) : le script complet est `database/wasla.sql`, dans `main`.
Les autres l'importent tel quel. Personne ne modifie ce fichier dans sa branche : un changement de structure
(nouvelle colonne, nouvelle table) se fait par une pull request vers `main`, validée par l'équipe, puis chacun
supprime sa base `wasla` et réimporte le fichier.

L'application accède à la base **uniquement par PDO** : `Database::getConnexion()` dans `config/database.php`.

**Entreprise et fournisseur sont des rôles de `utilisateur`, pas des tables.** Une refuge zone pointe vers
l'utilisateur de rôle `entreprise` (`refuge_zone.id_entreprise`), une demande adressée à un artisan pointe vers
l'utilisateur de rôle `fournisseur` (`demande_assistance.id_fournisseur`). Le contrôleur vérifie le rôle avant
d'enregistrer (la base ne peut pas le vérifier seule).

Sessions et sécurité : voir `docs/SECURITE.md`.

## 4. Ajouter une entité (exemple : Facture, Module 2)

1. **SQL** : la table `facture` existe déjà dans `database/wasla.sql` (base unique partagée). Si elle doit changer, la modification passe par une pull request vers `main`, puis chacun réimporte le fichier.
2. **Model** : `models/FactureModel.php` contient uniquement des attributs privés, des getters/setters et `fromArray()`. Pas de SQL.
3. **Controller** : `controllers/FactureController.php extends Controller`. Il contient les requêtes préparées (`$this->db->prepare(...)`), la validation (`$this->validate(...)`) et la vérification que la facture appartient bien à l'utilisateur connecté.
4. **Vues** : `views/frontoffice/pages/factures/liste.php`, `form.php`… Chaque vue suit ce modèle :

   ```php
   <?php ob_start(); ?>
     … HTML, chaque donnée affichée avec e(...) …
   <?php
   $content = ob_get_clean();
   $title = 'Mes factures — WASLA';
   $currentPage = 'factures';
   require __DIR__ . '/../../layout.php';
   ```

5. **Routes**, dans `routes/module2.php` :

   ```php
   'factures' => function (): void {
       AuthController::requireRole(['citoyen']);
       $factures = (new FactureController())->getByUser(AuthController::currentUser()->getIdUtilisateur());
       require __DIR__ . '/../views/frontoffice/pages/factures/liste.php';
   },
   ```

## 5. Règles obligatoires pour chaque formulaire

- **Aucune validation HTML5** : pas de `required`, `type="email"`, `type="number"`, `min`, `max`, `pattern`, `maxlength`.
- Le formulaire porte l'attribut `data-validate` ; chaque champ porte `data-rules="..."` et a sa zone d'erreur `<?= fieldError($errors, 'nom_du_champ') ?>`.
- **Les mêmes règles** sont revérifiées en PHP avec `$this->validate($_POST, [...])`.
- Chaque formulaire `POST` contient `<?= csrfField() ?>` (sinon `index.php` refuse l'envoi).
- Suppression : formulaire `POST` avec `data-confirm="Supprimer cette facture ?"`, jamais un simple lien.
- Toute action sensible commence par `AuthController::requireAuth()` ou `AuthController::requireRole([...])`.

Règles disponibles (JS et PHP) : `required`, `email`, `name`, `phone`, `password`, `min:N`, `max:N`, `match:champ`, `in:a,b,c`, `int`, `decimal`, `positive`, `date`, `after:champ`, `accepted`.

**Pages « Services » reprises de l'ancien dépôt** (formulaires gérés en JavaScript par `services/core.js`) : elles utilisent leurs propres attributs, eux aussi sans validation HTML5 :

| Attribut | Rôle |
|---|---|
| `data-required="message"` | champ obligatoire |
| `data-min="20"` / `data-max="500"` | longueur minimale / maximale du texte (remplace `maxlength`) |
| `data-pattern="phone"`, `"email"`, `"name"` | format attendu (remplace `type="tel"`, `type="email"`, `pattern`) |
| `data-num-min="1"` / `data-num-max="99999"` + `data-integer` | nombre saisi dans un champ texte (remplace `type="number"`, `min`, `max`, `step`) |
| `data-not-past` | date non passée |

Quand une page passe de la démonstration à la base de données, son formulaire devient un formulaire `POST` avec `data-validate`, des `data-rules`, `csrfField()` et la même validation en PHP.

## 6. Commits

Format : `type(portée): message`, par exemple :

- `feat(module2): CRUD des factures (liste, ajout, modification, suppression)`
- `fix(module2): contrôle du montant côté PHP`
- `docs: mise à jour du README`

Faites un commit par étape qui fonctionne, et poussez souvent : chaque contribution doit être visible sur GitHub.

## 7. Intégrer son travail dans main

```bash
git checkout module2-factures
git pull origin main          # récupérer les nouveautés de main AVANT d'ouvrir la PR
# résoudre les conflits éventuels, tester, puis :
git push
```

Ensuite, ouvrir une **pull request** `module2-factures → main` sur GitHub, la faire relire par un autre membre, puis la fusionner (*merge*).

## 8. GitHub Project

Chaque tâche est une carte : **assignée** à son responsable, avec un **label** de module (`module1`…`module4`, `commun`) et un statut **Todo → In progress → Done**. La carte est liée au commit ou à la pull request qui la termine.

## 9. Avant la validation (une seule machine)

1. `git checkout main` puis `git pull`.
2. Supprimer la base `wasla`, la recréer, puis réimporter `database/wasla.sql`.
3. Tester les 4 comptes de démonstration et un parcours complet par module.
