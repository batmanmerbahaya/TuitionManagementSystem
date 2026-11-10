<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
    <title>Sign Up - Pusat Tuisyen Anjung Ilmu Pendeta</title> 
    <link rel="stylesheet" href="{{ asset('css/UserSignUp.css') }}"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
</head> 

<body> 

    <div class="toggle-nav signup-active">
        <span class="toggle-indicator"></span>
        <a href="{{ route('login') }}" class="toggle-btn">Login</a>
        <a href="{{ route('signup') }}" class="toggle-btn active">Sign Up</a>
    </div>

    <div class="split-container">
    
    <a href="{{ route('landing') }}" class="back-arrow">&#8592; Back</a>
    
        <div class="info-panel">
            <div class="circle-deco circle-1"></div>
            <div class="circle-deco circle-2"></div>

            <div class="overlay-content">
                <h2>Join Us Today!</h2>
                <p>
                    Unlock exclusive access to expert tutors, comprehensive notes, 
                    and a community dedicated to your academic success.
                </p>
                <div class="features-list">
                    <span><i class="fa-solid fa-check-circle"></i> Expert Tutors</span>
                    <span><i class="fa-solid fa-check-circle"></i> Flexible Schedule</span>
                    <span><i class="fa-solid fa-check-circle"></i> Proven Results</span>
                </div>
            </div>
            
            <div class="curve-shape"></div>
        </div>

        <div class="form-panel">
            <div class="form-content">
                <h1 class="title-signup">Create Account</h1>
                <p class="subtitle">Start your learning journey today</p>
                
                <form action="{{ route('signup.store') }}" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                    @csrf

                    <div class="form-group avatar-upload-group">
                        <div class="avatar-upload-container" id="avatar-upload-container" onclick="document.getElementById('profilePic').click()">
                            <img id="profile-pic-preview" src="https://img.freepik.com/free-vector/woman-avatar-profile-round-icon_24640-14047.jpg?w=740" alt="Profile preview">
                            <div class="avatar-upload-overlay"><i class="fa-solid fa-camera"></i></div>
                        </div>
                        <p class="avatar-upload-hint">Click to upload your profile picture (optional)</p>
                        <span id="picture-error" class="error-msg"></span>
                        <input type="file" id="profilePic" name="profilePic" accept="image/*" onchange="validatePicture()" style="display:none;">
                    </div>

                    <div class="form-group">
                        <div class="input-with-icon">
                        	<span id="name-error" class="error-msg"></span>
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="userName" name="userName" placeholder="Full Name" onkeyup="validateName()" onblur="validateName()">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-with-icon">
                       		<span id="email-error" class="error-msg"></span>
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" placeholder="Email Address" required onkeyup="validateEmail()"> 
                        </div>
                    </div> 

                    <div class="form-group">
                        <span id="phone-error" class="error-msg"></span>
                        <div class="phone-wrapper" id="phone-box">
                            <span class="phone-prefix">+60</span>
                            <input type="tel" id="phone" name="phone" placeholder="12-345 6789" maxlength="13" required oninput="formatPhone(this)" onkeyup="validatePhone()">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-with-icon password-box">
                        	<span id="pass-error" class="error-msg"></span>
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="id_password" name="password" placeholder="Password" required onkeyup="validatePass()">
                            <i class="fa-solid fa-eye-slash toggle-password" onclick="toggleField('id_password', this)"></i>
                        </div>
                    </div>
                    
                    <div class="form-group"> 
                        <div class="input-with-icon">
                        <i class="fa-solid fa-graduation-cap input-icon"></i> 
                            <select name="level" required>
                            <option value="" disabled selected>Select Level</option>
                            <option value="FORM 4">Form 4</option>
                            <option value="FORM 5">Form 5</option>
                        </select>
                        <i class="fa-solid fa-chevron-down toggle-password" style="pointer-events: none; font-size: 12px;"></i>
                        </div> 
                    </div>

                    <button type="submit" class="signup-btn">REGISTER</button> 
                </form> 
                
                <div class="mobile-login-text">
                    Already have an account? <a href="{{ route('login') }}">Login</a>
                </div>
            </div>
        </div>

    </div> 
    
<script>
	// EYE ICON
    function toggleField(fieldId, iconElement) { 
        const field = document.getElementById(fieldId);
        if (field.type === "password") { 
            field.type = "text"; 
            iconElement.classList.remove("fa-eye-slash"); 
            iconElement.classList.add("fa-eye"); 
        } else { 
            field.type = "password";
            iconElement.classList.remove("fa-eye"); 
            iconElement.classList.add("fa-eye-slash"); 
        } 
    }

	// INPUT ERROR CHECK
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
    
  	//Auto-format Phone
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

	//Validate Picture (optional - only previews if one is chosen)
	function validatePicture() {
		const field = document.getElementById('profilePic');
		const container = document.getElementById('avatar-upload-container');
		const errorMsg = document.getElementById('picture-error');

		if (field.files.length > 0) {
			const reader = new FileReader();
			reader.onload = function(e) {
				document.getElementById('profile-pic-preview').src = e.target.result;
			};
			reader.readAsDataURL(field.files[0]);
		}
		return setInputStatus(container, errorMsg, null);
	}

	//Validate Name
	function validateName() {
		const field = document.getElementById('userName');
		const errorMsg = document.getElementById('name-error');
		const val = field.value;
		const nameRegex = /^[A-Za-z\s@']*$/;

		if (val.trim().length === 0) {
			return setInputStatus(field, errorMsg, "Name cannot be blank.");
		} else if (!nameRegex.test(val)) {
			return setInputStatus(field, errorMsg,
					"Name can only contain letters, spaces, @ and apostrophes (no numbers).");
		} else {
			return setInputStatus(field, errorMsg, null);
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

	//Final Check
	function validateForm() {
		const v1 = validatePicture();
		const v2 = validateEmail();
		const v3 = validatePhone();
		const v4 = validatePass();
		const v5 = validateName();
		return v1 && v2 && v3 && v4 && v5;
	}

	// Errors sent back by the server (?error=...)
	window.addEventListener('DOMContentLoaded', function() {
		const err = new URLSearchParams(window.location.search).get('error');
		if (err === 'emailTaken') {
			setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "This email is already registered.");
		} else if (err === 'invalidEmail') {
			setInputStatus(document.getElementById('email'), document.getElementById('email-error'), "Please enter a valid email address.");
		} else if (err === 'invalidName') {
			setInputStatus(document.getElementById('userName'), document.getElementById('name-error'), "Name can only contain letters, spaces, @ and apostrophes (no numbers).");
		} else if (err === 'invalidImage') {
			setInputStatus(document.getElementById('avatar-upload-container'), document.getElementById('picture-error'), "Only .jpg, .jpeg, or .png files are allowed.");
		} else if (err === 'invalidPhone') {
			setInputStatus(document.getElementById('phone-box'), document.getElementById('phone-error'), "Invalid number. Format: 12-345 6789");
		} else if (err === 'weakPassword') {
			setInputStatus(document.getElementById('id_password'), document.getElementById('pass-error'), "Password must be at least 8 characters with an uppercase letter, a lowercase letter and a number.");
		} else if (err === 'exception') {
			setInputStatus(document.getElementById('id_password'), document.getElementById('pass-error'), "Registration failed. Please check your details and try again.");
		}
	});

</script>
</body> 
</html>