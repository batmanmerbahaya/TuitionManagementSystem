<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Support\Format;
use App\Support\Ids;
use App\Support\Locks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/** Login, logout and student self sign-up. */
class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_LOCKOUT_SECONDS = 5 * 60;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        // At most 5 wrong passwords per email + computer, then a short lock-out.
        $throttleKey = 'login:'.strtolower($email).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return redirect()->route('login', [
                'status' => 'throttled',
                'email' => $email,
                'wait' => (int) ceil(RateLimiter::availableIn($throttleKey) / 60),
            ]);
        }

        $user = User::active()->where('useremail', $email)->first();
        [$role, $adminType] = $user ? $user->resolveRole() : [null, null];

        if (! $user || $role === null || ! $user->passwordMatches($password)) {
            RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);

            return redirect()->route('login', ['status' => 'invalid', 'email' => $email]);
        }
        RateLimiter::clear($throttleKey);

        // Older accounts may still have an MD5 password - upgrade it to bcrypt.
        if ($user->isLegacyPassword()) {
            $user->userpass = Hash::make($password);
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'userId' => $user->userid,
            'userRole' => $role,
            'adminType' => $adminType,
            'userName' => Format::firstName($user->username) ?: ucfirst(strtolower($role)),
        ]);

        return redirect()->route(match ($role) {
            'ADMIN' => 'admin.dashboard',
            'TEACHER' => 'teacher.dashboard',
            default => 'student.dashboard',
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['status' => 'logout']);
    }

    public function showSignup()
    {
        return view('auth.signup');
    }

    public function signup(Request $request)
    {
        $name = trim((string) $request->input('userName'));
        $email = trim((string) $request->input('email'));
        $level = (string) $request->input('level');
        $password = (string) $request->input('password');

        $phone = Format::normalisePhone($request->input('phone'));

        if ($error = $this->accountError($name, $email, $phone, $password, true)) {
            return redirect()->route('signup', ['error' => $error]);
        }
        if (! in_array($level, ['FORM 4', 'FORM 5'], true)) {
            return redirect()->route('signup', ['error' => 'exception']);
        }
        if (! $this->isValidOptionalImage($request, 'profilePic')) {
            return redirect()->route('signup', ['error' => 'invalidImage']);
        }
        if (User::emailTaken($email)) {
            return redirect()->route('signup', ['error' => 'emailTaken']);
        }

        try {
            Locks::run(function () use ($request, $name, $email, $phone, $level, $password) {
                $user = User::createAccount($name, $email, $phone, $password, $this->fileBytes($request, 'profilePic'));
                Student::create(['studentid' => Ids::nextStudent(), 'userid' => $user->userid, 'studentlevel' => $level]);
            });
        } catch (\Throwable $e) {
            Log::error('Sign up failed: '.$e->getMessage());

            return redirect()->route('signup', ['error' => 'exception']);
        }

        return redirect()->route('login', ['status' => 'registered']);
    }
}
