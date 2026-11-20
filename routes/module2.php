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
];
