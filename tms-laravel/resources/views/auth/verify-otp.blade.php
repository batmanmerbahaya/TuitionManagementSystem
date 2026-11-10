<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
    <title>Verify OTP</title> 
    <link rel="stylesheet" href="{{ asset('css/verifyOTP.css') }}"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
</head> 
<body> 
    
    <div class="toggle-nav login-active">
		<span class="toggle-indicator"></span> <a href="{{ route('login') }}"
			class="toggle-btn active">Login</a> <a href="{{ route('signup') }}"
			class="toggle-btn">Sign Up</a>
	</div>
    
    <div class="split-container"> 
        
        <a href="{{ route('password.forgot') }}" class="back-arrow">&#8592; Back</a>
        
        <div class="left-panel">
            <div class="form-content">
                <h1 class="title-verify">Verification</h1> 
                <p class="subtitle">
                    We've sent a 6-digit code to your email.<br>Please enter it below.
                </p>

                @if (request('status') === 'resent')
                    <div class="alert-box alert-success">
                        <i class="fas fa-check-circle"></i> <span>New code sent! Check your inbox.</span>
                    </div>
                @endif

                @php
                    $otpErrors = [
                        'invalid' => 'Invalid or expired OTP.',
                        'locked' => 'Too many wrong codes. Click "Resend Code" to get a new one.',
                        'wait' => 'Please wait 30 seconds before asking for another code.',
                        'tooMany' => 'Too many codes requested. Please try again in 15 minutes.',
                    ];
                @endphp
                @if (isset($otpErrors[request('error')]))
                    <div class="alert-box alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <span>{{ $otpErrors[request('error')] }}</span>
                    </div>
                @endif

                <form action="{{ route('password.otp.verify') }}" method="post">
                    @csrf
                    
                    <div class="form-group"> 
                        <div class="input-with-icon">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="text" name="otp" maxlength="6" 
                                   class="otp-input"
                                   placeholder="000000" required autofocus> 
                        </div>
                    </div> 
                    
                    <button type="submit" class="verify-btn">VERIFY CODE</button> 
                </form>

                <div class="resend-container">
                    <span>Didn't receive the code?</span>
                    <a href="{{ route('password.otp.resend') }}" id="resendBtn" class="resend-link">Resend Code</a>
                </div>
            </div> 
        </div> 

        <div class="right-panel">
            <div class="circle-deco circle-1"></div>
            <div class="circle-deco circle-2"></div>

            <div class="overlay-content">
                <h2>Secure Access</h2>
                <p>
                    Protecting your account is our top priority. 
                    Verify your identity to proceed.
                </p>
            </div>
            
            <div class="curve-shape"></div>
        </div>

    </div> 

    <script>
        // Resend Timer Logic
        document.getElementById('resendBtn').onclick = function(e) {
            // Let the link work, but visual feedback immediately
            const btn = this;
            setTimeout(() => {
                btn.style.pointerEvents = 'none';
                btn.style.color = '#999';
                btn.innerHTML = 'Sent! Wait 30s...';
            }, 100);

            // Re-enable after 30s (Visual only, backend should also handle logic)
            setTimeout(() => {
                btn.style.pointerEvents = 'auto';
                btn.style.color = '#00357a';
                btn.innerHTML = 'Resend Code';
            }, 30000); 
        };
    </script>
</body> 
</html>