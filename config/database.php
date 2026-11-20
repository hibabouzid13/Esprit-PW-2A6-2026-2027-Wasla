<?php
/**
 * config/database.php
 * Connexion PDO sécurisée — patron Singleton (workshop « Sessions et Sécurité », partie 2).
 *
 * Une seule connexion est créée par requête puis partagée par tous les contrôleurs :
 *     $this->db = Database::getConnexion();
 *
 * Adaptez DB_USER / DB_PASS à votre MySQL (Laragon / XAMPP / WAMP : root sans mot de passe).
 */

declare(strict_types=1);

class Database
{
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'wasla';
    private const DB_USER = 'root';
    private const DB_PASS = '';

    private static ?PDO $pdo = null;

    public static function getConnexion(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=utf8mb4',
                    self::DB_USER,
                    self::DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreur SQL = exception
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées (anti injection SQL)
                    ]
                );
            } catch (PDOException $e) {
                // On n'affiche jamais le détail technique au visiteur
                error_log('Connexion MySQL impossible : ' . $e->getMessage());
                http_response_code(500);
                die('Erreur : connexion à la base de données impossible. Vérifiez que MySQL est démarré '
                  . 'et que la base « wasla » a été importée (database/wasla.sql).');
            }
        }
        return self::$pdo;
    }
}
