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

return [
];
