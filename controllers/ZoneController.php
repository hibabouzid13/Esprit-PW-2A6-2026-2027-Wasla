<?php
/**
 * controllers/ZoneController.php
 * Lecture des zones (utilisée par l'inscription et par tous les modules).
 * Le CRUD complet des zones sera ajouté par le Module 1 dans sa branche.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ZoneModel.php';

class ZoneController extends Controller
{
    /** @return ZoneModel[] les 24 gouvernorats, triés par nom */
    public function getGouvernorats(): array
    {
        $stmt = $this->db->query("SELECT * FROM zone WHERE type_zone = 'gouvernorat' ORDER BY nom_zone");
        return array_map(fn (array $row) => ZoneModel::fromArray($row), $stmt->fetchAll());
    }

    public function exists(int $idZone): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM zone WHERE id_zone = :id LIMIT 1');
        $stmt->execute(['id' => $idZone]);
        return (bool) $stmt->fetchColumn();
    }
}
