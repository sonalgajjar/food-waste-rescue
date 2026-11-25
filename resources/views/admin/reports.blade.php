@extends('layouts.dashboard')

@section('title', 'Reports & Analytics')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Reports & Analytics</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Reports & Export Center</h3>
        <p class="text-muted small mb-0">Generate detailed food waste rescue and operational impact reports.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.pdf') }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf me-1"></i> Generate PDF Report</a>
        <a href="{{ route('admin.reports.excel') }}" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel CSV</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-4 text-center">
            <h2 class="fw-bold text-success mb-1">{{ number_format($donationStats['total']) }}</h2>
            <span class="text-muted small">Total Food Listings</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-4 text-center">
            <h2 class="fw-bold text-primary mb-1">{{ number_format($donationStats['completed']) }}</h2>
            <span class="text-muted small">Completed Pickups</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-4 text-center">
            <h2 class="fw-bold text-info mb-1">{{ number_format($restaurantCount) }}</h2>
            <span class="text-muted small">Active Donors</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-4 text-center">
            <h2 class="fw-bold text-warning mb-1">{{ number_format($ngoCount) }}</h2>
            <span class="text-muted small">Partner NGOs</span>
        </div>
    </div>
</div>
@endsection
