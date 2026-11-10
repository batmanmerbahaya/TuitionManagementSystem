<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Tusyen Anjung Ilmu Pendeta</title>
    <link rel="stylesheet" href="{{ asset('css/landingPage.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <a href="#" class="brand-logo">
            <i class="fas fa-graduation-cap"></i> Anjung Ilmu
        </a>
        <div class="nav-links">
            <a href="{{ route('login') }}" class="btn-nav btn-login">Log In</a>
            <a href="{{ route('signup') }}" class="btn-nav btn-signup">Sign Up</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-badge"><i class="fas fa-star"></i> Exclusive for Form 4 & 5 Students</div>
        
        <span class="hero-slogan">Membina Generasi Berilmu</span>
        <h1 class="hero-title">Pusat Tusyen <br> Anjung Ilmu Pendeta</h1>
        <p class="hero-subtitle">
            Specialized SPM preparation for upper secondary students. 
            Join the leading tuition center in Seremban & Senawang designed for excellence.
        </p>
        <a href="{{ route('login') }}" class="btn-cta">Start Your Journey</a>
    </header>

    <section class="section">
        <div class="about-container">
            <div class="about-text">
                <span class="section-label">Our Story</span>
                <h2 class="section-title">Committed to Academic Excellence</h2>
                <p class="about-desc">
                    Founded with a passion for nurturing young minds, <strong>Pusat Tusyen Anjung Ilmu Pendeta</strong> focuses specifically on <strong>Form 4 and Form 5</strong> students. We understand the critical nature of the SPM years and provide targeted strategies to ensure success.
                </p>
                
                <div class="owner-card">
                    <div class="owner-img owner-avatar" aria-hidden="true"><i class="fas fa-user-tie"></i></div>
                    <div class="owner-info">
                        <h4>En. Redha Bin Badly</h4>
                        <p>Founder & Principal</p>
                        <a href="tel:+60193673444"><i class="fas fa-phone-alt"></i> +60 19-367 3444</a>
                    </div>
                </div>
            </div>

            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=2070&auto=format&fit=crop" alt="Tuition Center Class" class="main-img">
                <div class="float-card">
                    <h3>10+</h3>
                    <p>Years of <br>Excellence</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section bg-light">
        <div class="gallery-header">
            <span class="section-label">Find Us</span>
            <h2 class="section-title">Our Locations</h2>
            <p style="color:#666;">We are conveniently located across Seremban and Senawang.</p>
        </div>

        <div class="locations-grid">
            <div class="location-card">
                <div class="loc-title"><i class="fas fa-building"></i> HQ: Pusat Bandar</div>
                <div class="loc-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Anjung Ilmu, Pusat Bandar Seremban</span>
                </div>
                <div class="loc-info">
                    <i class="fas fa-phone"></i>
                    <span>+60 6-761 0649</span>
                </div>
            </div>

            <div class="location-card">
                <div class="loc-title"><i class="fas fa-store"></i> Branch 1: Jusco S2</div>
                <div class="loc-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Anjung Ilmu Noor, Biz Avenue, Seremban 2 (Tingkat 2)</span>
                </div>
                <div class="loc-info">
                    <i class="fas fa-phone"></i>
                    <span>+60 6-601 6098</span>
                </div>
            </div>

            <div class="location-card">
                <div class="loc-title"><i class="fas fa-store"></i> Branch 2: Senawang</div>
                <div class="loc-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Anjung Ilmu Redha, Taipan Senawang</span>
                </div>
                <div class="loc-info">
                    <i class="fas fa-phone"></i>
                    <span>+60 19-367 3444 <br> +60 6-677 5325</span>
                </div>
            </div>

            <div class="location-card">
                <div class="loc-title"><i class="fas fa-store"></i> Branch 3: Sikamat</div>
                <div class="loc-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Anjung Ilmu, Taman Megaway, 628 Jln Kemboja 10</span>
                </div>
                <div class="loc-info">
                    <i class="fas fa-phone"></i>
                    <span>+60 12-661 7259 <br> +60 12-223 9231</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="gallery-header">
            <span class="section-label">Student Life</span>
            <h2 class="section-title">Moments in Class</h2>
        </div>

        <div class="gallery-grid">
            <div class="gallery-item g-item-1">
                <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2132&auto=format&fit=crop" alt="Tusyen 1">
            </div>
            <div class="gallery-item g-item-2">
                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=1600&auto=format&fit=crop" alt="Class in session">
            </div>
            <div class="gallery-item g-item-3">
                <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1200&auto=format&fit=crop" alt="Books on a desk">
            </div>
            <div class="gallery-item g-item-4">
                <img src="{{ asset('images/whiteboard.jpeg') }}" alt="Tusyen 4">
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2>Ready for SPM Success?</h2>
        <p>Join hundreds of Form 4 & 5 students achieving their dreams with us.</p>
        <a href="{{ route('signup') }}" class="btn-cta" style="background-color: white; color: var(--primary-blue);">Register Now</a>
    </section>

    <footer>
        <p>&copy; 2026 Pusat Tusyen Anjung Ilmu Pendeta. All Rights Reserved.</p>
    </footer>

</body>
</html>