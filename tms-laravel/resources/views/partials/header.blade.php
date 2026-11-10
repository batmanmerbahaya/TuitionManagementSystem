{{--
    Shared top bar + navigation for the logged-in pages.
    Used by the admin, teacher, student and profile pages.

    Parameters:
      $currentPage  - which nav link is highlighted (e.g. 'adminSubStudent')
      $profileMenu  - true on the profile pages: dropdown shows Logout / Change Password / Delete Account,
                      and the page provides its own popups + scripts
      $directLogout - true on the edit-profile page: Logout in the dropdown logs out immediately
--}}
@php
    $currentPage = $currentPage ?? '';
    $profileMenu = $profileMenu ?? false;
    $directLogout = $directLogout ?? false;
    $role = session('userRole');
    $displayName = session('userName') ?: ucfirst(strtolower($role ?? 'User'));
    $displayPic = auth()->user()?->photoUrl() ?? \App\Support\Format::DEFAULT_AVATAR;
    $active = fn (string $page) => $currentPage === $page ? 'active' : '';
    $homeRoute = match ($role) {
        'ADMIN' => 'admin.dashboard',
        'TEACHER' => 'teacher.dashboard',
        default => 'student.dashboard',
    };
@endphp

<header>
    <div class="top-bar">
        <a href="{{ route($homeRoute) }}" class="logo-section" title="Go to dashboard">
            <i class="fas fa-dove fa-2x"></i>
            <h2>Pusat Tuisyen Anjung Ilmu Pendeta</h2>
        </a>

        <div class="user-info">
            <span>Hi, {{ $displayName }}</span>
            <div style="position: relative; display: inline-block;">

                <a href="javascript:void(0)" class="profile-link" onclick="toggleMenu()">
                    <img src="{{ $displayPic }}" alt="Profile Picture" class="profile-pic">
                </a>

                <div id="dropdownMenu" class="dropdown-menu">
                    @if ($profileMenu)
                        @if ($directLogout)
                            <a href="#" onclick="submitAction('{{ route('logout') }}'); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        @else
                            <a href="#" onclick="showLogoutPopup()"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        @endif
                        <a href="{{ route('password.change.form') }}"><i class="fa-solid fa-key"></i> Change Password</a>
                        <a href="#" class="delete-account" onclick="showDeletePopup()"><i class="fa-solid fa-trash-can"></i> Delete Account</a>
                    @else
                        <a href="{{ route('profile.show') }}">
                            <i class="fas fa-user-circle"></i> View Profile
                        </a>
                        <a href="#" class="logout-link" onclick="showLogoutPopup()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="nav-bar">
        @if ($role === 'ADMIN')
            @if (session('adminType') === 'S')
                <a href="{{ route('admin.admins.index') }}" class="nav-link {{ $active('adminSubAdmin') }}">Admin</a>
            @endif
            <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ $active('adminSubTeacher') }}">Teacher</a>
            <a href="{{ route('admin.students.index') }}" class="nav-link {{ $active('adminSubStudent') }}">Student</a>
            <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ $active('adminSubjectList') }}">Subject</a>
            <a href="{{ route('admin.payments.index') }}" class="nav-link {{ $active('adminSubPayment') }}">Payment</a>
            <div class="nav-icons">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ $active('adminDashboard') }}">
                    <i class="fas fa-home {{ $active('adminDashboard') }}"></i>
                </a>
            </div>
        @elseif ($role === 'TEACHER')
            <a href="{{ route('teacher.students') }}" class="nav-link {{ $active('teacherStudent') }}">Student</a>
            <a href="{{ route('teacher.subjects') }}" class="nav-link {{ $active('teacherSubject') }}">Subject</a>
            <div class="nav-icons">
                <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ $active('teacherDashboard') }}">
                    <i class="fas fa-home {{ $active('teacherDashboard') }}"></i>
                </a>
            </div>
        @else
            <a href="{{ route('student.subjects.index') }}" class="nav-link {{ $active('studentSubSubject') }}">Subject</a>
            <a href="{{ route('student.payments.index') }}" class="nav-link {{ $active('studentSubPayment') }}">Payment</a>
            <div class="nav-icons">
                <a href="{{ route('student.dashboard') }}" class="nav-link {{ $active('studentDashboard') }}">
                    <i class="fas fa-home {{ $active('studentDashboard') }}"></i>
                </a>
            </div>
        @endif
    </div>

    @unless ($profileMenu)
    <div id="logoutPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container icon-logout">
                <i class="fas fa-sign-out-alt fa-2x"></i>
            </div>
            <h2>Logout?</h2>
            <p>Are you sure you want to sign out?</p>
            <div class="popup-buttons">
                <button onclick="submitAction('{{ route('logout') }}')" class="btn btn-primary">Yes</button>
                <button onclick="hidePopup('logoutPopup')" class="btn btn-neutral">Cancel</button>
            </div>
        </div>
    </div>

    <div id="deletePopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container icon-warning">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <h2>Delete Account?</h2>
            <p>You won't be able to access the system any longer. This action cannot be undone.</p>
            <div class="popup-buttons">
                <button onclick="submitAction('{{ route('profile.destroy') }}', 'DELETE')" class="btn btn-danger">Yes, Delete</button>
                <button onclick="hidePopup('deletePopup')" class="btn btn-neutral">Cancel</button>
            </div>
        </div>
    </div>
    @endunless
</header>

@include('partials.form-helper')

@unless ($profileMenu)
<script>
    function toggleMenu() { document.getElementById("dropdownMenu").classList.toggle('show'); }
    function showLogoutPopup() { document.getElementById('logoutPopup').style.display = 'flex'; }
    function showDeletePopup() { document.getElementById('deletePopup').style.display = 'flex'; }
    function hidePopup(id) { document.getElementById(id).style.display = 'none'; }
    function closeSuccessPopup() {
        hidePopup('successPopup');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    window.onload = function() {
        const popup = document.getElementById('successPopup');
        if (popup && new URLSearchParams(window.location.search).has('success')) {
            popup.style.display = 'flex';
        }
    }

    window.addEventListener('click', function(e) {
        const menu = document.getElementById("dropdownMenu");
        const icon = document.querySelector('.profile-link');

        if (menu && icon && !menu.contains(e.target) && !icon.contains(e.target)) {
            menu.classList.remove('show');
        }
    });
</script>
@endunless
