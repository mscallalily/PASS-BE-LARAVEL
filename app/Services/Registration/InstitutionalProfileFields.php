<?php

namespace App\Services\Registration;

use App\Models\Department;

/** Optional, printed profile data. Missing text remains missing; a course is not guessed. */
class InstitutionalProfileFields
{
    public static function read(array $document, string $category): array
    {
        $lines = [];
        foreach ($document['words'] ?? [] as $word) $lines[$word['line'] ?? 'unknown'][] = $word;
        $strong = [];
        foreach ($lines as $words) {
            if (min(array_column($words, 'confidence')) < 80) continue;
            $strong[] = trim(preg_replace('/\s+/u', ' ', mb_strtoupper(implode(' ', array_column($words, 'text')))));
        }
        $found = [];
        if ($category === 'college') {
            $departments = Department::where('is_active', true)->get()->filter(fn ($d) => !in_array(strtoupper($d->department_code), ['SHS', 'JHS', 'BED'], true)
                && !in_array(strtoupper($d->department_name), ['SECURITY OFFICE', 'HR DEPARTMENT', 'IT DEPARTMENT', 'REGISTRAR OFFICE'], true));
            foreach ($strong as $line) foreach ($departments as $department) {
                foreach ([$department->department_name, $department->department_code] as $value) {
                    if (mb_strlen($value) >= 3 && preg_match('/(?<![\pL\pN])'.preg_quote(mb_strtoupper($value), '/').'(?![\pL\pN])/u', $line)) $found['department'][$department->department_name] = true;
                }
            }
        } elseif ($category === 'employee') {
            foreach ($strong as $line) if (preg_match('/^(?:DEPARTMENT|OFFICE)\s*[:\-]\s*(.{2,150})$/u', $line, $m)) $found['department'][trim($m[1])] = true;
        } else {
            foreach ($strong as $line) {
                if (preg_match('/\bGRADE\s*(11|12)\b/u', $line, $m)) $found['year_level'][$m[1]] = true;
                if (preg_match('/^SECTION\s*[:\-]\s*([\pL\pN][\pL\pN .\'-]{0,59})$/u', $line, $m)) $found['section'][trim($m[1])] = true;
            }
        }
        $out = [];
        foreach ($found as $key => $values) if (count($values) === 1) $out[$key] = (string) array_key_first($values);
        return $out;
    }
}
