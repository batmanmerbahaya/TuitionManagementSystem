{{-- "My Profile" for every role ({{ route('profile.show') }} / {{ route('profile.show') }} / {{ route('profile.show') }}). --}}
@php
    $displayPic = $user->photoUrl();
    $displayPhone = \App\Support\Format::phone($user->usercontact);
    $displayRole = $adminType === 'S' ? 'Superadmin' : 'Admin';
    $cssFile = ['ADMIN' => 'adminProfile', 'TEACHER' => 'teacherProfile'][$role] ?? 'studentProfile';
    $homeRoute = ['ADMIN' => 'admin.dashboard', 'TEACHER' => 'teacher.dashboard'][$role] ?? 'student.dashboard';
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>{{ ucfirst(strtolower($role)) }} - Profile</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/'.$cssFile.'.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    @include('partials.header', ['profileMenu' => true])

    <div class="container">
        <div class="profile-card">
            <div class="back-btn-container">
                <a href="{{ route($homeRoute) }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Home
                </a>
                <a href="{{ route('profile.edit') }}" class="btn btn-primary" style="padding: 10px 24px; border-radius: 50px; text-decoration: none;">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
            
            <h1 class="page-title">MY PROFILE</h1>
            <form>
                <div class="profile-layout">
                    <div class="left-col">
                        <div class="large-avatar-container">
                            <img id="profile-pic" src="{{ $displayPic }}" alt="Profile Picture">
                        </div>
                    </div>
                    <div class="right-col">
                        @if ($role === 'ADMIN')
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="{{ $displayRole }}" class="input-modern" disabled>
                        </div>
                        @endif
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" value="{{ $user->username }}" class="input-modern" disabled>
                        </div>
                        @if ($role === 'STUDENT')
                        <div class="form-group">
                            <label>Level</label>
                            <input type="text" value="{{ $level }}" class="input-modern" disabled>
                        </div>
                        @endif
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" value="{{ $displayPhone }}" class="input-modern" disabled>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="{{ $user->useremail }}" class="input-modern" disabled>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="logoutPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container info">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2>Logout?</h2>
            <p>Are you sure you want to sign out?</p>
            <div class="popup-buttons">
                <button onclick="hidePopup('logoutPopup')" class="btn btn-neutral">Cancel</button>
                <button onclick="submitAction('{{ route('logout') }}')" class="btn btn-primary">Yes, Logout</button>
            </div>
        </div>
    </div>

    <div id="deletePopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Delete Account?</h2>
            <p>You won't be able to access the system any longer. This action cannot be undone.</p>
            <div class="popup-buttons">
                <button onclick="hidePopup('deletePopup')" class="btn btn-neutral">Cancel</button>
                <button onclick="submitAction('{{ route('profile.destroy') }}', 'DELETE')" class="btn btn-danger">Yes, Delete</button>
            </div>
        </div>   
    </div>

    <div id="successPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container success">
                <i class="fas fa-check"></i>
            </div>
            <h2>Updated Successfully!</h2>
            <p>Your profile information has been saved.</p>
            <div class="popup-buttons">
                <button class="btn btn-primary" onclick="closeSuccessPopup()">Done</button>
            </div>
        </div>
    </div>

    <div id="errorPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container error">
                <i class="fas fa-ban"></i>
            </div>
            <h2>Cannot Delete Account</h2>
            <p id="errorPopupMsg">An error occurred.</p>
            <div class="popup-buttons">
                <button class="btn btn-primary" onclick="closeErrorPopup()">Understood</button>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() { document.getElementById("dropdownMenu").classList.toggle('show'); }
        function showLogoutPopup() { document.getElementById('logoutPopup').style.display = 'flex'; }
        function showDeletePopup() { document.getElementById('deletePopup').style.display = 'flex'; }
        function hidePopup(id) { document.getElementById(id).style.display = 'none'; }
        
        function closeSuccessPopup() {
            hidePopup('successPopup');
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function closeErrorPopup() {
            hidePopup('errorPopup');
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('success')) {
                document.getElementById('successPopup').style.display = 'flex';
            }
            const err = params.get('error');
            if (err === 'hasRegisteredSubjects') {
                document.getElementById('errorPopupMsg').innerText =
                    'You have registered subjects or packages and cannot delete your account, whether paid or not. Please drop all your registrations first before deleting your account.';
                document.getElementById('errorPopup').style.display = 'flex';
            } else if (err === 'lastSuperAdmin') {
                document.getElementById('errorPopupMsg').innerText =
                    'You are the only superadmin. Make another admin a superadmin first (Admin page), then you can delete your account.';
                document.getElementById('errorPopup').style.display = 'flex';
            } else if (err === 'hasSubjects') {
                document.getElementById('errorPopupMsg').innerText =
                    'You are still assigned to teach one or more subjects. Please ask an admin to reassign or remove those subjects before deleting your account.';
                document.getElementById('errorPopup').style.display = 'flex';
            } else if (err === 'deleteFailed') {
                document.getElementById('errorPopupMsg').innerText =
                    'We could not delete your account. Please try again later.';
                document.getElementById('errorPopup').style.display = 'flex';
            }
        }
        
        window.addEventListener('click', function(e) {
            const menu = document.getElementById("dropdownMenu");
            const icon = document.querySelector('.profile-link');
            if (!menu.contains(e.target) && !icon.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    </script>
</body>
</html>