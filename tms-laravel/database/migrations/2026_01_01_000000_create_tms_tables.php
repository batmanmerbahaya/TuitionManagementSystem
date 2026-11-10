<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The TMS database (MySQL):
 *  - PACKAGES has a PACKAGELEVEL column
 *  - SUBJECTREGISTRATIONS.SUBJECTID / PACKAGEID are nullable (one or the other is set)
 *  - PAYMENTS uses a string PAYMENTID (yyyyMMddNN), SUBREGID, TRANSACTION_ID and a nullable ADMINID
 *  - money columns are DECIMAL(10,2)
 *  - subject start/end times are TIME columns
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('userid', 10)->primary();
            $table->string('username', 255);
            $table->string('usercontact', 15);
            $table->string('useremail', 255)->unique();
            $table->string('userpass', 255);
            $table->char('statusflag', 1)->default('A');   // A = active, D = deleted (soft delete)
        });
        // Profile pictures are stored in the database like the original (BLOB).
        DB::statement('ALTER TABLE users ADD userimage LONGBLOB NULL AFTER userpass');

        Schema::create('admins', function (Blueprint $table) {
            $table->string('adminid', 10)->primary();
            $table->char('adminrole', 1);                    // A = admin, S = superadmin
            $table->string('userid', 10);
            $table->foreign('userid')->references('userid')->on('users')->cascadeOnDelete();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->string('teacherid', 10)->primary();
            $table->string('userid', 10);
            $table->foreign('userid')->references('userid')->on('users')->cascadeOnDelete();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->string('studentid', 10)->primary();
            $table->string('studentlevel', 10);              // 'FORM 4' or 'FORM 5'
            $table->string('userid', 10);
            $table->foreign('userid')->references('userid')->on('users')->cascadeOnDelete();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->increments('subjectid');
            $table->string('subjectcode', 20)->unique();     // e.g. SUB-BIOF4
            $table->string('subjectname', 255);
            $table->string('subjectlevel', 10);
            $table->decimal('subjectfee', 10, 2);
            $table->string('subjectday', 10);                // Monday ... Sunday
            $table->time('subtimestart');
            $table->time('subtimeend');
            $table->integer('subavailability');              // number of slots
            $table->string('teacherid', 10);
            $table->foreign('teacherid')->references('teacherid')->on('teachers')->cascadeOnDelete();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->increments('packageid');
            $table->string('packagecode', 20)->unique();     // e.g. PKG-SCIF4
            $table->string('packagename', 255);
            $table->decimal('packagefee', 10, 2);
            $table->string('packagelevel', 10);
        });

        Schema::create('packagesubjects', function (Blueprint $table) {
            $table->unsignedInteger('subjectid');
            $table->unsignedInteger('packageid');
            $table->primary(['subjectid', 'packageid']);
            $table->foreign('subjectid')->references('subjectid')->on('subjects')->cascadeOnDelete();
            $table->foreign('packageid')->references('packageid')->on('packages')->cascadeOnDelete();
        });

        Schema::create('subjectregistrations', function (Blueprint $table) {
            $table->string('subregid', 100)->primary();      // SR001, SR002 ...
            $table->string('studentid', 10);
            $table->unsignedInteger('subjectid')->nullable(); // set for an individual subject
            $table->unsignedInteger('packageid')->nullable(); // set for a package
            $table->timestamp('registrationdatetime')->useCurrent();
            $table->char('subregstatusflag', 1)->default('A'); // A = active, D = dropped
            $table->foreign('studentid')->references('studentid')->on('students');
            // Keep (dropped) registrations and their payment history if a subject/package is deleted.
            $table->foreign('subjectid')->references('subjectid')->on('subjects')->nullOnDelete();
            $table->foreign('packageid')->references('packageid')->on('packages')->nullOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('paymentid', 20)->primary();      // yyyyMMddNN
            $table->date('paymentdate');
            $table->time('paymenttime');
            $table->string('paymenttype', 50);               // TUITION or REGISTRATION
            $table->string('paymentmonth', 20);              // e.g. NOVEMBER
            $table->decimal('paymentamount', 10, 2);
            $table->string('paymentstatus', 20);             // UNPAID, PENDING, APPROVED, REJECTED, CANCELLED
            $table->string('subregid', 100);
            $table->string('adminid', 10)->nullable();       // admin who approved/rejected
            $table->string('transaction_id', 50)->nullable()->index(); // groups payments uploaded together
            $table->foreign('subregid')->references('subregid')->on('subjectregistrations')->cascadeOnDelete();
            $table->foreign('adminid')->references('adminid')->on('admins')->nullOnDelete();
        });
        DB::statement('ALTER TABLE payments ADD paymentproof LONGBLOB NULL AFTER paymentstatus');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subjectregistrations');
        Schema::dropIfExists('packagesubjects');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');
    }
};
