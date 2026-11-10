<?php

namespace App\Models;

use App\Support\Ids;
use App\Support\Locks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PAYMENTS - one bill line. Registering creates UNPAID lines for the current month (TUITION, plus a
 * one-off REGISTRATION fee the first time); every following month a new TUITION line is generated
 * (see generateMonthlyBills()). The student uploads one proof for several lines at once; those lines
 * share a transaction_id and become PENDING until an admin APPROVES/REJECTS them.
 *
 * billingperiod is the month a bill is for, as "YYYY-MM".
 */
class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'paymentid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'paymentid', 'paymentdate', 'paymenttime', 'paymenttype', 'paymentmonth', 'billingperiod',
        'paymentamount', 'paymentstatus', 'paymentproof', 'subregid', 'adminid', 'transaction_id',
    ];

    protected $hidden = ['paymentproof'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(SubjectRegistration::class, 'subregid', 'subregid');
    }

    public static function registrationFee(): float
    {
        return (float) config('tms.payment.registration_fee', 15);
    }

    public static function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    /** "2026-11" -> Carbon for 1 November 2026 */
    public static function periodStart(string $period): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
    }

    /** "2026-11" -> "NOVEMBER 2026" */
    public static function periodLabel(?string $period): string
    {
        return $period ? strtoupper(self::periodStart($period)->format('F Y')) : '';
    }

    public static function isValidPeriod(?string $period): bool
    {
        return is_string($period) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1;
    }

    /**
     * One UNPAID bill line for a billing month (default: this month).
     * Callers that create several records must wrap the work in Locks::run().
     */
    public static function bill(string $subRegId, float $amount, string $type, ?string $period = null): self
    {
        $period ??= self::currentPeriod();

        return self::create([
            'paymentid' => Ids::nextPayment(),
            'paymentdate' => now()->toDateString(),
            'paymenttime' => now()->format('H:i:s'),
            'paymenttype' => $type,
            'paymentmonth' => strtoupper(self::periodStart($period)->format('F')),
            'billingperiod' => $period,
            'paymentamount' => $amount,
            'paymentstatus' => 'UNPAID',
            'subregid' => $subRegId,
        ]);
    }

    /**
     * Creates this month's TUITION bill for every active registration that does not have one yet,
     * at the subject's / package's current fee. Safe to run more than once for the same month.
     *
     * @return int number of bills created
     */
    public static function generateMonthlyBills(string $period): int
    {
        $periodEnd = self::periodStart($period)->endOfMonth();

        return Locks::run(function () use ($period, $periodEnd) {
            $due = DB::select(
                "SELECT sr.subregid, COALESCE(s.subjectfee, p.packagefee) AS fee
                   FROM subjectregistrations sr
                   JOIN students st ON sr.studentid = st.studentid
                   JOIN users u ON st.userid = u.userid
                   LEFT JOIN subjects s ON sr.subjectid = s.subjectid
                   LEFT JOIN packages p ON sr.packageid = p.packageid
                  WHERE sr.subregstatusflag = 'A'
                    AND u.statusflag = 'A'
                    AND (s.subjectid IS NOT NULL OR p.packageid IS NOT NULL)
                    AND sr.registrationdatetime <= ?
                    AND NOT EXISTS (SELECT 1 FROM payments x
                                     WHERE x.subregid = sr.subregid
                                       AND x.paymenttype = 'TUITION'
                                       AND x.billingperiod = ?)
                  ORDER BY sr.subregid",
                [$periodEnd->toDateTimeString(), $period]
            );

            foreach ($due as $row) {
                self::bill($row->subregid, (float) $row->fee, 'TUITION', $period);
            }

            return count($due);
        });
    }

    private static function describe(?string $type, ?string $packageName, ?string $subjectName): string
    {
        if (strcasecmp((string) $type, 'REGISTRATION') === 0) {
            return 'Registration Fee';
        }

        return $packageName ?? $subjectName ?? (string) $type;
    }

    /** UNPAID and REJECTED lines of a student. */
    public static function unpaidBills(string $studentId): Collection
    {
        return collect(DB::select(
            "SELECT p.paymentid, p.paymentdate, p.paymenttype, p.paymentamount, p.paymentstatus, p.billingperiod,
                    s.subjectname, pkg.packagename
               FROM payments p
               JOIN subjectregistrations sr ON p.subregid = sr.subregid
               LEFT JOIN subjects s ON sr.subjectid = s.subjectid
               LEFT JOIN packages pkg ON sr.packageid = pkg.packageid
              WHERE sr.studentid = ? AND p.paymentstatus IN ('UNPAID', 'REJECTED')
              ORDER BY p.billingperiod, p.paymentdate, p.paymentid",
            [$studentId]
        ))->map(function ($p) {
            $p->description = self::describe($p->paymenttype, $p->packagename, $p->subjectname);
            $p->periodLabel = self::periodLabel($p->billingperiod);

            return $p;
        });
    }

    /**
     * Payment lines grouped into transactions
     * (lines uploaded together share a transaction_id; lines without one stand alone).
     *
     * @param  string  $filter  'PENDING', 'HISTORY' or anything else for all
     * @param  string|null  $userId  limit to one student's user id
     * @param  string|null  $key  limit to one transaction id / payment id
     */
    public static function grouped(string $filter, ?string $userId = null, ?string $key = null): Collection
    {
        $sql = "SELECT p.paymentid, p.transaction_id, p.paymentdate, p.paymenttime, p.paymentamount,
                       p.paymentstatus, p.paymenttype, p.billingperiod, (p.paymentproof IS NOT NULL) AS has_proof,
                       u.userid, u.username AS studentname, u.usercontact, u.useremail, s.studentid,
                       COALESCE(sub.subjectname, pkg.packagename) AS item_name
                  FROM payments p
                  JOIN subjectregistrations sr ON p.subregid = sr.subregid
                  JOIN students s ON sr.studentid = s.studentid
                  JOIN users u ON s.userid = u.userid
                  LEFT JOIN subjects sub ON sr.subjectid = sub.subjectid
                  LEFT JOIN packages pkg ON sr.packageid = pkg.packageid
                 WHERE 1 = 1";
        $bindings = [];

        if ($filter === 'PENDING') {
            $sql .= " AND p.paymentstatus = 'PENDING'";
        } elseif ($filter === 'HISTORY') {
            $sql .= $userId !== null
                ? " AND p.paymentstatus IN ('APPROVED', 'REJECTED', 'PENDING')"
                : " AND p.paymentstatus IN ('APPROVED', 'REJECTED')";
        }
        if ($userId !== null) {
            $sql .= ' AND s.userid = ?';
            $bindings[] = $userId;
        }
        if ($key !== null) {
            $sql .= " AND COALESCE(NULLIF(p.transaction_id, ''), p.paymentid) = ?";
            $bindings[] = $key;
        }
        $sql .= ' ORDER BY p.paymentdate DESC, p.paymenttime DESC, p.paymentid';

        $groups = [];
        foreach (DB::select($sql, $bindings) as $row) {
            $groupKey = ($row->transaction_id !== null && $row->transaction_id !== '') ? $row->transaction_id : $row->paymentid;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = (object) [
                    'transactionId' => $groupKey,
                    'paymentId' => $row->paymentid,
                    'userId' => $row->userid,
                    'studentId' => $row->studentid,
                    'studentName' => $row->studentname,
                    'studentPhone' => $row->usercontact,
                    'studentEmail' => $row->useremail,
                    'paymentDate' => $row->paymentdate,
                    'paymentTime' => $row->paymenttime,
                    'paymentStatus' => $row->paymentstatus,
                    'paymentAmount' => 0.0,
                    'hasProof' => (bool) $row->has_proof,
                    'items' => [],
                ];
            }

            $isRegistration = strcasecmp((string) $row->paymenttype, 'REGISTRATION') === 0;

            $groups[$groupKey]->items[] = [
                'description' => $isRegistration ? 'Registration Fee' : ($row->item_name ?? 'Tuition Fee'),
                'type' => $isRegistration ? 'REGISTRATION' : 'TUITION',
                'period' => $row->billingperiod,
                'periodLabel' => self::periodLabel($row->billingperiod),
                'amount' => (float) $row->paymentamount,
            ];
            $groups[$groupKey]->paymentAmount += (float) $row->paymentamount;
        }

        return collect(array_values($groups));
    }

    /** The full transaction (for a receipt) that a payment id or transaction id belongs to. */
    public static function findGroup(string $id): ?object
    {
        $line = self::where('paymentid', $id)->orWhere('transaction_id', $id)->first(['paymentid', 'transaction_id']);
        if (! $line) {
            return null;
        }
        $key = ($line->transaction_id !== null && $line->transaction_id !== '') ? $line->transaction_id : $line->paymentid;

        return self::grouped('ALL', null, $key)->first();
    }

    /** Status of the newest payment line of a registration. */
    public static function latestStatus(string $subRegId): string
    {
        return self::where('subregid', $subRegId)
            ->orderByDesc('paymentdate')->orderByDesc('paymenttime')->orderByDesc('paymentid')
            ->value('paymentstatus') ?? 'UNPAID';
    }

    /**
     * The same, for many registrations in one query.
     *
     * @return array<string, string>
     */
    public static function latestStatuses(array $subRegIds): array
    {
        if (empty($subRegIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($subRegIds), '?'));
        $rows = DB::select(
            "SELECT subregid, paymentstatus FROM (
                SELECT subregid, paymentstatus,
                       ROW_NUMBER() OVER (PARTITION BY subregid ORDER BY paymentdate DESC, paymenttime DESC, paymentid DESC) AS rn
                  FROM payments WHERE subregid IN ($placeholders)
             ) latest WHERE rn = 1",
            array_values($subRegIds)
        );

        return collect($rows)->pluck('paymentstatus', 'subregid')->all();
    }

    /**
     * Unconfirmed money (UNPAID/PENDING/REJECTED) is deleted.
     * Confirmed money for this month (or later) is kept but marked CANCELLED; months that were
     * already paid and attended before stay APPROVED so past revenue is not lost.
     */
    public static function resolveForDrop(string $subRegId): void
    {
        self::where('subregid', $subRegId)->whereIn('paymentstatus', ['UNPAID', 'PENDING', 'REJECTED'])->delete();
        self::where('subregid', $subRegId)
            ->whereIn('paymentstatus', ['APPROVED', 'PAID'])
            ->where('billingperiod', '>=', self::currentPeriod())
            ->update(['paymentstatus' => 'CANCELLED']);
    }

    /**
     * Attach one proof file to the selected bill lines and
     * mark them PENDING under a new transaction id. Only the student's own UNPAID/REJECTED lines are touched.
     */
    public static function attachProof(array $paymentIds, string $fileBytes, string $studentId): int
    {
        $transactionId = 'TXN'.(int) floor(microtime(true) * 1000);

        return self::whereIn('paymentid', $paymentIds)
            ->whereIn('paymentstatus', ['UNPAID', 'REJECTED'])
            ->whereIn('subregid', SubjectRegistration::where('studentid', $studentId)->select('subregid'))
            ->update([
                'paymentproof' => $fileBytes,
                'paymentstatus' => 'PENDING',
                'transaction_id' => $transactionId,
            ]);
    }

    /**
     * Approve/reject a transaction id (or a single payment id).
     * Accepts a comma separated list.
     */
    public static function process(string $ids, string $status, string $adminId): void
    {
        foreach (array_filter(array_map('trim', explode(',', $ids))) as $id) {
            $updated = self::where('transaction_id', $id)->where('paymentstatus', 'PENDING')
                ->update(['paymentstatus' => $status, 'adminid' => $adminId]);
            if ($updated === 0) {
                self::where('paymentid', $id)->where('paymentstatus', 'PENDING')
                    ->update(['paymentstatus' => $status, 'adminid' => $adminId]);
            }
        }
    }

    /** Admin dashboard revenue chart - APPROVED amounts per billing month (year included). */
    public static function monthlyRevenue(): Collection
    {
        return collect(DB::select(
            "SELECT billingperiod, SUM(paymentamount) AS total
               FROM payments
              WHERE paymentstatus = 'APPROVED'
              GROUP BY billingperiod
              ORDER BY billingperiod"
        ))->map(function ($row) {
            $row->label = self::periodStart($row->billingperiod)->format('M Y');

            return $row;
        });
    }
}
