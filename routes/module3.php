<?php
/**
 * routes/module3.php — Module 3 : Refuge Zones (Louay)
 *
 * Seul Louay modifie ce fichier (dans la branche module3-refuges).
 * Ajoutez une entrée par action. Exemple :
 *
 *   'refuges' => function (): void {
 *       AuthController::requireRole(['citoyen']);  // contrôle d'accès côté serveur
 *       $items = (new RefugeZoneController())->getValidated();  // le contrôleur fait le SQL
 *       require __DIR__ . '/../views/frontoffice/pages/refuges/liste.php';  // la vue affiche
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
