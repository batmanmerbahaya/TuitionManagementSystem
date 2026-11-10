<?php

namespace App\Models;

use App\Support\Format;
use App\Support\Ids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * USERS table. Every admin, teacher and student has one row here;
 * the role is decided by which of ADMINS / TEACHERS / STUDENTS links to it.
 */
class User extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'userid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    /** The USERS table has no remember_token column. */
    protected $rememberTokenName = '';

    protected $fillable = ['userid', 'username', 'usercontact', 'useremail', 'userpass', 'userimage', 'statusflag'];

    protected $hidden = ['userpass', 'userimage'];

    /**
     * Creates the USERS row shared by every role.
     * Call inside a DB transaction together with the ADMINS / TEACHERS / STUDENTS insert.
     */
    public static function createAccount(string $name, string $email, string $phone, string $password, ?string $image): self
    {
        return self::create([
            'userid' => Ids::nextUser(),
            'username' => trim($name),
            'usercontact' => $phone,
            'useremail' => trim($email),
            'userpass' => Hash::make($password),
            'userimage' => $image,
            'statusflag' => 'A',
        ]);
    }

    /** Is the email used by another account that is not deleted? (Deleted accounts free their email.) */
    public static function emailTaken(string $email, ?string $exceptUserId = null): bool
    {
        return self::active()->where('useremail', trim($email))
            ->when($exceptUserId, fn ($q) => $q->where('userid', '<>', $exceptUserId))
            ->exists();
    }

    public function getAuthPassword()
    {
        return $this->userpass;
    }

    public function getAuthPasswordName()
    {
        return 'userpass';
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'userid', 'userid');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'userid', 'userid');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'userid', 'userid');
    }

    /** Accounts that have not been soft-deleted (statusflag 'D'). */
    public function scopeActive($query)
    {
        return $query->where(fn ($q) => $q->whereNull('statusflag')->orWhere('statusflag', '<>', 'D'));
    }

    /**
     * Checks a plain password. Passwords are stored with bcrypt; older accounts may still have an
     * unsalted MD5 hash, which is still accepted (and upgraded to bcrypt on login).
     */
    public function passwordMatches(string $plain): bool
    {
        $stored = (string) $this->userpass;

        if (password_get_info($stored)['algoName'] === 'bcrypt') {
            return Hash::check($plain, $stored);
        }

        return $stored !== '' && hash_equals(strtolower($stored), md5($plain));
    }

    public function isLegacyPassword(): bool
    {
        return password_get_info((string) $this->userpass)['algoName'] !== 'bcrypt';
    }

    /**
     * Checked in this order: admin first, then teacher, then student.
     *
     * @return array{0: ?string, 1: ?string} [role, adminType]
     */
    public function resolveRole(): array
    {
        if ($admin = $this->admin) {
            return ['ADMIN', $admin->adminrole];
        }
        if ($this->teacher) {
            return ['TEACHER', null];
        }
        if ($this->student) {
            return ['STUDENT', null];
        }

        return [null, null];
    }

    public function photoUrl(): string
    {
        return $this->getRawOriginal('userimage') !== null
            ? route('users.photo', $this->userid)
            : Format::DEFAULT_AVATAR;
    }
}
