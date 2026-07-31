<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mind Craft Island')</title>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-37048786S3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-37048786S3');
    </script>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicons/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('favicons/site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @if(app()->getLocale() === 'ar')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    <header>
        <div class="navbar">
            <a href="{{ route('register') }}" class="brand">
                <img src="{{ asset('favicons/android-chrome-192x192.png') }}" alt="" class="brand-logo">
                Mind Craft <span>Island</span>
            </a>
            <nav>
                <!-- <a href="#" class="nav-link">Programs</a>
                <a href="#" class="nav-link">About Us</a>
                <a href="#" class="btn-support">Support</a> -->
                @yield('nav-links')
                <!-- <a href="#" class="profile-icon" aria-label="Account">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"></path>
                    </svg>
                </a> -->
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="footer-brand-name">Mind Craft <span>Island</span></div>
                <div class="footer-copy">&copy; {{ date('Y') }} Mind Craft Island. All rights reserved.</div>
            </div>
            <!-- <div class="footer-links">
                <a href="#">Contact Us</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div> -->
        </div>
        <a href="https://api.whatsapp.com/send?phone=+96176690011&amp;text=Hello" class="whatsapp-fab" aria-label="Chat on WhatsApp">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor">
                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.51 2 12.04 2zm5.8 14a2.9 2.9 0 0 1-2 1.45c-.53.11-1.22.2-3.55-.76-2.98-1.23-4.9-4.23-5.05-4.43-.15-.2-1.2-1.6-1.2-3.06s.75-2.17 1.02-2.46c.26-.29.58-.36.77-.36h.55c.18 0 .42-.02.65.5.26.6.86 2.05.94 2.2.08.15.13.32.02.52-.1.2-.15.33-.3.5-.15.17-.3.39-.44.52-.15.15-.3.3-.13.6.17.3.75 1.24 1.62 2.01 1.12 1 2.06 1.31 2.36 1.46.3.15.47.13.65-.08.18-.2.75-.87.95-1.17.2-.3.4-.25.66-.15.27.1 1.7.8 1.99.95.3.15.5.22.57.34.07.13.07.72-.16 1.4z"></path>
            </svg>
        </a>
    </footer>

    @stack('scripts')

</body>

</html>