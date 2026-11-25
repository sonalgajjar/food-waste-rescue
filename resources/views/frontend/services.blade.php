@extends('layouts.frontend')

@section('title', 'Services — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">Our Services & Solutions</h1>
        <p class="lead opacity-90 mb-0">Designed specifically for Restaurants, Hotels, Caterers, and Non-Profit Relief Organizations.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100 bg-white p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-utensils"></i></div>
                    <h4 class="fw-bold mb-2">For Restaurants & Donors</h4>
                    <ul class="list-unstyled text-muted small space-y-2 mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Instant Surplus Listing Entry</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Automated Freshness & Expiry Timer</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Map Pickup Pinpoint Location</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Pickup Request Accept/Reject Controls</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Corporate Social Responsibility Analytics</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 bg-white p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-hands-helping"></i></div>
                    <h4 class="fw-bold mb-2">For Verified NGOs</h4>
                    <ul class="list-unstyled text-muted small space-y-2 mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> OpenStreetMap Geolocation Search</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Category & Distance Radius Filtering</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Real-time Push & In-App Alerts</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> One-Click Claim & Dispatch Scheduling</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Complete Request & Delivery Logs</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 bg-white p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-shield-alt"></i></div>
                    <h4 class="fw-bold mb-2">System Governance</h4>
                    <ul class="list-unstyled text-muted small space-y-2 mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> FSSAI & NGO Verification Review</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Automated Safety Policy Enforcement</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> PDF & Excel Report Exports</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Detailed System Health Metrics</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Role-Based Access Control</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
