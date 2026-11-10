
<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
    <title>Login Page</title> 
    <link rel="stylesheet" href="{{ asset('css/UserLogin.css') }}"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
</head> 

<body> 

    <div class="toggle-nav login-active">
        <span class="toggle-indicator"></span>
        <a href="{{ route('login') }}" class="toggle-btn active">Login</a>
        <a href="{{ route('signup') }}" class="toggle-btn">Sign Up</a>
    </div>

    <div class="split-container">
        
        <a href="{{ route('landing') }}" class="back-arrow">&#8592; Back</a>
        
        <div class="left-panel">
            <div class="form-content">
                <h1 class="title-login">Hello!</h1>
                <p class="subtitle">Sign in to your account</p>
                
                @php
                    $status = request('status');
                    $previousEmail = request('email');
                    $isDanger = in_array($status, ['deleted', 'invalid', 'throttled'], true);
                    $messages = [
                        'deleted' => '<b>Account Deleted:</b> Your account is no longer active.',
                        'logout' => '<b>Signed Out:</b> You have successfully logged out.',
                        'securityChanged' => '<b>Security Update:</b> Please log in again.',
                        'registered' => '<b>Welcome!</b> Account created. Please log in.',
                        'passwordChanged' => '<b>Success:</b> Password reset. Please log in.',
                        'invalid' => '<b>Login Failed:</b> Invalid email or password.',
                        'throttled' => '<b>Too Many Attempts:</b> Please wait '.max(1, (int) request('wait', 5)).' minute(s) and try again.',
                    ];
                @endphp
                @if ($status)
                    <div class="alert-box {{ $isDanger ? 'alert-danger' : 'alert-info' }}">
                        <div class="alert-icon">
                            <i class="fas {{ $isDanger ? 'fa-exclamation-triangle' : 'fa-info-circle' }}"></i>
                        </div>

                        <div class="alert-message">{!! $messages[$status] ?? '' !!}</div>
                    </div>
                @endif
                
                
                <form action="{{ route('login.attempt') }}" method="post">
                    @csrf
                
                    <div class="form-group"> 
                        <div class="input-with-icon">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" name="email" placeholder="Email Address" required
                                value="{{ $previousEmail }}"
                                class="{{ $status === 'invalid' ? 'input-error' : '' }}"> 
                        </div>
                    </div> 

                    <div class="form-group"> 
                        <div class="input-with-icon password-box"> 
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Password" required
                                class="{{ $status === 'invalid' ? 'input-error' : '' }}"> 
                            <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePassword()"></i> 
                        </div> 
                    </div>

                    <div class="form-options">
                        <a href="{{ route('password.forgot') }}" class="forgot-link">Forgot Password?</a>
                    </div>
     
                    <button type="submit" class="login-btn">SIGN IN</button> 
                </form> 
                
                <div class="mobile-signup-text">
                    Don’t have an account? <a href="{{ route('signup') }}">Create Account</a>
                </div>
            </div>
        </div>

        <div class="right-panel">
            
            <div class="circle-deco circle-1"></div>
            <div class="circle-deco circle-2"></div>

            <div class="overlay-content">
                <h2>Welcome Back!</h2>
                <p>Log in to continue your journey with Pusat Tuisyen Anjung Ilmu Pendeta.</p>
            </div>
            
            <div class="curve-shape"></div>
        </div>
    </div> 
    
    <script> 
    function togglePassword() { 
        const passwordField = document.getElementById("password");
        const icon = document.querySelector(".toggle-password"); 
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
    </script> 
</body> 
</html>