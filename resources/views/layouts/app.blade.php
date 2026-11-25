<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Food Waste Rescue — Connecting restaurants with surplus food to NGOs and communities in need.')">

    <title>{{ config('app.name', 'Food Waste Rescue') }} — @yield('title', 'Save Food, Feed Lives')</title>

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

    <!-- Custom Theme CSS -->
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #10b981;
            --primary-bg: #ecfdf5;
            --secondary: #1e293b;
            --accent: #f59e0b;
            --body-bg: #f8fafb;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --card-shadow-hover: 0 10px 25px rgba(0,0,0,.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* ── Buttons ── */
        .btn-primary, .btn-success {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-primary:hover, .btn-success:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }
        .btn-outline-primary, .btn-outline-success {
            color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-outline-primary:hover, .btn-outline-success:hover {
            background-color: var(--primary) !important;
            color: #fff !important;
        }
        .btn { border-radius: .5rem; font-weight: 500; }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--border-color);
            border-radius: .75rem;
            box-shadow: var(--card-shadow);
            transition: box-shadow .3s ease, transform .2s ease;
        }
        .card:hover { box-shadow: var(--card-shadow-hover); }

        /* ── Navbar ── */
        .navbar-brand { font-weight: 800; letter-spacing: -.5px; }
        .nav-link { font-weight: 500; font-size: .925rem; }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 600px;
            height: 600px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }

        /* ── Stats Cards ── */
        .stat-card {
            border-left: 4px solid var(--primary);
            border-radius: .75rem;
        }
        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* ── Section Headers ── */
        .section-header { margin-bottom: 3rem; }
        .section-header h2 {
            font-weight: 800;
            font-size: 2.25rem;
            color: var(--secondary);
        }
        .section-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: .75rem auto 0;
        }

        /* ── Badge ── */
        .badge-success, .bg-success { background-color: var(--primary) !important; }

        /* ── Footer ── */
        .footer {
            background: var(--secondary);
            color: #94a3b8;
        }
        .footer a { color: #cbd5e1; text-decoration: none; transition: color .2s; }
        .footer a:hover { color: var(--primary-light); }
        .footer h5 { color: #fff; font-weight: 700; font-size: 1rem; letter-spacing: .5px; }

        /* ── Animations ── */
        .fade-in { animation: fadeIn .6s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .hover-lift { transition: transform .2s ease; }
        .hover-lift:hover { transform: translateY(-4px); }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: .4; }

        /* ── Page Header ── */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0d9488 100%);
            padding: 4rem 0;
            color: #fff;
        }
        .page-header h1 { font-weight: 800; }

        /* ── Breadcrumb ── */
        .breadcrumb-item a { color: rgba(255,255,255,.8); text-decoration: none; }
        .breadcrumb-item.active { color: rgba(255,255,255,.6); }
        .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.5); }

        @yield('styles')
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    @include('components.navbar')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('components.footer')

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global Flash Messages via SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({ icon: 'success', title: 'Success!', text: '{{ session("success") }}', timer: 3000, showConfirmButton: false });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: 'Error!', text: '{{ session("error") }}', timer: 4000, showConfirmButton: true });
            @endif
            @if(session('warning'))
                Swal.fire({ icon: 'warning', title: 'Warning', text: '{{ session("warning") }}', timer: 3000, showConfirmButton: false });
            @endif
        });
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
