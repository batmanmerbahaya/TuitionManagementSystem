<?php

namespace App\Support;

/**
 * Small formatting helpers that the JSP pages used to do inline with scriptlets.
 */
class Format
{
    public const DEFAULT_AVATAR = 'https://img.freepik.com/free-vector/woman-avatar-profile-round-icon_24640-14047.jpg?w=740';

    /** Keep digits only and make sure the number starts with 0 (e.g. "12-345 6789" -> "0123456789"). */
    public static function normalisePhone(?string $raw): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $raw);

        return str_starts_with($clean, '0') ? $clean : '0'.$clean;
    }

    /**
     * "0123456789" -> "+60 12-3456 789" (profile page) or "12-3456 789" (edit form input).
     */
    public static function phone(?string $raw, bool $withCountryCode = true): string
    {
        $phone = (string) $raw;
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) >= 6) {
            $formatted = substr($phone, 0, 2).'-'.substr($phone, 2, 4);
            $rest = substr($phone, 6);
            if ($rest !== '') {
                $formatted .= ' '.$rest;
            }

            return $withCountryCode ? '+60 '.$formatted : $formatted;
        }

        return ($withCountryCode && $phone !== '') ? '+60 '.$phone : $phone;
    }

    public static function money($amount): string
    {
        return number_format((float) $amount, 2);
    }

    public static function firstName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name));

        return $parts[0] ?? '';
    }

    /** "BIOLOGY FORM 4" -> "Biology form 4". */
    public static function sentenceCase(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $value;
        }

        return mb_strtoupper(mb_substr($value, 0, 1)).mb_strtolower(mb_substr($value, 1));
    }

    /** "08:00:00" -> "08:00" */
    public static function hm($time): string
    {
        return $time ? substr((string) $time, 0, 5) : '';
    }

    /**
     * Short timetable badge code for a subject name.
     * $extended = true is the mapping used by the teacher/student dashboards,
     * false is the shorter mapping used on the admin dashboard.
     */
    public static function badgeCode(?string $subjectName, bool $extended = true): string
    {
        $n = mb_strtoupper((string) $subjectName);
        $has = fn (string ...$needles) => array_reduce($needles, fn ($found, $needle) => $found || str_contains($n, $needle), false);

        if ($extended) {
            return match (true) {
                $has('BAHASA', 'MALAY') => 'MAL',
                $has('ENGLISH') => 'ENG',
                $has('ADD') => 'ADD',
                $has('MATH') => 'MAT',
                $has('SCIENCE') => 'SCI',
                $has('HISTORY', 'SEJARAH') => 'HIS',
                $has('AKAUN', 'ACCOUNT', 'PRINSIP') => 'ACC',
                $has('BIO') => 'BIO',
                $has('PHYSIC') => 'PHY',
                $has('CHEM') => 'CHE',
                default => mb_substr($n, 0, 3),
            };
        }

        return match (true) {
            $has('BAHASA') => 'MAL',
            $has('ENGLISH') => 'ENG',
            $has('MATH') => 'MAT',
            $has('SCIENCE') => 'SCI',
            $has('BIO') => 'BIO',
            $has('PHYSIC') => 'PHY',
            $has('CHEM') => 'CHE',
            default => mb_substr($n, 0, 3),
        };
    }
}
