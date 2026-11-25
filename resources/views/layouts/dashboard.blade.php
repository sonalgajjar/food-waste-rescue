<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FoodRescue') }} — @yield('title', 'Dashboard')</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Dashboard Theme -->
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #10b981;
            --primary-bg: #ecfdf5;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-active: #059669;
            --sidebar-width: 260px;
            --topbar-height: 64px;
            --body-bg: #f1f5f9;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-dark);
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            z-index: 1050;
            overflow-y: auto;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            text-decoration: none;
        }
        .sidebar-brand .brand-icon {
            width: 40px; height: 40px;
            background: var(--primary);
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
        }
        .sidebar-brand span {
            color: #fff;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -.5px;
        }
        .sidebar-menu { padding: 1rem 0; }
        .sidebar-label {
            padding: .5rem 1.5rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            font-weight: 600;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .7rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            transition: all .2s ease;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover {
            color: #fff;
            background: var(--sidebar-hover);
        }
        .sidebar-link.active {
            color: #fff;
            background: rgba(5,150,105,.15);
            border-left-color: var(--primary);
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: .95rem; }

        /* ── Topbar ── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 1040;
        }

        /* ── Main Content ── */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 1.5rem;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--border-color);
            border-radius: .75rem;
            box-shadow: var(--card-shadow);
            background: #fff;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            padding: 1rem 1.25rem;
        }

        /* ── Stat Cards ── */
        .stat-card {
            border-left: 4px solid var(--primary);
            padding: 1.25rem;
        }
        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.2; }
        .stat-card .stat-label { font-size: .8rem; color: var(--text-muted); font-weight: 500; }

        /* ── Buttons ── */
        .btn-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; }
        .btn-primary:hover { background-color: var(--primary-dark) !important; border-color: var(--primary-dark) !important; }
        .btn-outline-primary { color: var(--primary) !important; border-color: var(--primary) !important; }
        .btn-outline-primary:hover { background-color: var(--primary) !important; color: #fff !important; }
        .btn { border-radius: .5rem; font-weight: 500; }

        /* ── Badges ── */
        .badge { border-radius: .375rem; font-weight: 600; font-size: .75rem; }
        .badge-available, .bg-available { background-color: #059669 !important; color: #fff; }
        .badge-reserved, .bg-reserved { background-color: #f59e0b !important; color: #fff; }
        .badge-picked, .bg-picked { background-color: #3b82f6 !important; color: #fff; }
        .badge-completed, .bg-completed { background-color: #10b981 !important; color: #fff; }
        .badge-expired, .bg-expired { background-color: #ef4444 !important; color: #fff; }
        .badge-cancelled, .bg-cancelled { background-color: #6b7280 !important; color: #fff; }
        .badge-pending, .bg-pending { background-color: #f59e0b !important; color: #fff; }
        .badge-accepted, .bg-accepted { background-color: #3b82f6 !important; color: #fff; }
        .badge-rejected, .bg-rejected { background-color: #ef4444 !important; color: #fff; }
        .badge-active, .bg-active { background-color: #059669 !important; color: #fff; }
        .badge-inactive, .bg-inactive { background-color: #ef4444 !important; color: #fff; }

        /* ── Notification Bell ── */
        .notification-bell { position: relative; }
        .notification-bell .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: .65rem;
            padding: .2rem .45rem;
            border-radius: 50%;
        }

        /* ── Empty State ── */
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: .3; }

        /* ── Breadcrumb ── */
        .breadcrumb { margin-bottom: 0; font-size: .85rem; }
        .breadcrumb-item a { color: var(--primary); text-decoration: none; }

        /* ── Table ── */
        .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); font-weight: 600; }

        /* ── Scrollbar ── */
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #475569; border-radius: 2px; }

        /* ── Hamburger ── */
        .sidebar-toggle { display: none; background: none; border: none; font-size: 1.25rem; color: var(--text-dark); }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .topbar { left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-toggle { display: block; }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1049;
            }
            .sidebar-overlay.show { display: block; }
        }

        @yield('styles')
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('home') }}" class="sidebar-brand">
            <div class="brand-icon"><i class="fas fa-seedling"></i></div>
            <span>FoodRescue</span>
        </a>

        <nav class="sidebar-menu">
            @php $role = auth()->user()->role ?? 'admin'; @endphp

            {{-- ============ ADMIN SIDEBAR ============ --}}
            @if($role === 'admin')
                <div class="sidebar-label">Main</div>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <div class="sidebar-label">Management</div>
                <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="{{ route('admin.restaurants') }}" class="sidebar-link {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
                    <i class="fas fa-utensils"></i> Restaurants
                </a>
                <a href="{{ route('admin.ngos') }}" class="sidebar-link {{ request()->routeIs('admin.ngos') ? 'active' : '' }}">
                    <i class="fas fa-hands-helping"></i> NGOs
                </a>
                <a href="{{ route('admin.donations') }}" class="sidebar-link {{ request()->routeIs('admin.donations') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i> Donations
                </a>
                <a href="{{ route('admin.requests') }}" class="sidebar-link {{ request()->routeIs('admin.requests') ? 'active' : '' }}">
                    <i class="fas fa-truck"></i> Pickup Requests
                </a>
                <a href="{{ route('admin.reviews.index') }}" class="sidebar-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                    <i class="fas fa-star-half-alt"></i> Reviews
                </a>

                <div class="sidebar-label">Communication</div>
                <a href="{{ route('admin.contacts') }}" class="sidebar-link {{ request()->routeIs('admin.contacts') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i> Contact Messages
                </a>

                <div class="sidebar-label">Analytics</div>
                <a href="{{ route('admin.reports') }}" class="sidebar-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>

            {{-- ============ RESTAURANT SIDEBAR ============ --}}
            @elseif($role === 'restaurant')
                <div class="sidebar-label">Main</div>
                <a href="{{ route('restaurant.dashboard') }}" class="sidebar-link {{ request()->routeIs('restaurant.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <div class="sidebar-label">Food Management</div>
                <a href="{{ route('restaurant.donations.index') }}" class="sidebar-link {{ request()->routeIs('restaurant.donations.*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i> My Donations
                </a>
                <a href="{{ route('restaurant.donations.create') }}" class="sidebar-link {{ request()->routeIs('restaurant.donations.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i> Add Donation
                </a>
                <a href="{{ route('restaurant.recurring.index') }}" class="sidebar-link {{ request()->routeIs('restaurant.recurring.*') ? 'active' : '' }}">
                    <i class="fas fa-redo"></i> Recurring Schedules
                </a>
                <a href="{{ route('restaurant.certificates.index') }}" class="sidebar-link {{ request()->routeIs('restaurant.certificates.*') ? 'active' : '' }}">
                    <i class="fas fa-certificate"></i> Certificates
                </a>
                <a href="{{ route('restaurant.history') }}" class="sidebar-link {{ request()->routeIs('restaurant.history') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> History
                </a>

                <div class="sidebar-label">Account</div>
                <a href="{{ route('restaurant.profile') }}" class="sidebar-link {{ request()->routeIs('restaurant.profile') ? 'active' : '' }}">
                    <i class="fas fa-user-cog"></i> Profile
                </a>

            {{-- ============ NGO SIDEBAR ============ --}}
            @elseif($role === 'ngo')
                <div class="sidebar-label">Main</div>
                <a href="{{ route('ngo.dashboard') }}" class="sidebar-link {{ request()->routeIs('ngo.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <div class="sidebar-label">Food Rescue</div>
                <a href="{{ route('ngo.listings') }}" class="sidebar-link {{ request()->routeIs('ngo.listings') ? 'active' : '' }}">
                    <i class="fas fa-search-location"></i> Browse Listings
                </a>
                <a href="{{ route('ngo.history') }}" class="sidebar-link {{ request()->routeIs('ngo.history') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> Pickup History
                </a>

                <div class="sidebar-label">Account</div>
                <a href="{{ route('ngo.profile') }}" class="sidebar-link {{ request()->routeIs('ngo.profile') ? 'active' : '' }}">
                    <i class="fas fa-user-cog"></i> Profile
                </a>
            @endif

            <!-- Logout (all roles) -->
            <div class="sidebar-label mt-3">Session</div>
            <a href="#" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Notification Bell -->
            @php
                $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
            @endphp
            <div class="dropdown notification-bell">
                <a href="#" class="text-dark position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-lg"></i>
                    @if($unreadCount > 0)
                        <span class="badge bg-danger">{{ $unreadCount }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px; max-height: 400px; overflow-y: auto;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Notifications</h6>
                        @if($unreadCount > 0)
                            <form action="{{ route('notifications.read', 0) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="mark_all" value="1">
                                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0">Mark all read</button>
                            </form>
                        @endif
                    </div>
                    @php
                        $notifications = \App\Models\Notification::where('user_id', auth()->id())->latest()->take(10)->get();
                    @endphp
                    @forelse($notifications as $n)
                        <div class="p-3 border-bottom {{ $n->is_read ? '' : 'bg-light' }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-1">
                                    @if(($n->type ?? null) === 'food_alert')
                                        <span class="badge bg-success" style="font-size: .65rem;"><i class="fas fa-utensils me-1"></i>Nearby Food</span>
                                    @endif
                                    <strong class="small text-dark">{{ $n->title }}</strong>
                                </div>
                                <small class="text-muted" style="font-size: .7rem;">{{ $n->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1 small text-muted">{{ Str::limit($n->message, 90) }}</p>
                            @if(!empty($n->link))
                                <div class="mt-1 d-flex justify-content-end">
                                    <a href="{{ $n->link }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: .75rem;">
                                        View Details <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted small">No notifications yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark" data-bs-toggle="dropdown">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;font-size:.85rem;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="d-none d-sm-block">
                        <div class="fw-semibold small">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="text-muted" style="font-size:.7rem;">{{ ucfirst(auth()->user()->role ?? 'User') }}</div>
                    </div>
                    <i class="fas fa-chevron-down ms-1" style="font-size:.7rem;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fas fa-th-large me-2"></i>Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('topbar-logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
                <form id="topbar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (required by DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        if (typeof $.fn.dataTable !== 'undefined') {
            $.fn.dataTable.ext.errMode = 'none';
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Global: Flash messages via SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({ icon: 'success', title: 'Success!', text: '{{ session("success") }}', timer: 3000, showConfirmButton: false });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: 'Error!', text: '{{ session("error") }}' });
            @endif
            @if(session('warning'))
                Swal.fire({ icon: 'warning', title: 'Warning', text: '{{ session("warning") }}', timer: 3000, showConfirmButton: false });
            @endif
        });

        // Sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        // SweetAlert delete confirmation helper
        function confirmDelete(formId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
