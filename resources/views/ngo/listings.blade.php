@extends('layouts.dashboard')

@section('title', 'Browse Available Surplus Food')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ngo.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Available Food Listings</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Available Food Donations</h3>
        <p class="text-muted small mb-0">Browse and claim fresh surplus meals listed by verified restaurants, bakeries, and caterers.</p>
    </div>
    @if(!$hasLocation)
        <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Set your GPS coordinates in <a href="{{ route('ngo.profile') }}" class="alert-link">NGO Profile</a> to enable distance calculation and nearest food sorting.</span>
        </div>
    @endif
</div>

<!-- Search, Distance & Category Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="{{ route('ngo.listings') }}" method="GET" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-lg-4 col-md-6 col-12">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search food name, restaurant, city...">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <select name="category" class="form-select">
                    <option value="all">All Categories</option>
                    <option value="Veg" {{ request('category') === 'Veg' ? 'selected' : '' }}>Vegetarian</option>
                    <option value="Non-Veg" {{ request('category') === 'Non-Veg' ? 'selected' : '' }}>Non-Veg</option>
                    <option value="Vegan" {{ request('category') === 'Vegan' ? 'selected' : '' }}>Vegan</option>
                    <option value="Dairy" {{ request('category') === 'Dairy' ? 'selected' : '' }}>Dairy</option>
                    <option value="Bakery" {{ request('category') === 'Bakery' ? 'selected' : '' }}>Bakery</option>
                    <option value="Groceries" {{ request('category') === 'Groceries' ? 'selected' : '' }}>Groceries</option>
                </select>
            </div>

            <!-- Distance Filter (5km, 10km, 25km, 50km) -->
            <div class="col-lg-2 col-md-3 col-6">
                <select name="distance" class="form-select" {{ !$hasLocation ? 'disabled' : '' }}>
                    <option value="">Any Distance</option>
                    <option value="5" {{ request('distance') == '5' ? 'selected' : '' }}>Within 5 km</option>
                    <option value="10" {{ request('distance') == '10' ? 'selected' : '' }}>Within 10 km</option>
                    <option value="25" {{ request('distance') == '25' ? 'selected' : '' }}>Within 25 km</option>
                    <option value="50" {{ request('distance') == '50' ? 'selected' : '' }}>Within 50 km</option>
                </select>
            </div>

            <!-- Sort Option -->
            <div class="col-lg-2 col-md-4 col-6">
                <select name="sort" class="form-select">
                    @if($hasLocation)
                        <option value="nearest" {{ request('sort') === 'nearest' ? 'selected' : '' }}>Nearest First</option>
                    @endif
                    <option value="newest" {{ request('sort', (!$hasLocation ? 'newest' : '')) === 'newest' ? 'selected' : '' }}>Newest Listed</option>
                    <option value="expiring" {{ request('sort') === 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="quantity" {{ request('sort') === 'quantity' ? 'selected' : '' }}>Highest Quantity</option>
                </select>
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="col-lg-2 col-md-4 col-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'category', 'distance', 'sort', 'food_type']))
                    <a href="{{ route('ngo.listings') }}" class="btn btn-outline-secondary" title="Clear Filters"><i class="fas fa-redo"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Listings Cards Grid -->
<div class="row g-4">
    @forelse($donations as $donation)
        @php
            $distance = $donation->distance_km ?? $donation->calculateDistanceTo($ngo->latitude, $ngo->longitude);
            $isExpiringSoon = $donation->is_expiring_soon;
        @endphp
        <div class="col-xl-4 col-md-6 col-12">
            <div class="card border-0 shadow-sm h-100 food-card transition-all">
                <!-- Food Image Header -->
                <div class="position-relative" style="height: 190px; overflow: hidden; background: #f8fafc; border-top-left-radius: .5rem; border-top-right-radius: .5rem;">
                    @if($donation->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($donation->image))
                        <img src="{{ asset('storage/' . $donation->image) }}" alt="{{ $donation->food_name }}" class="w-100 h-100 object-fit-cover">
                    @else
                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
                            <i class="fas fa-utensils fa-3x text-success opacity-50 mb-2"></i>
                            <span class="small fw-semibold text-success">{{ $donation->category }} Surplus</span>
                        </div>
                    @endif

                    <!-- Category Badge -->
                    <span class="position-absolute top-0 start-0 m-3 badge bg-white text-dark shadow-sm border px-2.5 py-1 fw-semibold">
                        <i class="fas fa-tag me-1 text-success"></i> {{ $donation->category }}
                    </span>

                    <!-- Availability Status -->
                    @if($donation->status === 'available')
                        <span class="position-absolute top-0 end-0 m-3 badge bg-success text-white shadow-sm px-2.5 py-1">
                            <i class="fas fa-check-circle me-1"></i> Available
                        </span>
                    @else
                        <span class="position-absolute top-0 end-0 m-3 badge bg-secondary text-white shadow-sm px-2.5 py-1">
                            {{ ucfirst($donation->status) }}
                        </span>
                    @endif
                </div>

                <div class="card-body p-4 d-flex flex-column">
                    <!-- Title without unwanted # numbers -->
                    <h5 class="fw-bold text-dark mb-1">{{ $donation->food_name }}</h5>

                    <!-- Restaurant / Donor Info -->
                    <div class="d-flex align-items-center text-muted small mb-2">
                        <i class="fas fa-store text-success me-1.5"></i>
                        <span class="fw-medium text-dark me-2">{{ $donation->restaurant->restaurant_name ?? 'Restaurant Donor' }}</span>
                        <span>• {{ $donation->restaurant->city ?? 'Local' }}</span>
                    </div>

                    <!-- Description -->
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">
                        {{ Str::limit($donation->description ?: 'Fresh, hygienically handled surplus food available for immediate NGO collection.', 95) }}
                    </p>

                    <!-- Details Box -->
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center small mb-1.5">
                            <span class="text-muted"><i class="fas fa-weight-hanging me-1"></i> Quantity:</span>
                            <span class="fw-bold text-dark">{{ $donation->quantity }} {{ $donation->unit }}</span>
                        </div>

                        <!-- Distance in Kilometres -->
                        <div class="d-flex justify-content-between align-items-center small mb-1.5">
                            <span class="text-muted"><i class="fas fa-route me-1 text-primary"></i> Distance:</span>
                            @if($distance !== null)
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                    {{ $distance }} km away
                                </span>
                            @else
                                <span class="text-muted">Location not set</span>
                            @endif
                        </div>

                        <!-- Expiry Information with Expiring Soon indicator -->
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="fas fa-hourglass-half me-1 text-danger"></i> Expiry:</span>
                            @if($isExpiringSoon)
                                <span class="badge bg-danger text-white animate-pulse">
                                    <i class="fas fa-fire me-1"></i> Expiring Soon ({{ $donation->expiry_time->diffForHumans(null, true) }})
                                </span>
                            @elseif($donation->expiry_time)
                                <span class="text-danger fw-semibold">
                                    in {{ $donation->expiry_time->diffForHumans(null, true) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 mt-auto">
                        <a href="{{ route('ngo.donations.show', $donation->id) }}" class="btn btn-outline-secondary flex-grow-1">
                            <i class="fas fa-info-circle me-1"></i> View Details
                        </a>
                        <a href="{{ route('ngo.donations.show', $donation->id) }}#claim" class="btn btn-primary flex-grow-1 shadow-sm">
                            <i class="fas fa-hand-holding-heart me-1"></i> Claim Food
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center">
                <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold">No Matching Surplus Food Listings</h5>
                <p class="text-muted small mb-3">Try widening your search keywords, adjusting the distance radius, or checking back soon.</p>
                <div>
                    <a href="{{ route('ngo.listings') }}" class="btn btn-outline-primary btn-sm">Clear All Filters</a>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $donations->appends(request()->query())->links() }}
</div>
@endsection
