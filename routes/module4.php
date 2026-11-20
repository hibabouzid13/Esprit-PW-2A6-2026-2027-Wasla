<?php
/**
 * routes/module4.php — Module 4 : Assistance d'urgence, solaire & fournisseurs (Bechir)
 *
 * Seul Bechir modifie ce fichier (dans la branche module4-assistance).
 * Ajoutez une entrée par action. Exemple :
 *
 *   'fournisseurs' => function (): void {
 *       AuthController::requireRole(['citoyen']);  // contrôle d'accès côté serveur
 *       $items = (new FournisseurController())->getAll();  // le contrôleur fait le SQL
 *       require __DIR__ . '/../views/frontoffice/pages/assistance/liste.php';  // la vue affiche
 *   },
 *
 * Règles :
 *   - pages Back Office : clé qui commence par « admin- » + requireRole(...)
 *   - formulaire POST : la vue contient <?= csrfField() ?> (index.php vérifie le jeton)
 *   - clés uniques dans tout le projet (index.php signale les doublons)
 */

declare(strict_types=1);

return [
];
