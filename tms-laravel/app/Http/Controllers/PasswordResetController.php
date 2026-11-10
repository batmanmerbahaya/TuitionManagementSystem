<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Forgot password with an emailed 6-digit OTP that is valid for 5 minutes.
 */
class PasswordResetController extends Controller
{
    private const OTP_LIFETIME_SECONDS = 5 * 60;

    private const MAX_CODES = 3;                 // OTP emails per email address + computer ...

    private const CODES_WINDOW_SECONDS = 15 * 60; // ... in 15 minutes

    private const RESEND_WAIT_SECONDS = 30;

    private const MAX_WRONG_GUESSES = 5;         // per code

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $email = trim((string) $request->input('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('password.forgot', ['error' => 'Please enter a valid email address.']);
        }

        if ($wait = $this->tooManyCodes($request, $email)) {
            return redirect()->route('password.forgot', ['error' => "Too many codes requested. Please try again in $wait minute(s)."]);
        }

        $request->session()->put('resetEmail', $email);
        $this->issueOtp($request, $email);

        return redirect()->route('password.otp.form');
    }

    public function resendOtp(Request $request)
    {
        $email = $request->session()->get('resetEmail');
        if (! $email) {
            return redirect()->route('password.forgot');
        }

        if (now()->getTimestamp() - (int) $request->session()->get('otpSentAt', 0) < self::RESEND_WAIT_SECONDS) {
            return redirect()->route('password.otp.form', ['error' => 'wait']);
        }
        if ($this->tooManyCodes($request, $email)) {
            return redirect()->route('password.otp.form', ['error' => 'tooMany']);
        }

        $this->issueOtp($request, $email);

        return redirect()->route('password.otp.form', ['status' => 'resent']);
    }

    public function showVerify(Request $request)
    {
        if (! $request->session()->has('resetEmail')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $session = $request->session();
        $otp = $session->get('generatedOTP');

        if ($otp === null) {
            return redirect()->route('password.otp.form', ['error' => 'locked']);
        }

        if (hash_equals($otp, trim((string) $request->input('otp'))) && now()->getTimestamp() < (int) $session->get('otpExpiry')) {
            $session->put('otpVerified', true);
            $session->forget(['generatedOTP', 'otpExpiry', 'otpAttempts']);

            return redirect()->route('password.reset.form');
        }

        // Too many wrong guesses: this code stops working and a new one must be requested.
        $attempts = (int) $session->get('otpAttempts', 0) + 1;
        if ($attempts >= self::MAX_WRONG_GUESSES) {
            $session->forget(['generatedOTP', 'otpExpiry', 'otpAttempts']);

            return redirect()->route('password.otp.form', ['error' => 'locked']);
        }
        $session->put('otpAttempts', $attempts);

        return redirect()->route('password.otp.form', ['error' => 'invalid']);
    }

    public function showReset(Request $request)
    {
        if (! $request->session()->get('otpVerified')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        $session = $request->session();
        $email = $session->get('resetEmail');

        if (! $email || ! $session->get('otpVerified')) {
            return redirect()->route('login');
        }

        $newPass = (string) $request->input('newPass');
        if ($newPass !== (string) $request->input('confirmPass') || ! $this->isStrongPassword($newPass)) {
            return redirect()->route('password.reset.form', ['error' => 'mismatch']);
        }

        $user = User::where('useremail', $email)->where('statusflag', 'A')->first();
        if (! $user) {
            return redirect()->route('password.reset.form', ['error' => 'dbError']);
        }

        $user->userpass = Hash::make($newPass);
        $user->save();

        $session->invalidate();

        return redirect()->route('login', ['status' => 'passwordChanged']);
    }

    /** Minutes to wait when this email + computer has asked for too many codes, else 0. */
    private function tooManyCodes(Request $request, string $email): int
    {
        $key = 'otp:'.strtolower($email).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_CODES)) {
            return (int) ceil(RateLimiter::availableIn($key) / 60);
        }
        RateLimiter::hit($key, self::CODES_WINDOW_SECONDS);

        return 0;
    }

    private function issueOtp(Request $request, string $email): void
    {
        $otp = (string) random_int(100000, 999999);
        $request->session()->put([
            'generatedOTP' => $otp,
            'otpExpiry' => now()->getTimestamp() + self::OTP_LIFETIME_SECONDS,
            'otpSentAt' => now()->getTimestamp(),
            'otpAttempts' => 0,
            'otpVerified' => false,
        ]);

        // Like EmailUtil.sendOTP(): a mail failure is logged, the user still goes to the OTP page.
        try {
            Mail::raw(
                "Hello,\n\nYour 6-digit OTP code is: {$otp}\n\nThis code is valid for 5 minutes.\n\nRegards,\nAnjung Ilmu System",
                fn ($message) => $message->to($email)->subject('Anjung Ilmu: Password Reset OTP')
            );
        } catch (\Throwable $e) {
            Log::error('SMTP ERROR: '.$e->getMessage());
        }
    }
}
