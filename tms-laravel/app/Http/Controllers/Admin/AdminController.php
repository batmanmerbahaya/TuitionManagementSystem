<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Support\Format;
use App\Support\Ids;
use App\Support\Locks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Superadmin-only admin management: list, add, delete, and promote / demote admins. */
class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::listing();

        return view('admin.admins.index', [
            'admins' => $admins,
            'totalAdmins' => $admins->count(),
            'superAdmins' => $admins->where('adminrole', 'S')->count(),
            'normalAdmins' => $admins->where('adminrole', '<>', 'S')->count(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['type' => 'admin']);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));
        $password = (string) $request->input('password');

        if ($error = $this->accountError($name, $email, $phone, $password, true)) {
            return redirect()->route('admin.admins.create', ['error' => $error]);
        }
        if (! $this->isValidOptionalImage($request, 'image')) {
            return redirect()->route('admin.admins.create', ['error' => 'invalidImage']);
        }
        if (User::emailTaken($email)) {
            return redirect()->route('admin.admins.create', ['error' => 'emailTaken']);
        }

        $role = $request->input('role') === 'superadmin' ? 'S' : 'A';

        try {
            Locks::run(function () use ($request, $name, $email, $phone, $password, $role) {
                $user = User::createAccount($name, $email, $phone, $password, $this->fileBytes($request, 'image'));
                Admin::create(['adminid' => Ids::nextAdmin(), 'userid' => $user->userid, 'adminrole' => $role]);
            });
        } catch (\Throwable $e) {
            Log::error('Add admin failed: '.$e->getMessage());

            return redirect()->route('admin.admins.create', ['status' => 'fail']);
        }

        return redirect()->route('admin.admins.create', ['status' => 'success']);
    }

    public function destroy(Request $request, string $userId)
    {
        $user = User::active()->find($userId);
        if (! $user || ! $user->admin) {
            return redirect()->route('admin.admins.index', ['status' => 'error', 'msg' => 'Admin not found.']);
        }

        // Use "Delete Account" on your own profile page instead (it logs you out properly).
        if ($userId === $request->session()->get('userId')) {
            return redirect()->route('admin.admins.index', ['status' => 'error', 'msg' => 'You cannot remove your own account from this list.']);
        }
        if ($user->admin->adminrole === 'S' && Admin::activeSuperAdminCount() <= 1) {
            return redirect()->route('admin.admins.index', ['status' => 'error', 'msg' => 'This is the last superadmin, so it cannot be removed.']);
        }

        $user->update(['statusflag' => 'D']);

        return redirect()->route('admin.admins.index', ['status' => 'success', 'name' => $user->username ?: 'Admin']);
    }

    public function updateRole(Request $request, string $userId)
    {
        $role = $request->input('role');
        $admin = Admin::where('userid', $userId)->first();

        if (! $admin || ! in_array($role, ['A', 'S'], true) || $userId === $request->session()->get('userId')) {
            return redirect()->route('admin.admins.index', ['status' => 'error', 'msg' => 'Invalid request. You cannot change your own role.']);
        }
        if ($admin->adminrole === 'S' && $role === 'A' && Admin::activeSuperAdminCount() <= 1) {
            return redirect()->route('admin.admins.index', ['status' => 'error', 'msg' => 'This is the last superadmin, so it cannot be changed to a normal admin.']);
        }

        $admin->update(['adminrole' => $role]);

        return redirect()->route('admin.admins.index', [
            'status' => 'success',
            'msg' => optional($admin->user)->username.' is now '.($role === 'S' ? 'a Super Admin.' : 'an Admin.'),
        ]);
    }
}
