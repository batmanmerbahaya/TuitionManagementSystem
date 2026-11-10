
<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
    <title>Reset Password</title> 
    <link rel="stylesheet" href="{{ asset('css/forgotPassword.css') }}"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
</head> 

<body> 

	<div class="toggle-nav login-active">
        <span class="toggle-indicator"></span>
        <a href="{{ route('login') }}" class="toggle-btn active">Login</a>
        <a href="{{ route('signup') }}" class="toggle-btn">Sign Up</a>
    </div>
	
    <div class="split-container">
        
        <a href="{{ route('login') }}" class="back-arrow">&#8592; Back</a>
        
        <div class="left-panel">
            <div class="form-content">
                <h1 class="title-reset">Reset Password</h1>
                <p class="subtitle">Enter the email associated with your account and we'll send you an OTP.</p>
                
                @php $error = request('error'); @endphp
                @php $msg = request('message'); @endphp
                @if ($error || $msg)
                    <div class="alert-box {{ $error ? 'alert-danger' : 'alert-info' }}">
                        <i class="fas {{ $error ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                        <span>{{ $error ?: $msg }}</span>
                    </div>
                @endif
                
                <form action="{{ route('password.otp.send') }}" method="post">
                    @csrf 
                    <input type="hidden" name="action" value="sendOTP">
                    
                    <div class="form-group"> 
                        <div class="input-with-icon">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" name="email" placeholder="Email Address" required> 
                        </div>
                    </div> 

                    <button type="submit" class="reset-btn">SEND OTP</button> 
                </form> 
                
            </div>
        </div>

        <div class="right-panel">
            
            <div class="circle-deco circle-1"></div>
            <div class="circle-deco circle-2"></div>

            <div class="overlay-content">
                <h2>Don't Worry!</h2>
                <p>
                    It happens to the best of us. We will help you recover your account in no time.
                </p>
            </div>
            
            <div class="curve-shape"></div>
        </div>

    </div> 
</body> 
</html>