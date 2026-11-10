{{-- One page for adding an admin, a teacher or a student ($type = admin|teacher|student). --}}
@php
    $label = ['admin' => 'Admin', 'teacher' => 'Teacher', 'student' => 'Student'][$type];
    $indexRoute = 'admin.'.$type.'s.index';
    $storeRoute = 'admin.'.$type.'s.store';
    $navPage = ['admin' => 'adminSubAdmin', 'teacher' => 'adminSubTeacher', 'student' => 'adminSubStudent'][$type];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="UTF-8">
<title>Admin - Add {{ $label }}</title>
<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="{{ asset('css/adminAddAdmin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>

	@include('partials.header', ['currentPage' => $navPage])

	<!-- BODY CONTENT -->
	<div class="container">
		<div class="profile-card">
			<div class="back-btn-container">
				<a href="{{ route($indexRoute) }}" class="back-btn"> <i class="fas fa-arrow-left"></i>Back</a>
			</div>
	
			<h1 class="page-title">ADD {{ strtoupper($label) }}</h1>
	
			<form action="{{ route($storeRoute) }}" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
				@csrf
				<div class="profile-layout">
					<div class="left-col">
						<div class="large-avatar-container" onclick="triggerUpload()">
							<img id="profile-pic"
								src="https://img.freepik.com/free-vector/woman-avatar-profile-round-icon_24640-14047.jpg?w=740"
								alt="{{ $label }} Profile Picture">
						</div>
						<p>Click the profile image to add picture</p>
						<span id="image-error" class="error-msg"></span>

						<input type="file" id="file-upload" name="image" accept="image/png, image/jpeg, image/jpg" style="display: none;">
					</div>
	
					<div class="right-col">
						@if ($type === 'admin')
						<div class="form-group">
							<label>Role :</label>
							<select id="role" name="role">
								<option value="admin">Admin</option>
								<option value="superadmin">Superadmin</option>
							</select>
						</div>
						@elseif ($type === 'student')
						<div class="form-group">
							<label>Level :</label>
							<select id="level" name="level">
								<option value="FORM 4">Form 4</option>
								<option value="FORM 5">Form 5</option>
							</select>
						</div>
						@endif
	
						<div class="form-group">
							<label>Name :</label>
							<div class="column">
								<span id="name-error" class="error-msg"></span>
								<input type="text" id="userName" name="name" class="input-modern" required onkeyup="validateName()">
							</div>
						</div>
	
						<div class="form-group">
						    <label>Contact number :</label>
						    <div class="column">
							    <span id="phone-error" class="error-msg"></span>
							    <div class="phone-wrapper" id="phone-box">
						            <span class="phone-prefix">+60</span>
						            <input type="tel" id="phone" name="phone" 
						                   placeholder="12-345 6789" 
						                   maxlength="13" 
						                   required 
						                   oninput="formatPhone(this)" 
						                   onkeyup="validatePhone()"> 
				        		</div>
				        	</div>
						</div>
						
						<div class="form-group">
						    <label>Email :</label>
						    <div class="column">
							    <span id="email-error" class="error-msg"></span>
							    <input type="email" id="email" name="email" class="input-modern" required onkeyup="validateEmail()">
							</div>
						</div>
						
						<div class="form-group">
						    <label>Password :</label>
						    <div class="column">
							    <span id="pass-error" class="error-msg"></span>
							    <div class="password-container">
							        <input type="password" name="password" class="input-modern" id="id_password" required onkeyup="validatePass()">
							        <i class="far fa-eye password-toggle-icon" id="togglePassword"></i>
							    </div>
							</div>
						</div>
	
						<button type="submit" class="btn btn-primary submit-btn">Add</button>
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
	        <h2>Added Successfully!</h2>
	        <p>{{ $label }} has been added successfully into the system.</p>
	        <div class="popup-buttons">
	        	<button class="btn btn-primary" onclick="closePopupAndRedirect()">Done</button>
	    	</div>
	    </div>
	</div>

	<script>
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
				setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "This email is already registered.");
			}
			if (urlParams.get('error') === 'invalidEmail') {
				setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "Please enter a valid email address.");
			}
			if (urlParams.get('error') === 'invalidPhone') {
				setInputStatus(document.getElementById('phone-box'), document.getElementById('phone-error'), "Invalid number. Format: 12-345 6789");
			}
			if (urlParams.get('error') === 'weakPassword') {
				setInputStatus(document.getElementById('id_password'), document.getElementById('pass-error'), "Password must be at least 8 characters with an uppercase letter, a lowercase letter and a number.");
			}
			if (urlParams.get('status') === 'fail') {
				setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "Could not add the account. Please check the details and try again.");
			}
		};

		function closePopupAndRedirect() {
			window.location.href = '{{ route($indexRoute) }}';
		}

		//File Upload
		function triggerUpload() {
			document.getElementById('file-upload').click();
		}

		let isImageValid = true;
		const allowedImageExt = /\.(jpe?g|png)$/i;

		document
				.getElementById('file-upload')
				.addEventListener(
						'change',
						function(event) {
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

		//Password Eye Toggle
		const togglePassword = document.querySelector('#togglePassword');
		const password = document.querySelector('#id_password');

		togglePassword.addEventListener('click', function(e) {
			const type = password.getAttribute('type') === 'password' ? 'text'
					: 'password';
			password.setAttribute('type', type);
			this.classList.toggle('fa-eye-slash');
			this.classList.toggle('fa-eye');
		});

		//AUTO-FORMATTING FUNCTION
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
		    if (nums.length > 7) {
		        formatted = formatted.substring(0, 7) + ' ' + formatted.substring(7);
		    }
			
		    input.value = formatted;
		}
		
		//Validate Phone
		function validatePhone() {
		    const inputField = document.getElementById('phone');
		    const wrapper = document.getElementById('phone-box');
		    const errorMsg = document.getElementById('phone-error');
		    const rawNums = inputField.value.replace(/\D/g, ''); 
		    const isValid = rawNums.length >= 9 && rawNums.length <= 10 && rawNums.startsWith('1');

		    if (rawNums.length > 0 && !isValid) {
		        errorMsg.textContent = "Invalid number. Format: 12-345 6789";
		        errorMsg.classList.add("show");
		        wrapper.classList.add("input-error");
		        return false;
		    } else {
		        errorMsg.classList.remove("show");
		        wrapper.classList.remove("input-error");
		        return true;
		    }
		}

		//Validate Email
		function validateEmail() {
			const field = document.getElementById('email');
			const errorMsg = document.getElementById('email-error');
			const val = field.value;
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if (val.length > 0 && !emailRegex.test(val)) {
				return setInputStatus(field, errorMsg,
						"Please enter a valid email address.");
			} else {
				return setInputStatus(field, errorMsg, null);
			}
		}

		//Validate Name
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

		//Validate Password
		function validatePass() {
			const field = document.getElementById('id_password');
			const errorMsg = document.getElementById('pass-error');
			const val = field.value;

			const hasUpper = /[A-Z]/.test(val);
			const hasLower = /[a-z]/.test(val);
			const hasNum = /[0-9]/.test(val);
			const hasLen = val.length >= 8;

			if (val.length > 0) {
				if (!hasLen)
					return setInputStatus(field, errorMsg,
							"Password must be at least 8 characters.");
				if (!hasUpper)
					return setInputStatus(field, errorMsg,
							"Password must contain an uppercase letter.");
				if (!hasLower)
					return setInputStatus(field, errorMsg,
							"Password must contain a lowercase letter.");
				if (!hasNum)
					return setInputStatus(field, errorMsg,
							"Password must contain a number.");

				// If all passed
				return setInputStatus(field, errorMsg, null);
			} else {
				return setInputStatus(field, errorMsg, null);
			}
		}

		// Final Check
		function validateForm() {
			const v1 = validatePhone();
			const v2 = validateEmail();
			const v3 = validatePass();
			const v4 = validateName();
			return v1 && v2 && v3 && v4 && isImageValid;
		}
	</script>
</body>
</html>