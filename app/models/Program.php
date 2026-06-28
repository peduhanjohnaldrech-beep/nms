<?php

namespace App\Models;

use Core\Model;

class Program extends Model
{
    protected string $table = 'programs';

    public function getActive(): array
    {
        return $this->fetchAll(
            "SELECT * FROM programs WHERE is_active = 1 ORDER BY sort_order, name"
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT * FROM programs ORDER BY sort_order, name"
        );
    }

    public function findByCode(string $code): array|false
    {
        return $this->fetch("SELECT * FROM programs WHERE code = ?", [$code]);
    }

    public function getEnrollmentStats(): array
    {
        return $this->fetchAll(
            "SELECT p.code, p.name, p.icon, p.color,
                    COUNT(pe.id) AS total_enrolled,
                    SUM(CASE WHEN pe.status = 'Active' THEN 1 ELSE 0 END) AS active_count
             FROM programs p
             LEFT JOIN program_enrollments pe ON pe.program = p.code
             WHERE p.is_active = 1
             GROUP BY p.id, p.code, p.name, p.icon, p.color
             ORDER BY p.sort_order, p.name"
        );
    }
}
