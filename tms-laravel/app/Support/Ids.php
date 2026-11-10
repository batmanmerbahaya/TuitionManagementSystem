<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generates the readable IDs used by the original system
 * (U001, A001, T001, S001, SR001 and yyyyMMddNN for payments).
 *
 * The numeric part is compared as a number, so U999 is followed by U1000
 * (comparing them as strings would break after 999).
 */
class Ids
{
    public static function next(string $table, string $column, string $prefix, int $pad = 3): string
    {
        $max = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(SUBSTRING($column, ?) AS UNSIGNED)) AS m", [strlen($prefix) + 1])
            ->value('m');

        return $prefix.str_pad((string) (((int) $max) + 1), $pad, '0', STR_PAD_LEFT);
    }

    public static function nextUser(): string
    {
        return self::next('users', 'userid', 'U');
    }

    public static function nextAdmin(): string
    {
        return self::next('admins', 'adminid', 'A');
    }

    public static function nextTeacher(): string
    {
        return self::next('teachers', 'teacherid', 'T');
    }

    public static function nextStudent(): string
    {
        return self::next('students', 'studentid', 'S');
    }

    public static function nextRegistration(): string
    {
        return self::next('subjectregistrations', 'subregid', 'SR');
    }

    /** Payment IDs are today's date followed by a 2-digit running number, e.g. 2026110601. */
    public static function nextPayment(): string
    {
        return self::next('payments', 'paymentid', now()->format('Ymd'), 2);
    }
}
