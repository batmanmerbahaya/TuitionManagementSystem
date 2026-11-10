<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password | SECURE</title>
<link rel="stylesheet" href="{{ asset('css/resetPassword.css') }}">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

	<div class="toggle-nav login-active">
		<span class="toggle-indicator"></span> <a href="{{ route('login') }}"
			class="toggle-btn active">Login</a> <a href="{{ route('signup') }}"
			class="toggle-btn">Sign Up</a>
	</div>

	<div class="split-container">

	<a href="{{ route('password.otp.form') }}" class="back-arrow">&#8592; Back</a>

		<div class="left-panel">
			<div class="form-content">
				<h1 class="title-reset">NEW PASSWORD</h1>
				<p class="subtitle">Please enter and confirm your new secure password.</p>
				@if (request('error'))
					<p class="subtitle" style="color:#dc2626;">
						{{ request('error') === 'dbError' ? 'No active account was found for this email.' : 'Passwords must match and have at least 8 characters with an uppercase letter, a lowercase letter and a number.' }}
					</p>
				@endif

				<form action="{{ route('password.reset') }}" method="post" id="resetForm">
					@csrf

					<div class="form-group">
						<div class="input-with-icon">
							<i class="fa-solid fa-lock input-icon"></i> 
							<input
								type="password" id="newPass" name="newPass"
								placeholder="Enter new password" required> <i
								class="fa-solid fa-eye-slash toggle-password"
								onclick="togglePassword(this)"></i>
						</div>
					</div>


					<div class="form-group">
						<div class="input-with-icon">
							<i class="fa-solid fa-lock input-icon"></i> 
							<input
								type="password" id="confirmPass" name="confirmPass"
								placeholder="Confirm password" required> <i
								class="fa-solid fa-eye-slash toggle-password"
								onclick="togglePassword(this)"></i>
						</div>
					</div>

					<button type="submit" class="update-btn">UPDATE</button>
				</form>
			</div>
		</div>

		<div class="right-panel">

			<div class="circle-deco circle-1"></div>
			<div class="circle-deco circle-2"></div>

			<div class="overlay-content">
				<h2>Reset Your Password</h2>
				<p>Let’s get you back into your account.</p>
			</div>

			<div class="curve-shape"></div>
		</div>
	</div>

	<script> 
	
	function togglePassword(icon) {
	    const passwordField = icon.previousElementSibling;

	    if (passwordField.type === "password") {
	        passwordField.type = "text";
	        icon.classList.remove("fa-eye-slash");
	        icon.classList.add("fa-eye");
	    } else {
	        passwordField.type = "password";
	        icon.classList.remove("fa-eye");
	        icon.classList.add("fa-eye-slash");
	    }
	}

    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const pass = document.getElementById('newPass').value;
        const confirm = document.getElementById('confirmPass').value;
        
        if(pass !== confirm) {
            e.preventDefault();
            alert("Passwords do not match!");
        } else if (pass.length < 8) {
            e.preventDefault();
            alert("Password must be at least 8 characters long.");
        } else if (!/[A-Z]/.test(pass)) {
            e.preventDefault();
            alert("Password must contain an uppercase letter.");
        } else if (!/[a-z]/.test(pass)) {
            e.preventDefault();
            alert("Password must contain a lowercase letter.");
        } else if (!/[0-9]/.test(pass)) {
            e.preventDefault();
            alert("Password must contain a number.");
        }
    });
    </script>
</body>
</html>