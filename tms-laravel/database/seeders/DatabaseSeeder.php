<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use App\Support\Ids;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first superadmin so you can log in and add everyone else.
     * Change the password after the first login (Profile > Change Password).
     */
    public function run(): void
    {
        $email = env('TMS_ADMIN_EMAIL', 'admin@anjungilmu.com');

        if (User::where('useremail', $email)->exists()) {
            $this->command?->info("Superadmin $email already exists.");

            return;
        }

        DB::transaction(function () use ($email) {
            $user = User::createAccount('Super Admin', $email, '0123456789', env('TMS_ADMIN_PASSWORD', 'Admin@123'), null);
            Admin::create(['adminid' => Ids::nextAdmin(), 'userid' => $user->userid, 'adminrole' => 'S']);
        });

        $this->command?->info("Superadmin created: $email");
    }
}
