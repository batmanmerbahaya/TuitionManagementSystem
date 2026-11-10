<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_each_role_lands_on_its_own_dashboard(): void
    {
        $this->loginAs('admin@anjungilmu.com', 'Admin@123')->assertRedirect(route('admin.dashboard'));
        $this->logout();
        $this->loginAs('teacher1@anjungilmu.com')->assertRedirect(route('teacher.dashboard'));
        $this->logout();
        $this->loginAs('student4@anjungilmu.com')->assertRedirect(route('student.dashboard'));
    }

    public function test_roles_cannot_open_each_others_pages(): void
    {
        $this->asStudent();

        $this->get('/admin/dashboard')->assertRedirectContains('status=invalid');
        $this->get('/teacher/dashboard')->assertRedirectContains('status=invalid');
        $this->get('/student/dashboard')->assertOk();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_login_is_locked_after_five_wrong_passwords(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginAs('student4@anjungilmu.com', 'wrong-password')->assertRedirectContains('status=invalid');
        }

        // Even the right password is refused during the lock-out.
        $this->loginAs('student4@anjungilmu.com')->assertRedirectContains('status=throttled');
        $this->get(route('login', ['status' => 'throttled', 'wait' => 5]))->assertSee('Too Many Attempts');

        $this->travel(6)->minutes();
        $this->loginAs('student4@anjungilmu.com')->assertRedirect(route('student.dashboard'));
    }

    public function test_old_md5_passwords_still_work_and_are_upgraded(): void
    {
        DB::table('users')->where('useremail', 'student4@anjungilmu.com')->update(['userpass' => md5('OldPass1')]);

        $this->loginAs('student4@anjungilmu.com', 'OldPass1')->assertRedirect(route('student.dashboard'));

        $this->assertStringStartsWith('$2y$', DB::table('users')->where('useremail', 'student4@anjungilmu.com')->value('userpass'));
    }

    public function test_sign_up_is_validated_on_the_server(): void
    {
        $form = ['userName' => 'Nur Aisyah', 'email' => 'aisyah@example.com', 'phone' => '12-345 6789', 'password' => 'Secret123', 'level' => 'FORM 4'];

        $this->post('/signup', ['userName' => 'R2D2'] + $form)->assertRedirectContains('error=invalidName');
        $this->post('/signup', ['email' => 'not-an-email'] + $form)->assertRedirectContains('error=invalidEmail');
        $this->post('/signup', ['phone' => '999'] + $form)->assertRedirectContains('error=invalidPhone');
        $this->post('/signup', ['password' => 'weak'] + $form)->assertRedirectContains('error=weakPassword');
        $this->post('/signup', ['email' => 'student4@anjungilmu.com'] + $form)->assertRedirectContains('error=emailTaken');

        $this->post('/signup', $form)->assertRedirectContains('status=registered');
        $this->assertDatabaseHas('users', ['useremail' => 'aisyah@example.com', 'usercontact' => '0123456789']);
        $this->loginAs('aisyah@example.com', 'Secret123')->assertRedirect(route('student.dashboard'));
    }

    public function test_a_deleted_accounts_email_can_be_used_again(): void
    {
        DB::table('users')->where('useremail', 'student5@anjungilmu.com')->update(['statusflag' => 'D']);

        $this->post('/signup', [
            'userName' => 'New Person', 'email' => 'student5@anjungilmu.com', 'phone' => '0123456789',
            'password' => 'Secret123', 'level' => 'FORM 5',
        ])->assertRedirectContains('status=registered');

        $this->assertSame(2, DB::table('users')->where('useremail', 'student5@anjungilmu.com')->count());
        $this->loginAs('student5@anjungilmu.com', 'Secret123')->assertRedirect(route('student.dashboard'));
    }

    public function test_a_user_deleted_while_logged_in_is_signed_out_on_the_next_click(): void
    {
        $this->asStudent();
        $this->get('/student/dashboard')->assertOk();

        DB::table('users')->where('useremail', 'student4@anjungilmu.com')->update(['statusflag' => 'D']);
        $this->app['auth']->forgetGuards(); // the next request loads the user from the database again

        $this->get('/student/dashboard')->assertRedirectContains('status=deleted');
        $this->get('/student/dashboard')->assertRedirect(route('login'));
    }
}
