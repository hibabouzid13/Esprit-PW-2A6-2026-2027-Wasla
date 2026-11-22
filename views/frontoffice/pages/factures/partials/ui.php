<?php
/**
 * Aides d'affichage partagées par les vues du Module 2 (Hiba).
 * Aucune donnée, aucun SQL : uniquement du balisage. Toute valeur variable passe par e().
 */

if (!function_exists('billLogo')) {
    /**
     * Pastille logo STEG (électricité) ou SONEDE (eau).
     * Les logos sont des symboles du sprite (partials/sprite.php), à inclure une fois par page.
     *
     * @param string $type       'electricite' | 'eau'
     * @param string $taille     'xl' (60 px) | 'lg' (52 px) | 'md' (36 px) | 'sm' (26 px) | 'xs' (20 px)
     * @param bool   $decoratif  true si le nom du fournisseur est déjà écrit à côté (logo ignoré par les lecteurs d'écran)
     */
    function billLogo(string $type, string $taille = 'lg', bool $decoratif = false): string
    {
        $cle = $type === 'eau' ? 'sonede' : 'steg';
        $nom = $type === 'eau' ? 'SONEDE' : 'STEG';
        $taille = in_array($taille, ['xl', 'lg', 'md', 'sm', 'xs'], true) ? $taille : 'lg';
        $a11y = $decoratif ? 'aria-hidden="true"' : 'role="img" aria-label="' . e($nom) . '"';
        return '<span class="bill-logo bill-logo--' . $cle . ' bill-logo--' . $taille . '">'
             . '<svg ' . $a11y . ' focusable="false"><use href="#bill-logo-' . $cle . '"/></svg></span>';
    }
}
