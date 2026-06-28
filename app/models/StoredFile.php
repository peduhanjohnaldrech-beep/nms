<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class StoredFile extends Model
{
    protected string $table = 'stored_files';

    public function getFiles(string $folder = ''): array
    {
        $sql    = "SELECT sf.*, u.full_name AS uploaded_by_name
                   FROM stored_files sf
                   LEFT JOIN users u ON u.id = sf.uploaded_by
                   WHERE " . ($folder !== '' ? "sf.folder = :folder" : "sf.folder IS NULL") . "
                   ORDER BY sf.uploaded_at DESC";
        $params = $folder !== '' ? [':folder' => $folder] : [];
        return $this->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM stored_files WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function getFolders(): array
    {
        $dir = FILES_STORAGE_PATH;
        if (!is_dir($dir)) return [];

        $folders = [];
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (is_dir($dir . '/' . $entry)) {
                $fileCount = count(array_diff(scandir($dir . '/' . $entry), ['.', '..']));
                $folders[] = ['name' => $entry, 'file_count' => $fileCount];
            }
        }
        usort($folders, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $folders;
    }

    public function deleteFile(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM stored_files WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
