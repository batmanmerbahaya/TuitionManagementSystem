<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Teacher extends Model
{
    protected $table = 'teachers';
    protected $primaryKey = 'teacherid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['teacherid', 'userid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'teacherid', 'teacherid');
    }

    /**
     * Active teachers with the subjects they teach
     * (each subject as ['name', 'level', 'id'], ordered by name).
     */
    public static function listing(): Collection
    {
        $teachers = collect(DB::select(
            "SELECT t.teacherid, u.userid, u.username, u.usercontact, u.useremail,
                    (u.userimage IS NOT NULL) AS has_photo
               FROM teachers t
               JOIN users u ON t.userid = u.userid
              WHERE (u.statusflag IS NULL OR u.statusflag <> 'D')
              ORDER BY t.teacherid ASC"
        ));

        $subjects = DB::table('subjects')
            ->select('subjectid', 'subjectname', 'subjectlevel', 'teacherid')
            ->orderBy('subjectname')
            ->get()
            ->groupBy('teacherid');

        return $teachers->map(function ($t) use ($subjects) {
            $t->subjects = ($subjects[$t->teacherid] ?? collect())->map(fn ($s) => [
                'id' => $s->subjectid,
                'name' => $s->subjectname ?? '-',
                'level' => $s->subjectlevel ?? '-',
            ])->values()->all();

            return $t;
        });
    }

    /** Active teachers for the subject "Assign Teacher" dropdowns. */
    public static function options(): Collection
    {
        return collect(DB::select(
            "SELECT t.teacherid, u.username
               FROM teachers t
               JOIN users u ON t.userid = u.userid
              WHERE (u.statusflag IS NULL OR u.statusflag <> 'D')
              ORDER BY u.username ASC"
        ));
    }

    /** Distinct active students in any of this teacher's subjects. */
    public static function studentCount(string $userId, ?string $level = null): int
    {
        $sql = "SELECT COUNT(DISTINCT sr.studentid) AS c
                  FROM subjectregistrations sr
                  LEFT JOIN packagesubjects ps ON sr.packageid = ps.packageid
                  JOIN subjects s ON (sr.subjectid = s.subjectid OR ps.subjectid = s.subjectid)
                  JOIN teachers t ON s.teacherid = t.teacherid
                  JOIN students st ON sr.studentid = st.studentid
                  JOIN users u ON st.userid = u.userid
                 WHERE t.userid = ?
                   AND u.statusflag = 'A'
                   AND sr.subregstatusflag = 'A'";
        $bindings = [$userId];

        if ($level !== null) {
            $sql .= ' AND st.studentlevel = ?';
            $bindings[] = $level;
        }

        return (int) DB::selectOne($sql, $bindings)->c;
    }

    /**
     * The students this teacher teaches,
     * with the names of this teacher's subjects each one is enrolled in.
     */
    public static function studentList(string $userId): Collection
    {
        return collect(DB::select(
            "SELECT u.userid, u.username, st.studentid, st.studentlevel, u.usercontact,
                    (u.userimage IS NOT NULL) AS has_photo,
                    (SELECT GROUP_CONCAT(DISTINCT sub2.subjectname ORDER BY sub2.subjectname SEPARATOR ', ')
                       FROM subjects sub2
                       JOIN subjectregistrations sr2
                         ON (sr2.subjectid = sub2.subjectid
                             OR sub2.subjectid IN (SELECT ps.subjectid FROM packagesubjects ps WHERE ps.packageid = sr2.packageid))
                      WHERE sr2.studentid = st.studentid
                        AND sr2.subregstatusflag = 'A'
                        AND sub2.teacherid = t.teacherid) AS subjects
               FROM students st
               JOIN users u ON st.userid = u.userid
               JOIN teachers t ON t.userid = ?
              WHERE u.statusflag = 'A'
                AND EXISTS (
                    SELECT 1 FROM subjectregistrations sr
                     WHERE sr.studentid = st.studentid
                       AND sr.subregstatusflag = 'A'
                       AND (sr.subjectid IN (SELECT sub.subjectid FROM subjects sub WHERE sub.teacherid = t.teacherid)
                            OR sr.packageid IN (SELECT ps.packageid FROM packagesubjects ps
                                                  JOIN subjects sub ON ps.subjectid = sub.subjectid
                                                 WHERE sub.teacherid = t.teacherid)))
              ORDER BY u.username ASC",
            [$userId]
        ));
    }
}
