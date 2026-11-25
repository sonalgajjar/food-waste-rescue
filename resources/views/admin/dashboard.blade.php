@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Admin Overview</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">System Overview & Analytics</h3>
        <p class="text-muted small mb-0">Platform governance, partner approvals, redistribution metrics, and system activity.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.pdf') }}" target="_blank" class="btn btn-outline-danger shadow-sm btn-sm"><i class="fas fa-file-pdf me-1"></i> PDF Summary</a>
        <a href="{{ route('admin.reports.excel') }}" class="btn btn-outline-success shadow-sm btn-sm"><i class="fas fa-file-excel me-1"></i> Excel Export</a>
    </div>
</div>

<!-- Impact & Sustainability Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-success bg-opacity-10 rounded-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white p-3 rounded-circle">
                    <i class="fas fa-leaf fa-lg"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success mb-0">{{ number_format($totalCo2Kg) }} kg</div>
                    <div class="text-muted small fw-semibold">CO₂ Emissions Diverted</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-primary bg-opacity-10 rounded-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white p-3 rounded-circle">
                    <i class="fas fa-utensils fa-lg"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-primary mb-0">{{ number_format($totalMealsRescued) }}</div>
                    <div class="text-muted small fw-semibold">Total Meals Rescued</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-purple bg-opacity-10 rounded-3" style="background-color: #f3e8ff;">
            <div class="d-flex align-items-center gap-3">
                <div class="text-white p-3 rounded-circle" style="background-color: #8b5cf6;">
                    <i class="fas fa-check-double fa-lg"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0">{{ $completionRate }}%</div>
                    <div class="text-muted small fw-semibold">Pickup Fulfillment Rate</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #059669 !important;">
            <div class="stat-value text-success mb-1">{{ number_format($totalUsers) }}</div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #10b981 !important;">
            <div class="stat-value text-primary mb-1">{{ number_format($totalRestaurants) }}</div>
            <div class="stat-label">Restaurants</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #3b82f6 !important;">
            <div class="stat-value text-info mb-1">{{ number_format($totalNGOs) }}</div>
            <div class="stat-label">Verified NGOs</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #f59e0b !important;">
            <div class="stat-value text-warning mb-1">{{ number_format($totalDonations) }}</div>
            <div class="stat-label">Food Listings</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #8b5cf6 !important;">
            <div class="stat-value text-purple mb-1" style="color: #8b5cf6;">{{ number_format($totalPickups) }}</div>
            <div class="stat-label">Completed Pickups</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card border-0 shadow-sm text-center p-3 h-100" style="border-left-color: #ef4444 !important;">
            <div class="stat-value text-danger mb-1">{{ number_format($pendingApprovalsCount) }}</div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>
</div>

<!-- Chart & Pending Approvals -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-area text-success me-2"></i> Platform Redistribution Trends</h6>
                <span class="badge bg-light text-dark border">Past 6 Months Comparison</span>
            </div>
            <div class="card-body">
                <div style="height: 290px; position: relative;">
                    <canvas id="adminAnalyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-clock text-warning me-2"></i> Pending Approvals</h6>
                <span class="badge bg-warning text-dark">{{ $pendingApprovalsCount }} Pending</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($pendingRestaurants as $r)
                        <div class="list-group-item p-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small d-block text-dark">{{ $r->restaurant_name }}</strong>
                                <span class="badge bg-secondary" style="font-size:10px;">Restaurant</span>
                                <small class="text-muted ms-1">{{ $r->city }}</small>
                            </div>
                            <a href="{{ route('admin.restaurants') }}" class="btn btn-sm btn-outline-success">Review</a>
                        </div>
                    @empty
                    @endforelse

                    @forelse($pendingNGOs as $n)
                        <div class="list-group-item p-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small d-block text-dark">{{ $n->ngo_name }}</strong>
                                <span class="badge bg-info text-dark" style="font-size:10px;">NGO</span>
                                <small class="text-muted ms-1">{{ $n->city }}</small>
                            </div>
                            <a href="{{ route('admin.ngos') }}" class="btn btn-sm btn-outline-success">Review</a>
                        </div>
                    @empty
                    @endforelse

                    @if($pendingApprovalsCount === 0)
                        <div class="p-4 text-center text-muted small">No pending registrations requiring approval.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Breakdown & System Food Donations -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-info me-2"></i> Food Category Mix</h6>
                <span class="badge bg-light text-dark border">By Quantity</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 240px; position: relative;">
                    <canvas id="adminCategoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Platform Food Donations -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-box-open text-primary me-2"></i> Recent Platform Food Donations</h6>
                <a href="{{ route('admin.donations') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Food Item</th>
                                <th>Donor Restaurant</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDonations as $d)
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">{{ $d->food_name }}</strong>
                                        <small class="text-muted">{{ $d->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td><small class="text-dark fw-medium">{{ $d->restaurant->restaurant_name ?? 'N/A' }}</small></td>
                                    <td><span class="badge bg-secondary">{{ $d->category }}</span></td>
                                    <td class="fw-medium text-dark">{{ $d->quantity }} {{ $d->unit }}</td>
                                    <td><span class="badge badge-{{ strtolower($d->status) }}">{{ ucfirst($d->status) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No donations posted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Registration Records -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-users text-success me-2"></i> Recent Registrations</h6>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary">Manage All Users</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($recentUsers as $u)
                        <div class="list-group-item p-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small d-block text-dark">{{ $u->name }}</strong>
                                <small class="text-muted d-block">{{ $u->email }}</small>
                                <span class="badge bg-light text-dark border" style="font-size: 10px;">{{ ucfirst($u->role) }}</span>
                                <small class="text-muted ms-1">{{ $u->city ?? 'Location not set' }}</small>
                            </div>
                            <div>
                                <span class="badge badge-{{ strtolower($u->status) }}">{{ ucfirst($u->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted small">No users registered yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('adminAnalyticsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Donations Posted',
                        data: {!! json_encode($chartDonations) !!},
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    },
                    {
                        label: 'Pickups Completed',
                        data: {!! json_encode($chartPickups) !!},
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Category Mix Donut Chart
        const catCanvas = document.getElementById('adminCategoryChart');
        if (catCanvas) {
            const catCtx = catCanvas.getContext('2d');
            const catLabels = {!! json_encode($categoryLabels) !!};
            const catData = {!! json_encode($categoryCounts) !!};

            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catLabels.length ? catLabels : ['No Data'],
                    datasets: [{
                        data: catData.length ? catData : [1],
                        backgroundColor: [
                            '#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#6366f1', '#6b7280'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    },
                    cutout: '65%'
                }
            });
        }
    });
</script>
@endsection
