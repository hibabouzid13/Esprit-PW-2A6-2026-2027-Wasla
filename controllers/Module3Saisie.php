<?php
/**
 * controllers/Module3Saisie.php — Module 3 (Louay)
 * Outils de contrôle de saisie partagés par les contrôleurs du module 3.
 *
 *  - MESSAGES (constante de chaque contrôleur) : un texte par champ et par règle.
 *    Le MÊME texte sert en PHP (validateWithMessages) et en JavaScript (attributs data-message-*
 *    écrits dans la vue par messageAttrs()) : l'utilisateur voit le même message avec ou sans JS.
 *  - Une valeur reçue sous forme de liste (ex. email[]=x) à la place d'un texte est refusée
 *    proprement, sans avertissement PHP « Array to string conversion ».
 *
 * Règles supplémentaires du module (vérifiées aussi dans le navigateur par js/services/refuge-forms.js,
 * attribut data-extra-rules) : matricule, max-value:N, min-value:N.
 */

declare(strict_types=1);

trait Module3Saisie
{
    /** Message affiché quand un champ texte arrive sous forme de liste. */
    public static string $valeurInvalide = 'Valeur non autorisée.';

    /**
     * Attributs data-message-* d'un champ, à écrire dans la balise du formulaire.
     * Ex. : <input … data-rules="required|phone"<?= EntrepriseController::messageAttrs('telephone') ?>>
     *
     * @param array<string, string|int> $vars remplacements dans le message (ex. ['{n}' => 12])
     */
    public static function messageAttrs(string $field, array $vars = []): string
    {
        $out = '';
        foreach (static::MESSAGES[$field] ?? [] as $rule => $message) {
            $out .= ' data-message-' . $rule . '="' . e(strtr($message, array_map('strval', $vars))) . '"';
        }
        return $out;
    }

    /** Message d'un champ pour une règle (avec remplacements éventuels). */
    public static function message(string $field, string $rule, array $vars = []): string
    {
        return strtr(static::MESSAGES[$field][$rule] ?? '', array_map('strval', $vars));
    }

    /**
     * Controller::validate() + refus des listes + messages de MESSAGES à la place des messages génériques.
     *
     * @param array<string, mixed>  $post
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    protected function validateWithMessages(array $post, array $rules): array
    {
        $errors = [];
        foreach (array_keys($rules) as $field) {
            if (isset($post[$field]) && !is_string($post[$field])) {
                $errors[$field] = self::$valeurInvalide;
            }
        }

        // Message générique de Controller::validate() → nom de la règle
        $generiques = [
            'Ce champ est obligatoire.'                 => 'required',
            'Les deux valeurs ne correspondent pas.'    => 'match',
            'Veuillez cocher cette case.'               => 'accepted',
            'Valeur non autorisée.'                     => 'in',
            'Nombre entier attendu.'                    => 'int',
            'Nombre attendu (ex. 45,500).'              => 'decimal',
            'La valeur doit être supérieure à 0.'       => 'positive',
        ];
        foreach ($this->validate($post, $rules) as $field => $message) {
            if (isset($errors[$field])) {
                continue;
            }
            $rule = $generiques[$message] ?? null;
            $errors[$field] = ($rule !== null ? (static::MESSAGES[$field][$rule] ?? null) : null) ?? $message;
        }
        return $errors;
    }

    /** Texte d'un champ (chaîne vide si absent ou si ce n'est pas un texte). */
    protected static function text(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    /** Texte NON nettoyé (mot de passe : les espaces comptent, comme dans AuthController::login()). */
    protected static function raw(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    /** true si l'exception PDO est un doublon sur une clé unique (erreur MySQL 1062). */
    protected static function isDuplicate(PDOException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
