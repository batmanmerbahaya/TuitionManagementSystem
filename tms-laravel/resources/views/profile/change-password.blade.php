

<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change Password</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/changePassword.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    @include('partials.header', ['currentPage' => ''])

    <div class="container">
        <div class="profile-card">
        	<div class="back-btn-container">
                <a href="{{ route('profile.show') }}" class="back-btn"> <i class="fas fa-arrow-left"></i>BACK</a>
			</div>
        
            <div class="page-header">
                <h1 class="page-title">Change Password</h1>
                <p class="page-subtitle">Ensure your account stays secure</p>
            </div>

            <div class="glass-panel" style="max-width: 500px; margin: 0 auto;">
                <form id="passwordForm" action="{{ route('password.change') }}" method="post">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="oldPass">Current Password :</label>
                        <div class="password-wrapper">
                            <input type="password" id="oldPass" name="currPass" class="input-modern" placeholder="Enter current password" required style="width: 100%; box-sizing: border-box;">
                            <i class="fas fa-eye toggle-icon" onclick="togglePass('oldPass', this)"></i>
                        </div>
                        <span id="curr-pass-error" class="error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="newPass">New Password :</label>
                        <div class="password-wrapper">
                            <input type="password" id="newPass" name="newPass" class="input-modern" placeholder="Enter new password" onkeyup="validatePass()" required style="width: 100%; box-sizing: border-box;">
                            <i class="fas fa-eye toggle-icon" onclick="togglePass('newPass', this)"></i>
                        </div>
                        <span id="pass-error" class="error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirmPass">Confirm Password :</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirmPass" name="confirmPass" class="input-modern" placeholder="Re-enter new password" required style="width: 100%; box-sizing: border-box;">
                            <i class="fas fa-eye toggle-icon" onclick="togglePass('confirmPass', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; justify-content: center; padding: 14px;">Update Password</button>
                    
                </form>
            </div>
        </div>
    </div>

    

    <div id="alertPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div id="alertIcon" class="popup-icon-container error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2 id="alertTitle">Error</h2>
            <p id="alertMsg"></p>
            <div class="popup-buttons">
                <button onclick="hidePopup('alertPopup')" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script>
       
        function showLogoutPopup() { 
            document.getElementById("dropdownMenu").classList.remove('show'); 
            document.getElementById('logoutPopup').style.display = 'flex'; 
        }
        function closeLogoutPopup() {
	        document.getElementById('logoutPopup').style.display = 'none';
	    }
        function togglePass(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function toggleMenu() { 
            document.getElementById("dropdownMenu").classList.toggle('show'); 
        }

        function showPopup(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function hidePopup(id) {
            document.getElementById(id).style.display = 'none';
        }

        function showAlertPopup(title, message) {
            document.getElementById('alertTitle').innerText = title;
            document.getElementById('alertMsg').innerText = message;
            showPopup('alertPopup');
        }
		
        window.addEventListener('click', function(e) {
            const menu = document.getElementById("dropdownMenu");
            const icon = document.querySelector('.profile-link');
            
            if (!menu.contains(e.target) && !icon.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        function setInputStatus(field, errorMsgElement, message) {
            if (message) {
                field.classList.add("input-error");
                errorMsgElement.innerText = message;
                errorMsgElement.classList.add("show");
                return false;
            } else {
                field.classList.remove("input-error");
                errorMsgElement.innerText = "";
                errorMsgElement.classList.remove("show");
                return true;
            }
        }

        function validatePass() {
            const field = document.getElementById('newPass');
            const errorMsg = document.getElementById('pass-error');
            const val = field.value;

            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasLen = val.length >= 8;

            if (val.length > 0) {
                if (!hasLen) return setInputStatus(field, errorMsg, "At least 8 characters required.");
                if (!hasUpper) return setInputStatus(field, errorMsg, "Needs an uppercase letter.");
                if (!hasLower) return setInputStatus(field, errorMsg, "Needs a lowercase letter.");
                if (!hasNum) return setInputStatus(field, errorMsg, "Needs a number.");
                return setInputStatus(field, errorMsg, null);
            } else {
                return setInputStatus(field, errorMsg, null);
            }
        }

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = document.getElementById('newPass').value;
            const confirmPass = document.getElementById('confirmPass').value;

            if (newPass !== confirmPass) {
                e.preventDefault();
                showAlertPopup("Passwords Do Not Match", "Your new password and confirmation do not match. Please re-enter them.");
                return;
            }

            if (!validatePass()) {
                e.preventDefault();
                return;
            }

            // No client-side success popup here - the server verifies the current
            // password and redirects to {{ route('login') }}?status=securityChanged on
            // success, or back here with ?error=wrongCurrentPassword on failure.
        });

        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            if (error === 'wrongCurrentPassword') {
                setInputStatus(document.getElementById('oldPass'), document.getElementById('curr-pass-error'), "Current password is incorrect.");
            } else if (error === 'weakPassword') {
                showAlertPopup("Password Not Accepted", "Your new password must match the confirmation and have at least 8 characters with an uppercase letter, a lowercase letter and a number.");
            } else if (error === 'systemError') {
                showAlertPopup("Something Went Wrong", "An error occurred while changing your password. Please try again.");
            }
        });
    </script>
</body>
</html>