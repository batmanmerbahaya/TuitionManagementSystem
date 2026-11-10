<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Monthly billing: every bill belongs to a billing month ("2026-11"). The unique key stops the
        // same registration from being billed twice for the same month (even if two admins click
        // "Generate bills" at the same time).
        DB::statement('ALTER TABLE payments ADD billingperiod CHAR(7) NULL AFTER paymentmonth');
        DB::statement("UPDATE payments SET billingperiod = DATE_FORMAT(paymentdate, '%Y-%m')");
        DB::statement('ALTER TABLE payments MODIFY billingperiod CHAR(7) NOT NULL');
        DB::statement('ALTER TABLE payments ADD UNIQUE INDEX payments_one_bill_per_month (subregid, paymenttype, billingperiod)');

        // An email only has to be unique among accounts that are not deleted, so a deleted
        // account's email can be used again. activeemail is NULL for deleted accounts.
        DB::statement('ALTER TABLE users DROP INDEX users_useremail_unique');
        DB::statement("ALTER TABLE users ADD activeemail VARCHAR(255) GENERATED ALWAYS AS (IF(statusflag = 'D', NULL, useremail)) STORED");
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_activeemail_unique (activeemail)');
        DB::statement('ALTER TABLE users ADD INDEX users_useremail_index (useremail)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP INDEX users_useremail_index');
        DB::statement('ALTER TABLE users DROP INDEX users_activeemail_unique');
        DB::statement('ALTER TABLE users DROP COLUMN activeemail');
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_useremail_unique (useremail)');

        DB::statement('ALTER TABLE payments DROP INDEX payments_one_bill_per_month');
        DB::statement('ALTER TABLE payments DROP COLUMN billingperiod');
    }
};
