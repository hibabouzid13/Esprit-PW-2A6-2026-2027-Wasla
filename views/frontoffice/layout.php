<?php
/**
 * views/frontoffice/layout.php — habillage commun du Front Office.
 *
 * Chaque vue prépare son HTML avec ob_start(), le met dans $content avec ob_get_clean(),
 * définit ses variables puis inclut ce layout (méthode du workshop, partie 3.4) :
 *
 *   $content      HTML de la page (obligatoire)
 *   $title        titre de l'onglet
 *   $description  meta description
 *   $currentPage  clé de la page courante (lien actif de la navbar)
 *   $pageCss      feuilles de style propres à la page (ex. ['pages'])
 *   $pageScripts  scripts propres à la page (ex. ['js/data.js', 'js/layout.js'])
 *   $extraHead    balises supplémentaires dans <head>
 */
$title       = $title ?? 'WASLA — Connecter. Anticiper. Protéger.';
$description = $description ?? "WASLA : surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.";
$currentPage = $currentPage ?? '';
$pageCss     = $pageCss ?? ['pages'];
$pageScripts = $pageScripts ?? ['js/data.js', 'js/icons.js', 'js/layout.js', 'js/pages.js'];
$extraHead   = $extraHead ?? '';

// Avant tout affichage : la déconnexion d'une session expirée ou révoquée écrit un cookie
AuthController::currentUser();

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/spinner.php';
require __DIR__ . '/partials/navbar.php';

echo $content;

require __DIR__ . '/partials/footer.php';
require __DIR__ . '/partials/scripts.php';
