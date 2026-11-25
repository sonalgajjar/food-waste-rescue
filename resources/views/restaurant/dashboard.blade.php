@extends('layouts.dashboard')

@section('title', 'Restaurant Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Restaurant Dashboard</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Welcome back, {{ $restaurant->restaurant_name }}</h3>
        <p class="text-muted small mb-0">Manage your surplus food donations, review incoming NGO requests, and track your redistribution impact.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('restaurant.donations.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-boxes me-1"></i> Manage Listings
        </a>
        <a href="{{ route('restaurant.donations.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Add Food Donation
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #059669 !important;">
            <div class="stat-value text-success mb-1">{{ number_format($totalDonations) }}</div>
            <div class="stat-label">Total Listings</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #10b981 !important;">
            <div class="stat-value text-primary mb-1">{{ number_format($availableDonations) }}</div>
            <div class="stat-label">Active & Available</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #f59e0b !important;">
            <div class="stat-value text-warning mb-1">{{ number_format($pendingPickupRequests) }}</div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #3b82f6 !important;">
            <div class="stat-value text-info mb-1">{{ number_format($reservedDonations) }}</div>
            <div class="stat-label">Claimed / Approved</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #8b5cf6 !important;">
            <div class="stat-value text-purple mb-1" style="color: #8b5cf6;">{{ number_format($completedDonations) }}</div>
            <div class="stat-label">Completed Pickups</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #ef4444 !important;">
            <div class="stat-value text-danger mb-1">{{ number_format($expiredDonations) }}</div>
            <div class="stat-label">Expired Listings</div>
        </div>
    </div>
</div>

<!-- Chart & Incoming NGO Requests -->
<div class="row g-4 mb-4">
    <!-- Monthly Donations Bar Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-success me-2"></i> Monthly Donations Posted</h6>
                <span class="badge bg-light text-dark border">Past 6 Months</span>
            </div>
            <div class="card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="monthlyDonationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent NGO Requests Column -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bell text-warning me-2"></i> Recent NGO Requests</h6>
                <a href="{{ route('restaurant.history') }}" class="small text-decoration-none text-success">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($recentRequests as $req)
                        <div class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small text-dark">{{ $req->ngo->ngo_name ?? 'NGO' }}</strong>
                                <span class="badge badge-{{ strtolower($req->status) }}">{{ ucfirst($req->status) }}</span>
                            </div>
                            <p class="mb-1 small text-muted">Item: {{ $req->foodDonation->food_name ?? 'Food' }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size: .75rem;"><i class="fas fa-clock me-1"></i> {{ $req->created_at->diffForHumans() }}</small>
                                @if($req->status === 'pending')
                                    <form action="{{ route('restaurant.requests.status', $req->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11px;">Accept</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted small">No recent pickup requests.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Food Donations Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-utensils text-success me-2"></i> My Recent Food Listings</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('restaurant.donations.create') }}" class="btn btn-sm btn-success text-white"><i class="fas fa-plus me-1"></i> Add Food</a>
            <a href="{{ route('restaurant.donations.index') }}" class="btn btn-sm btn-outline-secondary">Manage All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Cooked Time</th>
                        <th>Expiry Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDonations as $d)
                        <tr>
                            <td>
                                <strong class="text-dark d-block">{{ $d->food_name }}</strong>
                                <small class="text-muted">{{ $d->food_type }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $d->category }}</span></td>
                            <td class="fw-medium text-dark">{{ $d->quantity }} {{ $d->unit }}</td>
                            <td class="small text-muted">{{ $d->cooked_time ? $d->cooked_time->format('M d, H:i') : '-' }}</td>
                            <td>
                                <small class="text-danger fw-semibold">
                                    {{ $d->expiry_time ? $d->expiry_time->format('M d, H:i') : '-' }}
                                    @if($d->is_expiring_soon)
                                        <span class="badge bg-danger ms-1">Soon</span>
                                    @endif
                                </small>
                            </td>
                            <td><span class="badge badge-{{ strtolower($d->status) }}">{{ ucfirst($d->status) }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('restaurant.donations.edit', $d->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Donation"><i class="fas fa-edit"></i></a>
                                    <form id="del-donation-{{ $d->id }}" action="{{ route('restaurant.donations.destroy', $d->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('del-donation-{{ $d->id }}')" title="Delete Donation">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No food items listed yet. Click "Add Food Donation" to post surplus food!</td>
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
        const ctx = document.getElementById('monthlyDonationsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Donations Posted',
                    data: {!! json_encode($chartData) !!},
                    backgroundColor: 'rgba(25, 135, 84, 0.75)',
                    borderColor: '#198754',
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
                                return context.parsed.y + ' Donations Listed';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>
@endsection
