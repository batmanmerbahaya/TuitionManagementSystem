<?php

namespace Tests;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Every test starts from the demo data (DemoSeeder): superadmin admin@anjungilmu.com / Admin@123,
 * teacher1/teacher2@anjungilmu.com, student4 (Form 4) / student5 (Form 5)@anjungilmu.com - password Password1.
 * Subjects 1-4 are Form 4 (1 Mathematics Mon 08-10, 2 Science Tue 10-12, 3 English Wed 14-16, 4 Biology Thu 16-18),
 * 5-8 are Form 5; package 1 = Form 4 core (subjects 1, 2, 3), package 2 = Form 5 science (6, 7, 8).
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DemoSeeder::class;

    /** Safety net: never wipe a database that is not a *_test database. */
    protected function beforeRefreshingDatabase(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException("Refusing to run tests on database '$database'. Tests must use a *_test database (see phpunit.xml).");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // ->assertRedirectSays('some message'): the redirect's ?msg=... (URL-decoded) contains the text.
        TestResponse::macro('assertRedirectSays', function (string $text) {
            Assert::assertStringContainsString($text, urldecode((string) $this->headers->get('Location')));

            return $this;
        });
    }

    protected function loginAs(string $email, string $password = 'Password1'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->post('/login', ['email' => $email, 'password' => $password]);
    }

    protected function asAdmin(): static
    {
        $this->loginAs('admin@anjungilmu.com', 'Admin@123')->assertRedirect(route('admin.dashboard'));

        return $this;
    }

    protected function asStudent(string $email = 'student4@anjungilmu.com'): static
    {
        $this->loginAs($email)->assertRedirect(route('student.dashboard'));

        return $this;
    }

    protected function asTeacher(string $email = 'teacher1@anjungilmu.com'): static
    {
        $this->loginAs($email)->assertRedirect(route('teacher.dashboard'));

        return $this;
    }

    protected function logout(): void
    {
        $this->post('/logout');
        $this->app['auth']->forgetGuards();
    }

    /** AJAX registration like the student subject page does. */
    protected function register(array $data): string
    {
        return trim($this->post('/student/register', $data, ['X-Requested-With' => 'XMLHttpRequest'])->getContent());
    }

    protected function studentId(string $email): string
    {
        return DB::table('students')->join('users', 'users.userid', '=', 'students.userid')
            ->where('users.useremail', $email)->value('students.studentid');
    }

    protected function userId(string $email): string
    {
        return DB::table('users')->where('useremail', $email)->where('statusflag', 'A')->value('userid');
    }
}
