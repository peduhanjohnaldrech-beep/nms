<?php

class DateHelper
{
    public static function ageInMonths(string $dob, ?string $referenceDate = null): int
    {
        try {
            $birth = new DateTime($dob);
            $ref   = $referenceDate ? new DateTime($referenceDate) : new DateTime();
        } catch (\Exception $e) {
            return 0;
        }
        if ($ref < $birth) return 0;
        $diff = $birth->diff($ref);
        return ($diff->y * 12) + $diff->m;
    }

    public static function formatDate(string $date, string $format = 'F j, Y'): string
    {
        if (empty($date) || $date === '0000-00-00') return '—';
        try {
            return (new DateTime($date))->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    public static function formatAge(int $months): string
    {
        $years = intdiv($months, 12);
        $rem   = $months % 12;
        if ($years === 0) return "{$rem} month" . ($rem !== 1 ? 's' : '');
        if ($rem === 0)   return "{$years} year" . ($years !== 1 ? 's' : '');
        return "{$years} yr" . ($years !== 1 ? 's' : '') . " {$rem} mo" . ($rem !== 1 ? 's' : '');
    }
}
