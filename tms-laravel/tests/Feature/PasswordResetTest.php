<?php

namespace Tests\Feature;

use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_full_forgot_password_flow(): void
    {
        $this->post('/forgot-password', ['email' => 'student5@anjungilmu.com'])->assertRedirect(route('password.otp.form'));

        // The new-password page stays locked until the code is verified.
        $this->get('/reset-password')->assertRedirect(route('password.forgot'));

        $otp = session('generatedOTP');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        $this->post('/verify-otp', ['otp' => '000000'])->assertRedirectContains('error=invalid');
        $this->post('/verify-otp', ['otp' => $otp])->assertRedirect(route('password.reset.form'));

        $this->post('/reset-password', ['newPass' => 'weak', 'confirmPass' => 'weak'])->assertRedirectContains('error=mismatch');
        $this->post('/reset-password', ['newPass' => 'NewPass123', 'confirmPass' => 'NewPass123'])->assertRedirectContains('status=passwordChanged');

        $this->loginAs('student5@anjungilmu.com', 'NewPass123')->assertRedirect(route('student.dashboard'));
    }

    public function test_a_code_stops_working_after_five_wrong_guesses(): void
    {
        $this->post('/forgot-password', ['email' => 'student5@anjungilmu.com']);
        $otp = session('generatedOTP');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/verify-otp', ['otp' => '000000'])->assertRedirectContains('error=invalid');
        }
        $this->post('/verify-otp', ['otp' => '000000'])->assertRedirectContains('error=locked');

        // The right code no longer works either; a new one must be requested.
        $this->post('/verify-otp', ['otp' => $otp])->assertRedirectContains('error=locked');
    }

    public function test_codes_expire_after_five_minutes(): void
    {
        $this->post('/forgot-password', ['email' => 'student5@anjungilmu.com']);
        $otp = session('generatedOTP');

        $this->travel(6)->minutes();
        $this->post('/verify-otp', ['otp' => $otp])->assertRedirectContains('error=invalid');
    }

    public function test_resending_needs_a_short_wait_and_is_limited(): void
    {
        $this->post('/forgot-password', ['email' => 'student5@anjungilmu.com']);

        $this->get('/resend-otp')->assertRedirectContains('error=wait');

        $this->travel(31)->seconds();
        $this->get('/resend-otp')->assertRedirectContains('status=resent');
        $this->travel(31)->seconds();
        $this->get('/resend-otp')->assertRedirectContains('status=resent');

        // 3 codes in 15 minutes is the limit.
        $this->travel(31)->seconds();
        $this->get('/resend-otp')->assertRedirectContains('error=tooMany');
        $this->post('/forgot-password', ['email' => 'student5@anjungilmu.com'])->assertRedirectContains('error=');
    }
}
