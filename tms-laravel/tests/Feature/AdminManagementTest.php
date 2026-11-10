<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Student;
use App\Models\SubjectRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    public function test_admin_edits_a_students_details(): void
    {
        $this->asAdmin();
        $userId = $this->userId('student4@anjungilmu.com');

        $this->get('/admin/students')->assertSee('Edit Student Details');

        $this->put("/admin/students/$userId", ['name' => 'Ali Bin Ahmad Zaki', 'email' => 'ali@example.com', 'phone' => '019-876 5432', 'level' => 'FORM 4'])
            ->assertRedirectContains('status=success');

        $this->assertDatabaseHas('users', ['userid' => $userId, 'username' => 'Ali Bin Ahmad Zaki', 'useremail' => 'ali@example.com', 'usercontact' => '0198765432']);
    }

    public function test_level_changes_only_after_the_students_registrations_are_dropped(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $this->logout();

        $this->asAdmin();
        $userId = $this->userId('student4@anjungilmu.com');
        $form = ['name' => 'Ali Bin Ahmad', 'email' => 'student4@anjungilmu.com', 'phone' => '0145556666', 'level' => 'FORM 5'];

        $this->put("/admin/students/$userId", $form)->assertRedirectContains('error=edit');
        $this->assertSame('FORM 4', Student::where('userid', $userId)->value('studentlevel'));

        $regId = SubjectRegistration::where('studentid', $this->studentId('student4@anjungilmu.com'))->value('subregid');
        $this->post("/admin/registrations/$regId/drop")->assertRedirectContains('status=success');

        $this->put("/admin/students/$userId", $form)->assertRedirectContains('status=success');
        $this->assertSame('FORM 5', Student::where('userid', $userId)->value('studentlevel'));
    }

    public function test_admin_edits_a_teacher(): void
    {
        $this->asAdmin();
        $userId = $this->userId('teacher1@anjungilmu.com');

        $this->put("/admin/teachers/$userId", ['name' => 'Aminah Yusof', 'email' => 'aminah@example.com', 'phone' => '0121112222'])
            ->assertRedirectContains('status=updated');
        $this->assertDatabaseHas('users', ['userid' => $userId, 'username' => 'Aminah Yusof', 'useremail' => 'aminah@example.com']);

        // Email already used by someone else
        $this->put("/admin/teachers/$userId", ['name' => 'Aminah Yusof', 'email' => 'teacher2@anjungilmu.com', 'phone' => '0121112222'])
            ->assertRedirectContains('status=error');
    }

    public function test_add_user_forms_are_validated_on_the_server(): void
    {
        $this->asAdmin();
        $form = ['name' => 'Cikgu Lim', 'email' => 'lim@example.com', 'phone' => '0167778888', 'password' => 'Teacher123'];

        $this->post('/admin/teachers', ['email' => 'nope'] + $form)->assertRedirectContains('error=invalidEmail');
        $this->post('/admin/teachers', ['password' => 'short'] + $form)->assertRedirectContains('error=weakPassword');
        $this->post('/admin/teachers', ['phone' => '12'] + $form)->assertRedirectContains('error=invalidPhone');
        $this->post('/admin/students', ['password' => 'short', 'level' => 'FORM 4'] + $form)->assertRedirectContains('error=weakPassword');
        $this->post('/admin/admins', ['email' => 'nope'] + $form)->assertRedirectContains('error=invalidEmail');

        $this->post('/admin/teachers', $form)->assertRedirectContains('status=success');
    }

    public function test_superadmin_changes_roles_and_it_takes_effect_on_the_next_click(): void
    {
        $this->asAdmin();
        $this->post('/admin/admins', ['name' => 'Normal Admin', 'email' => 'admin2@example.com', 'phone' => '0111111111', 'password' => 'Admin1234', 'role' => 'admin'])
            ->assertRedirectContains('status=success');
        $admin2 = $this->userId('admin2@example.com');
        $this->logout();

        // A normal admin cannot open admin management.
        $this->loginAs('admin2@example.com', 'Admin1234');
        $this->get('/admin/admins')->assertRedirect(route('admin.dashboard'));

        // Promoted by the superadmin (in another browser) ...
        Admin::where('userid', $admin2)->update(['adminrole' => 'S']);
        $this->app['auth']->forgetGuards();
        // ... it works without logging out and in again.
        $this->get('/admin/admins')->assertOk()->assertSee('Admin Management');
        $this->logout();

        // Through the page: superadmin demotes admin2 again.
        $this->asAdmin();
        $this->patch("/admin/admins/$admin2/role", ['role' => 'A'])->assertRedirectContains('status=success');
        $this->assertSame('A', Admin::where('userid', $admin2)->value('adminrole'));
    }

    public function test_you_cannot_remove_or_demote_yourself(): void
    {
        $this->asAdmin();
        $me = $this->userId('admin@anjungilmu.com');

        $this->delete("/admin/admins/$me")->assertRedirectContains('status=error');
        $this->patch("/admin/admins/$me/role", ['role' => 'A'])->assertRedirectContains('status=error');

        $this->assertSame('A', User::find($me)->statusflag);
        $this->assertSame('S', Admin::where('userid', $me)->value('adminrole'));
    }

    public function test_the_last_superadmin_cannot_delete_their_account(): void
    {
        $this->asAdmin();

        $this->delete('/profile')->assertRedirectContains('error=lastSuperAdmin');
        $this->assertSame('A', User::find($this->userId('admin@anjungilmu.com'))->statusflag);
    }

    public function test_a_superadmin_can_leave_when_another_superadmin_exists(): void
    {
        $this->asAdmin();
        $this->post('/admin/admins', ['name' => 'Second Boss', 'email' => 'boss2@example.com', 'phone' => '0111111112', 'password' => 'Admin1234', 'role' => 'superadmin']);

        $this->delete('/profile')->assertRedirectContains('status=deleted');
        $this->assertSame(1, Admin::activeSuperAdminCount());
    }

    public function test_a_teacher_without_subjects_can_be_removed(): void
    {
        $this->asAdmin();
        $this->delete('/admin/teachers/'.$this->userId('teacher1@anjungilmu.com'))->assertRedirectContains('reason=hasSubjects');

        DB::table('subjects')->where('teacherid', 'T001')->update(['teacherid' => 'T002']);
        $this->delete('/admin/teachers/'.$this->userId('teacher1@anjungilmu.com'))->assertRedirectContains('status=success');
        $this->get('/admin/teachers')->assertDontSee('Aminah Binti Yusof');
    }
}
