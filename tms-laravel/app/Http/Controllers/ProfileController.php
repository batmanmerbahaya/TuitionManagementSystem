<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Subject;
use App\Models\SubjectRegistration;
use App\Models\User;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "My profile" pages shared by all three roles: view and edit the profile, change the password
 * and delete the account.
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', $this->profileData($request));
    }

    public function edit(Request $request)
    {
        return view('profile.edit', $this->profileData($request));
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $name = trim((string) $request->input('userName'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));

        if ($error = $this->accountError($name, $email, $phone)) {
            return redirect()->route('profile.edit', ['error' => $error]);
        }
        if (! $this->isValidOptionalImage($request, 'profileImage')) {
            return redirect()->route('profile.edit', ['error' => 'invalidImage']);
        }
        if (User::emailTaken($email, $user->userid)) {
            return redirect()->route('profile.edit', ['error' => 'emailTaken']);
        }

        $emailChanged = strcasecmp($email, (string) $user->useremail) !== 0;

        $user->username = $name;
        $user->useremail = $email;
        $user->usercontact = $phone;
        if ($image = $this->fileBytes($request, 'profileImage')) {
            $user->userimage = $image;
        }
        $user->save();

        // Changing the login email logs the user out (same as the original).
        if ($emailChanged) {
            $this->endSession($request);

            return redirect()->route('login', ['status' => 'securityChanged']);
        }

        $request->session()->put('userName', Format::firstName($name));

        return redirect()->route('profile.show', ['success' => 'true']);
    }

    public function editPassword()
    {
        return view('profile.change-password');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->passwordMatches((string) $request->input('currPass'))) {
            return redirect()->route('password.change.form', ['error' => 'wrongCurrentPassword']);
        }

        $newPass = (string) $request->input('newPass');
        if ($newPass !== (string) $request->input('confirmPass') || ! $this->isStrongPassword($newPass)) {
            return redirect()->route('password.change.form', ['error' => 'weakPassword']);
        }

        $user->userpass = bcrypt($newPass);
        $user->save();

        $this->endSession($request);

        return redirect()->route('login', ['status' => 'securityChanged']);
    }

    public function destroy(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $role = $request->session()->get('userRole');

        // A teacher who still teaches subjects cannot delete their account.
        if ($role === 'TEACHER' && Subject::where('teacherid', optional($user->teacher)->teacherid)->exists()) {
            return redirect()->route('profile.show', ['error' => 'hasSubjects']);
        }

        // A student with any active registration (paid or not) cannot delete their account.
        if ($role === 'STUDENT' && $user->student && SubjectRegistration::hasActive($user->student->studentid)) {
            return redirect()->route('profile.show', ['error' => 'hasRegisteredSubjects']);
        }

        // The system must always keep at least one superadmin.
        if ($role === 'ADMIN' && optional($user->admin)->adminrole === 'S' && Admin::activeSuperAdminCount() <= 1) {
            return redirect()->route('profile.show', ['error' => 'lastSuperAdmin']);
        }

        $user->statusflag = 'D';
        $user->save();
        $this->endSession($request);

        return redirect()->route('login', ['status' => 'deleted']);
    }

    /** Streams a profile picture from the USERS.USERIMAGE blob. */
    public function photo(string $userId)
    {
        $image = User::whereKey($userId)->value('userimage');
        if ($image === null) {
            return redirect()->away(Format::DEFAULT_AVATAR);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($image) ?: 'image/jpeg';

        return response($image, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    private function profileData(Request $request): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            'user' => $user,
            'role' => $request->session()->get('userRole'),
            'adminType' => $request->session()->get('adminType'),
            'level' => optional($user->student)->studentlevel,
        ];
    }

    private function endSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
