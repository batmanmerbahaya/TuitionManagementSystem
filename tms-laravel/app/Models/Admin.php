<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Admin extends Model
{
    protected $table = 'admins';
    protected $primaryKey = 'adminid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['adminid', 'adminrole', 'userid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    /** Superadmins whose account is not deleted. */
    public static function activeSuperAdminCount(): int
    {
        return self::where('adminrole', 'S')
            ->whereHas('user', fn ($q) => $q->active())
            ->count();
    }

    /** Every admin whose account is not deleted. */
    public static function listing(): Collection
    {
        return collect(DB::select(
            "SELECT a.adminid, a.adminrole, u.userid, u.username, u.usercontact, u.useremail
               FROM admins a
               JOIN users u ON a.userid = u.userid
              WHERE (u.statusflag IS NULL OR u.statusflag <> 'D')
              ORDER BY a.adminid ASC"
        ));
    }
}
