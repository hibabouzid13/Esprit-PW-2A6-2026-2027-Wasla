<?php
/**
 * config/helpers.php
 * Petites fonctions d'AFFICHAGE utilisées par les vues (aucun accès à la base).
 */

declare(strict_types=1);

/** Pages du menu « Services » : le lien Services de la navbar reste actif sur chacune d'elles. */
const SERVICE_PAGES = ['services', 'alertes', 'factures', 'fournisseurs', 'refuges', 'relais', 'sante'];

/** Échappe un texte avant de l'afficher (protection XSS). À utiliser pour TOUTE donnée affichée. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Lien vers une action de l'application.
 *   url('factures')                        → index.php?action=factures
 *   url('facture-modifier', ['id' => 4])   → index.php?action=facture-modifier&id=4
 *   url('a-propos', [], 'engagements')     → index.php?action=a-propos#engagements
 */
function url(string $action, array $params = [], string $anchor = ''): string
{
    $query = http_build_query(['action' => $action] + $params);
    return 'index.php?' . $query . ($anchor !== '' ? '#' . rawurlencode($anchor) : '');
}

/** Chemin public d'un fichier d'assets. asset('css/tokens.css') → views/frontoffice/assets/css/tokens.css */
function asset(string $path, string $office = 'frontoffice'): string
{
    return 'views/' . $office . '/assets/' . ltrim($path, '/');
}

/** Toutes les actions déclarées dans routes/*.php (chargées par index.php). */
function routes(): array
{
    return $GLOBALS['WASLA_ROUTES'] ?? [];
}

/** true si l'action existe (sert à n'activer un lien que lorsque la page existe vraiment). */
function routeExists(string $action): bool
{
    return isset(routes()[$action]);
}

/** Lien vers une action si elle existe, sinon « # » (page pas encore créée par son module). */
function pageUrl(string $action, array $params = [], string $anchor = ''): string
{
    return routeExists($action) ? url($action, $params, $anchor) : '#';
}

/** Classe CSS « active » si $page est la page courante. */
function isActive(string $page, string $currentPage, string $class = 'is-active'): string
{
    return $page === $currentPage ? $class : '';
}

/** Attribut aria-current si $page est la page courante (accessibilité). */
function ariaCurrent(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'aria-current="page"' : '';
}

/** Classe « is-open » si la page courante fait partie du groupe (sidebar du Back Office). */
function isOpen(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-open' : '';
}

/** Champ caché du jeton CSRF : à mettre dans CHAQUE formulaire POST. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(AuthController::getCsrfToken()) . '">';
}

/** Libellé lisible d'un rôle. */
function roleLabel(string $role): string
{
    return [
        'admin'         => 'Super Admin',
        'steg'          => 'Sous-admin STEG',
        'sonede'        => 'Sous-admin SONEDE',
        'citoyen'       => 'Citoyen',
        'entreprise'    => 'Entreprise',
        'fournisseur'   => 'Fournisseur',
        'benevole'      => 'Bénévole',
        'proprietaire'  => 'Propriétaire solaire',
    ][$role] ?? ucfirst($role);
}

/** Libellé lisible d'une spécialité de fournisseur. */
function specialiteLabel(?string $specialite): string
{
    return [
        'electricien' => 'Électricien',
        'plombier'    => 'Plombier',
        'maintenance' => 'Technicien de maintenance',
        'solaire'     => 'Installateur solaire',
        'hydraulique' => 'Spécialiste hydraulique',
    ][$specialite ?? ''] ?? '—';
}

/** Initiales pour l'avatar (ex. « Ahmed Ben Salah » → « AB »). */
function initials(UserModel $user): string
{
    return mb_strtoupper(mb_substr($user->getPrenom(), 0, 1) . mb_substr($user->getNom(), 0, 1));
}

/** Date SQL → format français (« 2026-11-19 14:05:00 » → « 19/11/2026 »). */
function dateFr(?string $date, bool $withTime = false): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? '—' : date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

/**
 * Zone d'erreur d'un champ de formulaire (remplie par PHP après envoi,
 * et par validation.js dans le navigateur).
 *   <?= fieldError($errors, 'email') ?>
 */
function fieldError(array $errors, string $name): string
{
    $visible = isset($errors[$name]) ? ' is-visible' : '';
    return '<p class="field__error' . $visible . '" data-error-for="' . e($name) . '">' . e($errors[$name] ?? '') . '</p>';
}

/** Classe « is-invalid » si le champ a une erreur côté serveur. */
function invalidClass(array $errors, string $name): string
{
    return isset($errors[$name]) ? ' is-invalid' : '';
}
