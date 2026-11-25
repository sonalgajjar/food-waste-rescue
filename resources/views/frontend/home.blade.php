@extends('layouts.frontend')

@section('title', 'Rescuing Food, Feeding Hope')

@section('content')
<!-- Hero Section -->
<section class="hero-section text-center text-lg-start">
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-success bg-opacity-25 text-white border border-success px-3 py-2 rounded-pill mb-3">
                    <i class="fas fa-leaf me-1"></i> Sustainable Food Redistribution Platform
                </span>
                <h1 class="display-4 fw-black text-white mb-3" style="line-height: 1.15;">
                    Turn Food Waste Into Meals For Those In Need
                </h1>
                <p class="lead text-light mb-4 opacity-90">
                    Connecting restaurants, hotels, bakeries, and event caterers directly with verified NGOs to rescue surplus cooked meals and packaged groceries before they expire.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="{{ route('register') }}" class="btn btn-rescue btn-lg shadow-sm">
                        <i class="fas fa-user-plus me-2"></i> Join as Donor / NGO
                    </a>
                    <a href="{{ route('how-it-works') }}" class="btn btn-outline-light btn-lg px-4 rounded-3">
                        <i class="fas fa-play-circle me-2"></i> How It Works
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <div class="p-4 bg-white text-dark rounded-4 shadow-lg border">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-success text-white rounded-circle p-3">
                            <i class="fas fa-utensils fa-2x"></i>
                        </div>
                        <div class="text-start">
                            <h4 class="fw-bold mb-0">Live Rescue Operations</h4>
                            <p class="text-muted small mb-0">Real-time dynamic redistribution</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-success mb-0">{{ number_format($totalMealsRescued) }}</h3>
                                <small class="text-muted fw-semibold">Meals Rescued</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-success mb-0">{{ $activeRestaurantsCount }}</h3>
                                <small class="text-muted fw-semibold">Partner Donors</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Impact Metrics Bar -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="fas fa-box-open fa-2x text-success mb-2"></i>
                    <h2 class="fw-bold text-dark mb-0">{{ number_format($totalDonationsCount) }}</h2>
                    <p class="text-muted small mb-0">Total Food Listings</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="fas fa-hands-helping fa-2x text-success mb-2"></i>
                    <h2 class="fw-bold text-dark mb-0">{{ number_format($activeNgosCount) }}</h2>
                    <p class="text-muted small mb-0">Verified NGOs</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                    <h2 class="fw-bold text-dark mb-0">{{ number_format($totalCO2Saved ?? 0, 1) }} kg</h2>
                    <p class="text-muted small mb-0">CO₂ Emissions Diverted</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                    <h2 class="fw-bold text-dark mb-0">100%</h2>
                    <p class="text-muted small mb-0">Verified Safe Handover</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Available Food Donations -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small">Live Redistribution</span>
                <h2 class="fw-bold mb-0">Available Surplus Food Items</h2>
            </div>
            @auth
                @if(auth()->user()->isNGO())
                    <a href="{{ route('ngo.listings') }}" class="btn btn-outline-success">View All Listings <i class="fas fa-arrow-right ms-1"></i></a>
                @endif
            @endauth
        </div>

        <div class="row g-4">
            @forelse($recentDonations as $donation)
                <div class="col-lg-4 col-md-6">
                    <div class="card card-custom h-100 bg-white">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-3 py-1 rounded-pill">
                                    {{ $donation->category }}
                                </span>
                                <span class="badge bg-success text-white px-2.5 py-1">Available</span>
                            </div>

                            <h5 class="fw-bold mb-2 text-dark">{{ $donation->food_name }}</h5>
                            <p class="text-muted small mb-3 flex-grow-1">
                                {{ Str::limit($donation->description, 90) }}
                            </p>

                            <div class="border-top pt-3 space-y-2">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><i class="fas fa-store me-1 text-success"></i> {{ $donation->restaurant->restaurant_name ?? 'Donor' }}</span>
                                    <span class="fw-bold text-dark">{{ number_format($donation->quantity, 0) }} {{ $donation->unit }}</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><i class="fas fa-clock me-1 text-danger"></i> Expires {{ $donation->expiry_time ? $donation->expiry_time->diffForHumans() : 'Soon' }}</span>
                                    <span><i class="fas fa-map-marker-alt me-1 text-primary"></i> {{ $donation->restaurant->city ?? 'City' }}</span>
                                </div>
                            </div>

                            @auth
                                @if(auth()->user()->isNGO())
                                    <a href="{{ route('ngo.donations.show', $donation->id) }}" class="btn btn-rescue w-100 mt-3">
                                        <i class="fas fa-truck me-1"></i> Request Pickup
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-success w-100 mt-3">
                                    Log In to Claim Food
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 bg-white rounded-3 border">
                        <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-dark">No Available Food Listings Right Now</h5>
                        <p class="text-muted small mb-0">Check back shortly or register as a donor to post surplus food!</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Features / How It Works -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-success fw-bold text-uppercase small">Safe, Transparent & Verified</span>
            <h2 class="fw-bold">How Food Waste Rescue Operates</h2>
            <p class="text-muted">A closed-loop, verifiable rescue ecosystem from kitchen to beneficiary.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 text-center">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="feature-icon mx-auto bg-success text-white mb-3" style="width: 50px; height: 50px; line-height: 50px; border-radius: 50%; font-size: 1.25rem;"><i class="fas fa-plus-circle"></i></div>
                    <h5 class="fw-bold mb-2">1. Post Food</h5>
                    <p class="text-muted small mb-0">Restaurants list surplus meals with categories, preparation time, expiry countdown, and food safety declarations.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 text-center">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="feature-icon mx-auto bg-primary text-white mb-3" style="width: 50px; height: 50px; line-height: 50px; border-radius: 50%; font-size: 1.25rem;"><i class="fas fa-bell"></i></div>
                    <h5 class="fw-bold mb-2">2. Proximity Alerts</h5>
                    <p class="text-muted small mb-0">Active NGOs receive automated proximity notifications and coordinate logistics via direct in-app messaging.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 text-center">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="feature-icon mx-auto bg-warning text-dark mb-3" style="width: 50px; height: 50px; line-height: 50px; border-radius: 50%; font-size: 1.25rem;"><i class="fas fa-qrcode"></i></div>
                    <h5 class="fw-bold mb-2">3. OTP / QR Handover</h5>
                    <p class="text-muted small mb-0">Assigned volunteers scan the secure QR code or enter the 6-digit OTP to guarantee verified physical handover.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 text-center">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="feature-icon mx-auto bg-dark text-white mb-3" style="width: 50px; height: 50px; line-height: 50px; border-radius: 50%; font-size: 1.25rem;"><i class="fas fa-award"></i></div>
                    <h5 class="fw-bold mb-2">4. Verified Certificate</h5>
                    <p class="text-muted small mb-0">Donors receive downloadable tax-acknowledged PDF certificates, CO₂ audit receipts, and two-way ratings.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-success text-white text-center">
    <div class="container py-4">
        <h2 class="display-6 fw-bold mb-3">Join the Mission to Zero Food Waste</h2>
        <p class="lead mb-4 opacity-90 max-w-2xl mx-auto">Whether you're a food business with excess daily production or an NGO feeding communities, Food Waste Rescue is free, fast, and secure.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4 text-success fw-bold shadow-sm">
                <i class="fas fa-user-plus me-2"></i> Register Now
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-sign-in-alt me-2"></i> Partner Sign In
            </a>
        </div>
    </div>
</section>
@endsection
