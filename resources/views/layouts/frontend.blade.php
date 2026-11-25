<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FoodRescue') }} — @yield('title', 'Empowering Food Redistribution')</title>
    <meta name="description" content="@yield('meta_description', 'FoodRescue connects restaurants, bakeries, and banquet halls with local NGOs to redistribute surplus food to communities in need.')">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --primary: #198754;
            --primary-dark: #146c43;
            --primary-light: #20c997;
            --primary-bg: #f8f9fa;
            --dark: #1e293b;
            --body-bg: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #334155;
            background-color: var(--body-bg);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--primary) !important;
            letter-spacing: -.5px;
        }

        .nav-link {
            font-weight: 500;
            color: #475569 !important;
            padding: .5rem 1rem !important;
            transition: color .2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        .btn-rescue {
            background-color: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: .6rem 1.4rem;
            border-radius: .5rem;
            border: none;
            transition: all .25 ease;
        }

        .btn-rescue:hover {
            background-color: var(--primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        .hero-section {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%);
            color: #ffffff;
            padding: 5rem 0 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0; left: 0;
            height: 40px;
            background: #ffffff;
            clip-path: polygon(0 100%, 100% 100%, 100% 0);
        }

        .feature-icon {
            width: 60px; height: 60px;
            border-radius: .75rem;
            background: rgba(25, 135, 84, .1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 4rem 0 2rem;
        }

        .footer h5 { color: #ffffff; font-weight: 700; margin-bottom: 1.25rem; }
        .footer a { color: #94a3b8; text-decoration: none; transition: color .2s; }
        .footer a:hover { color: #20c997; }

        .card-custom {
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .card-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <i class="fas fa-seedling"></i>
                </div>
                <span>FoodRescue</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('services') ? 'active' : '' }}" href="{{ route('services') }}">Services</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('how-it-works') ? 'active' : '' }}" href="{{ route('how-it-works') }}">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('faq') ? 'active' : '' }}" href="{{ route('faq') }}">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-rescue">
                            <i class="fas fa-th-large me-1"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-success px-4 rounded-3 fw-semibold">Log In</a>
                        <a href="{{ route('register') }}" class="btn btn-rescue rounded-3">Get Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Body Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <span class="h4 text-white fw-bold mb-0">FoodRescue</span>
                    </div>
                    <p class="small text-secondary mb-3">
                        Bridging food donors and NGOs to eradicate hunger and eliminate food waste across cities. Safe, transparent, real-time redistribution.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled mb-0 small space-y-2">
                        <li class="mb-2"><a href="{{ route('home') }}">Home</a></li>
                        <li class="mb-2"><a href="{{ route('about') }}">About Us</a></li>
                        <li class="mb-2"><a href="{{ route('services') }}">Our Services</a></li>
                        <li class="mb-2"><a href="{{ route('how-it-works') }}">How It Works</a></li>
                        <li class="mb-2"><a href="{{ route('contact') }}">Contact Support</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Legal & Policy</h5>
                    <ul class="list-unstyled mb-0 small space-y-2">
                        <li class="mb-2"><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                        <li class="mb-2"><a href="{{ route('terms') }}">Terms & Conditions</a></li>
                        <li class="mb-2"><a href="{{ route('faq') }}">Frequently Asked Questions</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled mb-0 small text-secondary">
                        <li class="mb-2"><i class="fas fa-map-marker-alt text-success me-2"></i> 404 Rescue Hub, Navrangpura, Ahmedabad, Gujarat 380009</li>
                        <li class="mb-2"><i class="fas fa-envelope text-success me-2"></i> contact@foodrescue.in</li>
                        <li class="mb-2"><i class="fas fa-phone text-success me-2"></i> +91 98765 43210</li>
                    </ul>
                </div>
            </div>

            <hr class="border-secondary opacity-25">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-secondary">
                <div>&copy; {{ date('Y') }} FoodRescue. All rights reserved.</div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({ icon: 'success', title: 'Success!', text: '{{ session("success") }}', timer: 3000, showConfirmButton: false });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: 'Error!', text: '{{ session("error") }}' });
            @endif
        });
    </script>
    @yield('scripts')
</body>
</html>
