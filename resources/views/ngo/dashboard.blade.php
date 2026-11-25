@extends('layouts.dashboard')

@section('title', 'NGO Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">NGO Dashboard</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Welcome back, {{ $ngo->ngo_name }}</h3>
        <p class="text-muted small mb-0">Coordinate food rescue logistics, monitor active requests, and view nearby available surplus food.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('ngo.history') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-history me-1"></i> Pickup History
        </a>
        <a href="{{ route('ngo.listings') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-search-location me-1"></i> Browse Available Food
        </a>
    </div>
</div>

@if(isset($recentFoodAlerts) && $recentFoodAlerts->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4 border-start border-success border-4" style="background: #f0fdf4;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-20 d-flex align-items-center justify-content-center text-success" style="width: 42px; height: 42px; font-size: 1.1rem;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success">Nearby Alert</span>
                            <strong class="text-dark">{{ $recentFoodAlerts->first()->title }}</strong>
                            <small class="text-muted">{{ $recentFoodAlerts->first()->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-0 small text-secondary mt-1">{{ $recentFoodAlerts->first()->message }}</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if($recentFoodAlerts->first()->link)
                        <a href="{{ $recentFoodAlerts->first()->link }}" class="btn btn-sm btn-success text-white">
                            <i class="fas fa-hand-holding-heart me-1"></i> View & Claim
                        </a>
                    @endif
                    <a href="{{ route('ngo.listings') }}" class="btn btn-sm btn-outline-success">
                        All Listings
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #059669 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-success">{{ number_format($nearbyDonationsCount) }}</div>
                    <div class="stat-label">Available Food Listings</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #f59e0b !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-warning">{{ number_format($pendingRequests) }}</div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #3b82f6 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-primary">{{ number_format($acceptedRequests) }}</div>
                    <div class="stat-label">Approved Pickups</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #10b981 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-dark">{{ number_format($completedPickups) }}</div>
                    <div class="stat-label">Completed Pickups</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #8b5cf6 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-purple" style="color: #8b5cf6;">{{ number_format($totalMealsRescued) }}</div>
                    <div class="stat-label">Meals Rescued (Total)</div>
                </div>
                <div class="stat-icon bg-opacity-10 text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fas fa-hands-helping"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Chart & Live Map -->
<div class="row g-4 mb-4">
    <!-- Monthly Pickup Chart (Rendered with real data) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-success me-2"></i> Monthly Pickup Activity</h6>
                <span class="badge bg-light text-dark border">Past 6 Months</span>
            </div>
            <div class="card-body">
                <div style="height: 300px; position: relative;">
                    <canvas id="ngoMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Map -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i> Nearby Available Food Map</h6>
                <span class="badge bg-success">Live Rediscovery</span>
            </div>
            <div class="card-body p-0">
                <div id="ngoDashboardMap" style="height: 340px; width: 100%; border-bottom-left-radius: .5rem; border-bottom-right-radius: .5rem;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Pickup Requests Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-history text-primary me-2"></i> Recent Pickup Requests</h6>
        <a href="{{ route('ngo.history') }}" class="btn btn-sm btn-outline-success">View Full History</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Food Item</th>
                        <th>Donor Restaurant</th>
                        <th>Location</th>
                        <th>Scheduled Pickup</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                        <tr>
                            <td>
                                <strong class="d-block text-dark">{{ $req->foodDonation->food_name ?? 'Surplus Food' }}</strong>
                                <small class="text-muted">{{ $req->foodDonation->quantity ?? '-' }} {{ $req->foodDonation->unit ?? '' }} ({{ $req->foodDonation->category ?? '' }})</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $req->foodDonation->restaurant->restaurant_name ?? 'Restaurant' }}</div>
                                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i> {{ $req->foodDonation->restaurant->phone ?? '-' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $req->foodDonation->restaurant->city ?? '-' }}</small>
                            </td>
                            <td>
                                <small class="text-dark fw-medium">
                                    <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                    {{ $req->pickup_time ? $req->pickup_time->format('M d, Y H:i') : '-' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-{{ strtolower($req->status) }} px-2.5 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($req->status === 'pending')
                                    <form action="{{ route('ngo.requests.cancel', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this pickup request?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Request">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </button>
                                    </form>
                                @elseif($req->status === 'accepted')
                                    <form action="{{ route('handover.start', $req->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Notify restaurant you are on your way">
                                            <i class="fas fa-truck me-1"></i> Start Pickup
                                        </button>
                                    </form>
                                @elseif($req->status === 'out_for_pickup')
                                    <a href="{{ route('ngo.history') }}" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm" title="Verify OTP with donor">
                                        <i class="fas fa-key me-1"></i> Enter OTP
                                    </a>
                                @elseif(in_array($req->status, ['handover_confirmed', 'picked']))
                                    <form action="{{ route('handover.complete', $req->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Confirm distribution">
                                            <i class="fas fa-check-double me-1"></i> Complete
                                        </button>
                                    </form>
                                @elseif($req->status === 'completed')
                                    @if($req->certificate)
                                        <a href="{{ route('certificates.download', $req->certificate->id) }}" class="btn btn-sm btn-outline-success" title="Download Official Certificate">
                                            <i class="fas fa-certificate me-1"></i> Certificate
                                        </a>
                                    @endif
                                @endif
                                @if($req->foodDonation)
                                    <a href="{{ route('ngo.donations.show', $req->foodDonation->id) }}" class="btn btn-sm btn-outline-secondary" title="View Food Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No pickup requests created yet. <a href="{{ route('ngo.listings') }}" class="text-success text-decoration-none fw-semibold">Browse available surplus food</a> to start rescuing meals!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Render Monthly Pickups Chart
        const chartCanvas = document.getElementById('ngoMonthlyChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            const labels = {!! json_encode($chartLabels) !!};
            const dataValues = {!! json_encode($chartData) !!};

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Food Rescues / Pickups',
                        data: dataValues,
                        backgroundColor: 'rgba(5, 150, 105, 0.75)',
                        borderColor: '#059669',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 38
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Pickups Completed';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Render Live Leaflet Map
        const mapElement = document.getElementById('ngoDashboardMap');
        if (mapElement && typeof L !== 'undefined') {
            const ngoLat = {{ $ngo->latitude ?? 28.6180 }};
            const ngoLng = {{ $ngo->longitude ?? 77.2110 }};

            const map = L.map('ngoDashboardMap').setView([ngoLat, ngoLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // NGO Location Marker (Blue)
            const blueIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            L.marker([ngoLat, ngoLng], { icon: blueIcon })
                .addTo(map)
                .bindPopup('<b>{{ addslashes($ngo->ngo_name) }}</b><br><small class="text-muted">Your NGO Headquarters</small>');

            // Available Food Markers (Green)
            const greenIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            const mapDonations = {!! json_encode($mapDonations) !!};
            if (Array.isArray(mapDonations)) {
                mapDonations.forEach(item => {
                    if (item.latitude && item.longitude) {
                        const restName = item.restaurant ? item.restaurant.restaurant_name : 'Restaurant Donor';
                        L.marker([item.latitude, item.longitude], { icon: greenIcon })
                            .addTo(map)
                            .bindPopup(`
                                <div style="min-width: 170px;">
                                    <strong style="color: #059669; font-size: 13px;">${item.food_name}</strong><br>
                                    <small class="text-muted d-block mb-1">${restName}</small>
                                    <span class="badge bg-secondary mb-1">${item.category}</span>
                                    <div class="small fw-semibold mb-2">${item.quantity} ${item.unit}</div>
                                    <a href="/ngo/donations/${item.id}" class="btn btn-sm btn-success text-white w-100 py-1" style="font-size: 11px;">
                                        View & Claim Food
                                    </a>
                                </div>
                            `);
                    }
                });
            }
        }
    });
</script>
@endsection
