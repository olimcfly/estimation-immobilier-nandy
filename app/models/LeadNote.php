<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class LeadNote
{
    public static function createTable(): void
    {
        Database::connection()->exec("
            CREATE TABLE IF NOT EXISTS lead_notes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lead_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                author VARCHAR(120) NOT NULL DEFAULT 'Admin',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_lead_notes_lead (lead_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function findByLeadId(int $leadId): array
    {
        $sql = 'SELECT * FROM lead_notes WHERE lead_id = :lead_id ORDER BY created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':lead_id' => $leadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $leadId, string $content, string $author): int
    {
        $sql = 'INSERT INTO lead_notes (lead_id, content, author, created_at) VALUES (:lead_id, :content, :author, NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':lead_id' => $leadId,
            ':content' => $content,
            ':author' => $author,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM lead_notes WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
