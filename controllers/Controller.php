<?php
/**
 * controllers/Controller.php
 * Classe de base de TOUS les contrôleurs WASLA (les 4 modules en héritent).
 *
 *  - $this->db : la connexion PDO partagée (Database::getConnexion()).
 *  - validate() : contrôle de saisie côté serveur. Les noms de règles sont les MÊMES
 *    que ceux de views/frontoffice/assets/js/validation.js (contrôle côté navigateur),
 *    pour que chaque formulaire soit vérifié deux fois avec les mêmes règles.
 *
 * Exemple dans un contrôleur de module :
 *   $errors = $this->validate($post, [
 *       'numero_facture' => 'required|max:50',
 *       'montant'        => 'required|decimal|positive',
 *       'type_ressource' => 'required|in:eau,electricite',
 *   ]);
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

abstract class Controller
{
    protected PDO $db;

    /** Format d'e-mail : exactement le même motif que EMAIL dans validation.js. */
    private const EMAIL = '/^(?=[^@]{1,64}@)[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@([A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/D';

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    /**
     * Vérifie $data selon $rules et retourne les erreurs (tableau vide = tout est valide).
     *
     * Règles disponibles (séparées par « | ») :
     *   required, email, name, phone, password, min:N, max:N, match:champ,
     *   in:a,b,c, int, decimal, positive, date, after:champ, accepted
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @return array<string, string> champ => message d'erreur
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $raw   = $data[$field] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            $list  = explode('|', $ruleString);

            foreach ($list as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, '');

                // Un champ vide n'est contrôlé que par « required » / « accepted »
                if ($value === '' && !in_array($name, ['required', 'accepted'], true)) {
                    continue;
                }

                $message = match ($name) {
                    // seulement des espaces (insécables comprises) = vide, comme trim() en JavaScript
                    'required' => $value === '' || preg_match('/^[\s\x{FEFF}]*\z/u', $value) === 1
                                  ? 'Ce champ est obligatoire.' : null,
                    'email'    => preg_match(self::EMAIL, $value) && mb_strlen($value) <= 150
                                  ? null : 'Veuillez saisir une adresse e-mail valide.',
                    'name'     => preg_match("/^[\\p{L}][\\p{L}' -]{1,49}$/u", $value)
                                  ? null : 'Lettres, espaces, apostrophes et tirets uniquement (2 à 50 caractères).',
                    'phone'    => preg_match('/^(\+216)?[2-9]\d{7}$/', preg_replace('/[\s.-]/u', '', $value) ?? '')
                                  ? null : 'Numéro tunisien à 8 chiffres attendu (ex. 22 123 456).',
                    'password' => match (true) {
                        mb_strlen($value) < 8 || !preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)
                            => 'Au moins 8 caractères, dont une lettre et un chiffre.',
                        // password_hash() garde les espaces des bords : on les refuse
                        preg_match('/^\s|\s\z/u', $raw) === 1
                            => 'Le mot de passe ne doit pas commencer ni finir par un espace.',
                        default => null,
                    },
                    'min'      => mb_strlen($value) >= (int) $param ? null : "Au moins $param caractères.",
                    'max'      => mb_strlen($value) <= (int) $param ? null : "Au plus $param caractères.",
                    // valeurs brutes : « abc12345 » et « abc12345 » suivi d'une espace ne sont pas identiques
                    'match'    => $raw === ($data[$param] ?? '') ? null : 'Les deux valeurs ne correspondent pas.',
                    'in'       => in_array($value, explode(',', $param), true) ? null : 'Valeur non autorisée.',
                    'int'      => preg_match('/^-?\d+$/', $value) ? null : 'Nombre entier attendu.',
                    'decimal'  => preg_match('/^-?\d+([.,]\d{1,3})?$/', $value) ? null : 'Nombre attendu (ex. 45,500).',
                    'positive' => (float) str_replace(',', '.', $value) > 0 ? null : 'La valeur doit être supérieure à 0.',
                    'date'     => $this->isDate($value) ? null : 'Date invalide (format AAAA-MM-JJ).',
                    'after'    => $this->isAfter($value, trim((string) ($data[$param] ?? ''))) ? null : 'Cette date doit être postérieure à la précédente.',
                    'accepted' => in_array($value, ['1', 'on', 'yes'], true) ? null : 'Veuillez cocher cette case.',
                    default    => null,
                };

                if ($message !== null) {
                    $errors[$field] = $message;
                    break; // un seul message par champ
                }
            }
        }

        return $errors;
    }

    private function isDate(string $value): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /** true si $value (date ou date-heure) est strictement après $other ; true si $other est vide. */
    private function isAfter(string $value, string $other): bool
    {
        if ($other === '') {
            return true;
        }
        $a = strtotime($value);
        $b = strtotime($other);
        return $a !== false && $b !== false && $a > $b;
    }

    /** Redirige vers une action de l'application et arrête le script. */
    protected function redirect(string $action, array $params = []): void
    {
        header('Location: ' . url($action, $params));
        exit;
    }
}
