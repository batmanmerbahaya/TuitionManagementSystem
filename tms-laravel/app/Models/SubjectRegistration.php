<?php

namespace App\Models;

use App\Support\Ids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SUBJECTREGISTRATIONS - one row per individual subject OR per package a student registers for.
 * subregstatusflag: 'A' = active, 'D' = dropped.
 */
class SubjectRegistration extends Model
{
    protected $table = 'subjectregistrations';
    protected $primaryKey = 'subregid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['subregid', 'studentid', 'subjectid', 'packageid', 'subregstatusflag'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentid', 'studentid');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subjectid', 'subjectid');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'packageid', 'packageid');
    }

    public static function registerSubject(string $studentId, int $subjectId): self
    {
        return self::create([
            'subregid' => Ids::nextRegistration(),
            'studentid' => $studentId,
            'subjectid' => $subjectId,
            'packageid' => null,
            'subregstatusflag' => 'A',
        ]);
    }

    public static function registerPackage(string $studentId, int $packageId): self
    {
        return self::create([
            'subregid' => Ids::nextRegistration(),
            'studentid' => $studentId,
            'subjectid' => null,
            'packageid' => $packageId,
            'subregstatusflag' => 'A',
        ]);
    }

    /**
     * One row per subject the student (or students) are registered for.
     * A package registration produces one row per subject in the package, all with the same subregid.
     *
     * @param  string|null  $studentId  null = every student (used by the admin student page)
     */
    public static function registeredItems(?string $studentId = null): Collection
    {
        $filter = $studentId !== null ? 'r.studentid = ? AND' : '';
        $bindings = $studentId !== null ? [$studentId, $studentId] : [];

        return collect(DB::select(
            "SELECT r.subregid, r.studentid, s.subjectname, s.subjectlevel, s.subjectday, s.subtimestart, s.subtimeend, '-' AS packagename
               FROM subjectregistrations r
               JOIN subjects s ON r.subjectid = s.subjectid
              WHERE $filter r.packageid IS NULL AND r.subregstatusflag <> 'D'
             UNION
             SELECT r.subregid, r.studentid, s.subjectname, s.subjectlevel, s.subjectday, s.subtimestart, s.subtimeend, p.packagename
               FROM subjectregistrations r
               JOIN packages p ON r.packageid = p.packageid
               JOIN packagesubjects ps ON p.packageid = ps.packageid
               JOIN subjects s ON ps.subjectid = s.subjectid
              WHERE $filter r.subregstatusflag <> 'D'
             ORDER BY subregid, packagename",
            $bindings
        ));
    }

    /** Whether the student has any registration that is not dropped. */
    public static function hasActive(string $studentId): bool
    {
        return self::where('studentid', $studentId)->where('subregstatusflag', '<>', 'D')->exists();
    }

    /** The student has never registered anything (even dropped). */
    public static function isFirstTime(string $studentId): bool
    {
        return ! self::where('studentid', $studentId)->exists();
    }

    /** Whether the registration belongs to this student. */
    public static function ownedBy(string $subRegId, string $studentId): bool
    {
        return self::where('subregid', $subRegId)
            ->where('studentid', $studentId)
            ->where('subregstatusflag', '<>', 'D')
            ->exists();
    }

    /** Soft drop. */
    public static function drop(string $subRegId): void
    {
        self::where('subregid', $subRegId)->update(['subregstatusflag' => 'D']);
    }
}
