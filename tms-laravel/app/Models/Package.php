<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Package extends Model
{
    protected $table = 'packages';
    protected $primaryKey = 'packageid';
    public $timestamps = false;

    protected $fillable = ['packagecode', 'packagename', 'packagefee', 'packagelevel'];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'packagesubjects', 'packageid', 'subjectid');
    }

    /** Packages of a level the student has not registered yet. */
    public static function availableForStudent(string $level, string $studentId): Collection
    {
        return collect(DB::select(
            "SELECT * FROM packages p
              WHERE p.packagelevel = ?
                AND p.packageid NOT IN (
                    SELECT sr.packageid FROM subjectregistrations sr
                     WHERE sr.studentid = ? AND sr.packageid IS NOT NULL AND sr.subregstatusflag <> 'D')
              ORDER BY p.packageid",
            [$level, $studentId]
        ));
    }

    /** Packages (of a level) containing at least one full subject. */
    public static function fullPackageIds(string $level): array
    {
        return array_map('intval', array_column(DB::select(
            'SELECT DISTINCT ps.packageid
               FROM packagesubjects ps
               JOIN packages p ON ps.packageid = p.packageid
               JOIN subjects s ON ps.subjectid = s.subjectid
              WHERE p.packagelevel = ?
                AND '.Subject::OCCUPANCY_SQL.' >= s.subavailability',
            [$level]
        ), 'packageid'));
    }

    /** Name of the first full subject in the package, or null. */
    public static function fullSubjectName(int $packageId): ?string
    {
        $row = DB::selectOne(
            'SELECT s.subjectname
               FROM packagesubjects ps
               JOIN subjects s ON ps.subjectid = s.subjectid
              WHERE ps.packageid = ?
                AND '.Subject::OCCUPANCY_SQL.' >= s.subavailability
              LIMIT 1',
            [$packageId]
        );

        return $row->subjectname ?? null;
    }
}
