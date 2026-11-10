<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Serialises writes that generate IDs (U001, SR001, 2026111001 ...) or check free seats,
 * so two requests arriving at the same moment cannot pick the same ID or both take the
 * last seat. Uses a MySQL named lock and runs the callback inside a transaction.
 */
class Locks
{
    public const WRITES = 'tms_writes';

    public static function run(Closure $callback, string $name = self::WRITES, int $timeoutSeconds = 15): mixed
    {
        $acquired = (int) DB::selectOne('SELECT GET_LOCK(?, ?) AS ok', [$name, $timeoutSeconds])->ok;
        if ($acquired !== 1) {
            throw new RuntimeException('The system is busy, please try again.');
        }

        try {
            return DB::transaction($callback);
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS ok', [$name]);
        }
    }
}
