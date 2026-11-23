<?php
/**
 * controllers/FormMessages.php — Module 1 (Houssem)
 * Messages d'erreur IDENTIQUES en JavaScript et en PHP.
 *
 * Chaque contrôleur du module déclare une constante MESSAGES :
 *     public const MESSAGES = ['id_zone' => ['required' => 'Sélectionnez la zone touchée.'], ...];
 *
 *  - la vue écrit ces messages dans les attributs data-message-* (lus par validation.js) :
 *        <select ... data-rules="required"<?= InterruptionController::messageAttrs('id_zone') ?>>
 *  - après validate(), le contrôleur remplace le message générique par le même texte :
 *        $errors = $this->withMessages($errors);
 * Envoyé avec ou sans JavaScript, le formulaire affiche donc le même message.
 */

declare(strict_types=1);

trait FormMessages
{
    /** Attributs data-message-* d'un champ, prêts à écrire dans la balise (texte échappé). */
    public static function messageAttrs(string $field): string
    {
        $html = '';
        foreach (static::MESSAGES[$field] ?? [] as $rule => $message) {
            $html .= ' data-message-' . $rule . '="' . e($message) . '"';
        }
        return $html;
    }

    /**
     * Remplace le message générique de Controller::validate() par le message propre au champ.
     *
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    protected function withMessages(array $errors): array
    {
        foreach ($errors as $field => $message) {
            $rule = self::ruleOf($message);
            if ($rule !== null && isset(static::MESSAGES[$field][$rule])) {
                $errors[$field] = static::MESSAGES[$field][$rule];
            }
        }
        return $errors;
    }

    /** Règle qui a produit un message générique de Controller::validate() (null si inconnue). */
    private static function ruleOf(string $message): ?string
    {
        return match (true) {
            $message === 'Ce champ est obligatoire.'                        => 'required',
            $message === 'Cette date doit être postérieure à la précédente.' => 'after',
            $message === 'Les deux valeurs ne correspondent pas.'           => 'match',
            $message === 'Valeur non autorisée.'                            => 'in',
            $message === 'Nombre entier attendu.'                           => 'int',
            str_starts_with($message, 'Au moins ')                          => 'min',
            str_starts_with($message, 'Au plus ')                           => 'max',
            default                                                         => null,
        };
    }
}
