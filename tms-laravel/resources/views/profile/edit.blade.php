{{-- Edit profile for every role ({{ route('profile.edit') }} / {{ route('profile.edit') }} / {{ route('profile.edit') }}). --}}
@php
    $displayPic = $user->photoUrl();
    $displayPhone = \App\Support\Format::phone($user->usercontact, false);
    $displayRole = $adminType === 'S' ? 'Superadmin' : 'Admin';
    $cssFile = ['ADMIN' => 'adminEditProfile', 'TEACHER' => 'teacherEditProfile'][$role] ?? 'studentEditProfile';
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="UTF-8">
<title>{{ ucfirst(strtolower($role)) }} - Edit Profile</title>
<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="{{ asset('css/'.$cssFile.'.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

	@include('partials.header', ['profileMenu' => true, 'directLogout' => true])

	<div class="container">
        <div class="profile-card">
	        <div class="back-btn-container">
				<a href="{{ route('profile.show') }}" class="back-btn"> <i class="fas fa-arrow-left"></i>BACK</a>
			</div>
			<h1 class="page-title">EDIT PROFILE</h1>
			
			<form id="editProfileForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
				@csrf
				@method('PUT')
				<div class="profile-layout">
					<div class="left-col">
						<div class="large-avatar-container" onclick="triggerUpload()">
					        <img id="profile-pic" src="{{ $displayPic }}" alt="Profile Picture">
					    </div>
					    <p>Click the image to change profile picture.</p>
					    <span id="image-error" class="error-msg"></span>

						<input type="file" id="file-upload" name="profileImage" accept="image/png, image/jpeg, image/jpg" style="display: none;">
					</div>
		
					<div class="right-col">
							
							<div class="form-group">
					            <label>Name :</label> 
					            <span id="name-error" class="error-msg"></span>
				            <input type="text" id="userName" name="userName" value="{{ $user->username }}" class="input-modern" onkeyup="validateName()">
					        </div>
					        
					        @if ($role === 'STUDENT')
					        <div class="form-group">
					            <label>Level :</label>
					            <input type="text" value="{{ $level }}" class="input-modern" disabled>
					        </div>
					        @elseif ($role === 'ADMIN')
					        <div class="form-group">
					            <label>Role :</label>
					            <input type="text" value="{{ $displayRole }}" class="input-modern" disabled>
					        </div>
					        @endif
					
					        <div class="form-group">
								<label>Contact number :</label>
								<div class="column">
								    <span id="phone-error" class="error-msg"></span>
								    <div class="phone-wrapper" id="phone-box">
							            <span class="phone-prefix">+60</span>
							            <input type="hidden" id="realPhone" name="phone">
							            <input type="tel" id="phone" value="{{ $displayPhone }}" placeholder="12-345 6789" maxlength="15" required oninput="formatPhone(this)" onkeyup="validatePhone()"> 
					        		</div>
					        	</div>
							</div>
					
					        <div class="form-group">
								<label>Email :</label>
								<div class="column">
								    <span id="email-error" class="error-msg"></span>
								    <input type="email" id="email" name="email" class="input-modern" value="{{ $user->useremail }}" required onkeyup="validateEmail()">
								</div>
							</div>
					        
					        <button type="submit" class="btn btn-primary submit-btn">Save Changes</button>
					</div>
				</div>
			</form>
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
                <button type="button" class="btn btn-primary" onclick="closePopupAndRedirect()">Done</button>
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
	
	<div id="logoutPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div class="popup-icon-container warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Confirm Security Changes?</h2>
            <p>Changing your <b>Email</b> or <b>Password</b> will log you out. You will need to log in again with your new credentials.</p>
            <div class="popup-buttons">
                <button onclick="closeLogoutPopup()" class="btn btn-neutral">Cancel</button>
                <button onclick="confirmAndSubmit()" class="btn btn-primary">Yes, Proceed</button>
            </div>
        </div>
    </div>
	
<script>
function toggleMenu() { document.getElementById("dropdownMenu").classList.toggle('show'); }
function showLogoutPopup() { document.getElementById('logoutPopup').style.display = 'flex'; }
function showDeletePopup() { document.getElementById('deletePopup').style.display = 'flex'; }
function hidePopup(id) { document.getElementById(id).style.display = 'none'; }

window.addEventListener('click', function(e) {
    const menu = document.getElementById("dropdownMenu");
    const icon = document.querySelector('.profile-link');
    if (!menu.contains(e.target) && !icon.contains(e.target)) {
        menu.classList.remove('show');
    }
});

window.onload = function() {
	const urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('status') === 'success') {
		document.getElementById('successPopup').style.display = 'flex';
	}
	if (urlParams.get('error') === 'invalidName') {
		setInputStatus(document.getElementById('userName'), document.getElementById('name-error'),
			"Name can only contain letters, spaces, apostrophes, and @.");
	}
	if (urlParams.get('error') === 'invalidImage') {
		document.getElementById('image-error').textContent = "Only .jpg, .jpeg, or .png files are allowed.";
		document.getElementById('image-error').classList.add("show");
	}
	if (urlParams.get('error') === 'emailTaken') {
		setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "This email is already used by another account.");
	}
	if (urlParams.get('error') === 'invalidEmail') {
		setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "Please enter a valid email address.");
	}
	if (urlParams.get('error') === 'invalidPhone') {
		setInputStatus(document.getElementById('phone-box'), document.getElementById('phone-error'), "Invalid number. Format: 12-3456 789");
	}
};

function preparePhoneForSubmit() {
	const visibleInput = document.getElementById('phone');
    const hiddenInput = document.getElementById('realPhone');
    let cleanNumber = visibleInput.value.replace(/\D/g, '');
    hiddenInput.value = '0' + cleanNumber;
}

function confirmAndSubmit() {
	preparePhoneForSubmit();
    editForm.submit();
}

function closeLogoutPopup() {
    document.getElementById('logoutPopup').style.display = 'none';
}

function setInputStatus(inputElement, errorElement, msg) {
	if (msg) {
		errorElement.textContent = msg;
		errorElement.classList.add("show");
		inputElement.classList.add("input-error");
		return false;
	} else {
		errorElement.classList.remove("show");
		inputElement.classList.remove("input-error");
		return true;
	}
}

const originalEmail = @json($user->useremail);

let isImageValid = true;
const allowedImageExt = /\.(jpe?g|png)$/i;

function triggerUpload() {
    document.getElementById('file-upload').click();
}
document.getElementById('file-upload').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const errorMsg = document.getElementById('image-error');

    if (file) {
        if (!allowedImageExt.test(file.name)) {
            isImageValid = false;
            errorMsg.textContent = "Only .jpg, .jpeg, or .png files are allowed.";
            errorMsg.classList.add("show");
            event.target.value = "";
            return;
        }

        isImageValid = true;
        errorMsg.classList.remove("show");

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-pic').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

const editForm = document.getElementById('editProfileForm');

editForm.addEventListener('submit', function(event) {
    if (!validateForm()) {
        event.preventDefault();
        return;
    }

    preparePhoneForSubmit();
    const currentEmail = document.getElementsByName('email')[0].value;
    if (currentEmail !== originalEmail) {
        event.preventDefault();
        showLogoutPopup();
    }
});

// FORMATING
function formatPhone(input) {
    let nums = input.value.replace(/\D/g, '');
	if (nums.startsWith('0')) {
        nums = nums.substring(1);
	}

    if (nums.length > 10) {
        nums = nums.substring(0, 10);
	}

    let formatted = nums;
    
    if (nums.length > 2) {
        formatted = nums.substring(0, 2) + '-' + nums.substring(2);
	}

    if (nums.length > 6) {
        formatted = nums.substring(0, 2) + '-' + nums.substring(2, 6) + ' ' + nums.substring(6);
	}
	
    input.value = formatted;
}

function validatePhone() {
    const inputField = document.getElementById('phone');
    const wrapper = document.getElementById('phone-box');
    const errorMsg = document.getElementById('phone-error');
    
    const rawNums = inputField.value.replace(/\D/g, '');
    const isValid = rawNums.length >= 9 && rawNums.length <= 10 && rawNums.startsWith('1');
    if (rawNums.length > 0 && !isValid) {
        errorMsg.textContent = "Invalid number. Format: 12-3456 789";
        errorMsg.classList.add("show");
        wrapper.classList.add("input-error");
        return false;
    } else {
        errorMsg.classList.remove("show");
        wrapper.classList.remove("input-error");
        return true;
    }
}

function validateEmail() {
	const field = document.getElementById('email');
	const errorMsg = document.getElementById('email-error');
	const val = field.value;
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	if (val.length > 0 && !emailRegex.test(val)) {
		return setInputStatus(field, errorMsg, "Please enter a valid email address.");
	} else {
		return setInputStatus(field, errorMsg, null);
	}
}

function validateName() {
	const field = document.getElementById('userName');
	const errorMsg = document.getElementById('name-error');
	const val = field.value.trim();
	const nameRegex = /^[A-Za-z\s'@]+$/;

	if (val.length === 0) {
		return setInputStatus(field, errorMsg, "Name cannot be blank.");
	} else if (!nameRegex.test(val)) {
		return setInputStatus(field, errorMsg,
				"Name can only contain letters, spaces, apostrophes, and @.");
	} else {
		return setInputStatus(field, errorMsg, null);
	}
}

function validateForm() {
	const v1 = validatePhone();
	const v2 = validateEmail();
	const v3 = validateName();
	return v1 && v2 && v3 && isImageValid;
}
</script>
</body>
</html>