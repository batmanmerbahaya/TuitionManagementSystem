<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subject;
use App\Support\Format;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Add, update and delete subject packages. */
class PackageController extends Controller
{
    private const NAME_PATTERN = '/^[A-Za-z0-9 ]+$/';

    public function create()
    {
        $subjects = Subject::orderBy('subjectlevel')->orderBy('subjectname')->get(['subjectid', 'subjectname', 'subjectcode', 'subjectlevel']);

        return view('admin.packages.create', [
            'form4Subjects' => $subjects->filter(fn ($s) => strcasecmp($s->subjectlevel, 'FORM 4') === 0),
            'form5Subjects' => $subjects->filter(fn ($s) => strcasecmp($s->subjectlevel, 'FORM 5') === 0),
        ]);
    }

    public function store(Request $request)
    {
        $back = fn (string $msg) => redirect()->route('admin.packages.create', ['status' => 'error', 'msg' => $msg]);

        $name = trim((string) $request->input('packageName'));
        if (! preg_match(self::NAME_PATTERN, $name)) {
            return $back('Name can only contain letters, numbers and spaces (e.g. Basic Package Form 4).');
        }

        $code = strtoupper(trim((string) $request->input('packageCode')));
        if (! $this->isValidCode($code)) {
            return $back("Package code '$code' is invalid. Use the format XXXF4 or XXXF5 (e.g. SCIF4).");
        }

        $price = (string) $request->input('price', '');
        $subjectIds = array_map('intval', (array) $request->input('selectedSubjects', []));
        $level = (string) $request->input('level');

        if (empty($subjectIds) || $price === '' || ! is_numeric($price) || ! in_array($level, ['FORM 4', 'FORM 5'], true)) {
            return $back('MissingFields');
        }
        if ((float) $price < 0) {
            return $back('The fee cannot be negative.');
        }

        // A package must be attendable: its classes cannot overlap each other.
        if ($clash = Subject::firstClashWithin(Subject::whereIn('subjectid', $subjectIds)->get())) {
            return $back('These subjects cannot be in one package: '.$clash);
        }

        try {
            DB::transaction(function () use ($code, $name, $price, $level, $subjectIds) {
                $package = Package::create([
                    'packagecode' => 'PKG-'.$code,
                    'packagename' => Format::sentenceCase($name),
                    'packagefee' => (float) $price,
                    'packagelevel' => $level,
                ]);
                $package->subjects()->sync($subjectIds);
            });
        } catch (UniqueConstraintViolationException) {
            return $back('Duplicate Package Code. One of the package codes already exists. Package codes must be unique.');
        } catch (\Throwable $e) {
            Log::error('Add package failed: '.$e->getMessage());

            return $back('The package could not be saved.');
        }

        return redirect()->route('admin.subjects.index', ['tab' => 'packages', 'status' => 'success']);
    }

    public function update(Request $request, int $packageId)
    {
        $error = fn (string $msg) => redirect()->route('admin.subjects.index', ['tab' => 'packages', 'status' => 'error', 'msg' => $msg]);

        $package = Package::find($packageId);
        if (! $package) {
            return $error('missing_package_id');
        }

        $price = str_replace(',', '', trim((string) $request->input('price', '')));
        if ($price === '') {
            return $error('missing_price');
        }
        if (! is_numeric($price)) {
            return $error('invalid_price');
        }

        $code = strtoupper(trim((string) $request->input('packageCode')));
        if (! $this->isValidCode($code)) {
            return $error("Package code '$code' is invalid. Use the format XXXF4 or XXXF5 (e.g. SCIF4).");
        }

        $name = trim((string) $request->input('packageName'));
        if (! preg_match(self::NAME_PATTERN, $name)) {
            return $error('Name can only contain letters, numbers and spaces (e.g. Basic Package Form 4).');
        }
        $name = Format::sentenceCase($name);
        if ((float) $price < 0) {
            return $error('The fee cannot be negative.');
        }

        $newSubjectIds = collect((array) $request->input('selectedSubjects', []))->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $currentSubjectIds = $package->subjects()->pluck('subjects.subjectid')->map(fn ($id) => (int) $id)->sort()->values();

        if ($newSubjectIds->isEmpty()) {
            return $error('A package needs at least one subject.');
        }

        if ($newSubjectIds->all() !== $currentSubjectIds->all()) {
            // Changing the subjects would silently change what enrolled students attend (and the seat
            // counts of those subjects), so it is only allowed while nobody is registered.
            $enrolled = DB::table('subjectregistrations')->where('packageid', $packageId)->where('subregstatusflag', '<>', 'D')->count();
            if ($enrolled > 0) {
                return $error("$enrolled student(s) are registered for this package, so its subjects cannot be changed. You can still change its name, code and fee (the new fee applies from the next monthly bill).");
            }
            if ($clash = Subject::firstClashWithin(Subject::whereIn('subjectid', $newSubjectIds->all())->get())) {
                return $error('These subjects cannot be in one package: '.$clash);
            }
        }

        try {
            DB::transaction(function () use ($package, $name, $price, $code, $newSubjectIds) {
                $package->update(['packagename' => $name, 'packagefee' => (float) $price, 'packagecode' => 'PKG-'.$code]);
                $package->subjects()->sync($newSubjectIds->all());
            });
        } catch (UniqueConstraintViolationException) {
            return $error('Duplicate Package Code. One of the package codes already exists. Package codes must be unique.');
        }

        return redirect()->route('admin.subjects.index', ['tab' => 'packages', 'status' => 'updated', 'name' => $name]);
    }

    public function destroy(Request $request, int $packageId)
    {
        $name = (string) $request->input('name', 'Package');
        $error = fn (string $msg) => redirect()->route('admin.subjects.index', ['tab' => 'packages', 'status' => 'error', 'msg' => $msg]);

        // Blocked while a registered student has a paid (APPROVED) or under-review (PENDING) payment
        // for it. Students who already dropped the package do not count.
        $activeStudents = DB::table('subjectregistrations as sr')
            ->join('payments as p', 'sr.subregid', '=', 'p.subregid')
            ->where('sr.packageid', $packageId)
            ->where('sr.subregstatusflag', '<>', 'D')
            ->whereIn('p.paymentstatus', ['APPROVED', 'PENDING'])
            ->exists();
        if ($activeStudents) {
            return $error('Cannot delete package with active students');
        }

        $deleted = DB::transaction(function () use ($packageId) {
            DB::table('packagesubjects')->where('packageid', $packageId)->delete();

            // Unpaid / rejected registrations of this package are removed (their bills cascade with them).
            DB::table('subjectregistrations')
                ->where('packageid', $packageId)
                ->whereIn('subregid', DB::table('payments')->whereIn('paymentstatus', ['UNPAID', 'REJECTED'])->select('subregid'))
                ->delete();

            return Package::whereKey($packageId)->delete();
        });

        return $deleted
            ? redirect()->route('admin.subjects.index', ['tab' => 'packages', 'status' => 'deleted', 'name' => $name])
            : $error('Package not found');
    }
}
