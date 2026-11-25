@extends('layouts.dashboard')

@section('title', $donation->food_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ngo.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('ngo.listings') }}">Listings</a></li>
    <li class="breadcrumb-item active">{{ Str::limit($donation->food_name, 25) }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-3 py-1 rounded-pill mb-2">
                            {{ $donation->category }} — {{ $donation->food_type }}
                        </span>
                        <h3 class="fw-bold mb-1 text-dark">{{ $donation->food_name }}</h3>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-store me-1 text-success"></i> Offered by <strong>{{ $donation->restaurant->restaurant_name ?? 'Restaurant' }}</strong>
                            • <i class="fas fa-map-marker-alt ms-1 text-primary"></i> {{ $donation->restaurant->city ?? 'Local' }}
                        </p>
                    </div>
                    <span class="badge badge-{{ strtolower($donation->status) }} fs-6 px-3 py-2">{{ ucfirst($donation->status) }}</span>
                </div>

                <hr>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        @if($donation->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($donation->image))
                            <div class="mb-3 rounded-3 overflow-hidden shadow-sm" style="max-height: 220px;">
                                <img src="{{ asset('storage/' . $donation->image) }}" alt="{{ $donation->food_name }}" class="w-100 h-100 object-fit-cover">
                            </div>
                        @endif

                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i> Food & Rescue Details</h6>
                        <table class="table table-sm border-0 mb-3">
                            <tr>
                                <th class="text-muted" style="width:140px;">Quantity Available:</th>
                                <td class="fw-bold text-dark fs-6">{{ $donation->quantity }} {{ $donation->unit }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Distance to NGO:</th>
                                <td>
                                    @if($distanceKm !== null)
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold fs-7">
                                            <i class="fas fa-route me-1"></i> {{ $distanceKm }} km away
                                        </span>
                                    @else
                                        <span class="text-muted small">Location not specified</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Cooked Date:</th>
                                <td>{{ $donation->cooked_time ? $donation->cooked_time->format('M d, Y H:i') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Expiry Time:</th>
                                <td class="text-danger fw-semibold">
                                    {{ $donation->expiry_time ? $donation->expiry_time->format('M d, Y H:i') : '-' }}
                                    @if($donation->is_expiring_soon)
                                        <span class="badge bg-danger ms-1">Expiring Soon</span>
                                    @endif
                                    <div id="liveCountdown" class="small text-danger fw-bold mt-1"></div>
                                </td>
                            </tr>
                            @if($donation->storage_instructions)
                            <tr>
                                <th class="text-muted">Storage:</th>
                                <td><span class="badge bg-info bg-opacity-10 text-info fw-semibold">{{ $donation->storage_instructions }}</span></td>
                            </tr>
                            @endif
                            @if($donation->packaging_condition)
                            <tr>
                                <th class="text-muted">Packaging:</th>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold">{{ $donation->packaging_condition }}</span></td>
                            </tr>
                            @endif
                            @if($donation->allergen_info)
                            <tr>
                                <th class="text-muted">Allergens:</th>
                                <td><span class="badge bg-warning bg-opacity-10 text-dark fw-semibold"><i class="fas fa-exclamation-triangle text-warning me-1"></i> {{ $donation->allergen_info }}</span></td>
                            </tr>
                            @endif
                            @if($donation->pickup_instructions)
                            <tr>
                                <th class="text-muted">Pickup Note:</th>
                                <td class="text-dark small">{{ $donation->pickup_instructions }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-muted">Pickup Address:</th>
                                <td class="text-dark">{{ $donation->pickup_address }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Donor Phone:</th>
                                <td>
                                    <a href="tel:{{ $donation->restaurant->phone ?? '' }}" class="text-decoration-none text-dark fw-medium">
                                        <i class="fas fa-phone-alt text-success me-1"></i> {{ $donation->restaurant->phone ?? 'Contact on request' }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        @if($donation->description)
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-semibold small text-muted mb-1">Donor Notes & Instructions:</h6>
                                <p class="mb-0 small text-secondary">{{ $donation->description }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3"><i class="fas fa-map-marker-alt text-danger me-2"></i> Pickup Pinpoint Location</h6>
                        <div id="showDonationMap" style="height: 280px; border-radius: .5rem;" class="border mb-3"></div>
                        <div class="p-3 bg-light rounded-3 small text-muted">
                            <i class="fas fa-shield-alt text-success me-1"></i> <strong>Safe Food Guarantee:</strong> All food listed is preserved under strict hygiene guidelines. Please verify freshness upon physical collection.
                        </div>
                    </div>
                </div>

                @if($existingRequest)
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                        <h6 class="fw-bold mb-2 small text-muted text-uppercase" style="letter-spacing: .5px;">Pickup Progress Stepper</h6>
                        <x-pickup-stepper :request="$existingRequest" />
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-between align-items-center border-top pt-3 gap-2">
                    <a href="{{ route('ngo.listings') }}" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back to Listings</a>

                    @if($existingRequest)
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('chat.show', $existingRequest->id) }}" class="btn btn-outline-primary">
                                <i class="fas fa-comment-dots me-1"></i> Open Chat
                            </a>
                            <a href="{{ route('ngo.history') }}" class="btn btn-success text-white">
                                <i class="fas fa-history me-1"></i> Track in History
                            </a>
                        </div>
                    @elseif($donation->status === 'available')
                        <button type="button" class="btn btn-primary btn-lg px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="fas fa-hand-holding-heart me-1"></i> Request Pickup Now
                        </button>
                    @else
                        <button disabled class="btn btn-secondary btn-lg px-4">Currently Unavailable</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pickup Request Modal -->
@if($donation->status === 'available' && !$existingRequest)
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-truck me-2"></i> Schedule Food Pickup</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ngo.requests.store', $donation->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Pickup Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="pickup_time" class="form-control" value="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required min="{{ now()->format('Y-m-d\TH:i') }}" max="{{ $donation->expiry_time ? $donation->expiry_time->format('Y-m-d\TH:i') : '' }}">
                        <div class="form-text small">Please schedule before food expiry: {{ $donation->expiry_time ? $donation->expiry_time->format('M d, H:i') : '' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Volunteer Remarks / Vehicle Details</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="e.g. Dispatched rescue van with insulated containers. Volunteer Driver contact: 9876543210"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold">Confirm Pickup Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $donation->latitude ?? $donation->restaurant->latitude ?? 28.6139 }};
        const lng = {{ $donation->longitude ?? $donation->restaurant->longitude ?? 77.2090 }};

        const map = L.map('showDonationMap').setView([lat, lng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup('<b>{{ addslashes($donation->restaurant->restaurant_name ?? "Pickup Point") }}</b><br>{{ addslashes($donation->pickup_address) }}')
            .openPopup();

        // Auto open modal if user clicked "Claim Food" on listing card
        if (window.location.hash === '#claim') {
            const modalEl = document.getElementById('requestModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        }

        // Live Expiry Countdown
        @if($donation->expiry_time)
            const expiryDate = new Date("{{ $donation->expiry_time->toIso8601String() }}").getTime();
            const countdownEl = document.getElementById('liveCountdown');

            function updateCountdown() {
                const now = new Date().getTime();
                const diff = expiryDate - now;

                if (diff <= 0) {
                    if (countdownEl) countdownEl.innerHTML = '<span class="badge bg-danger">EXPIRED</span>';
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                if (countdownEl) {
                    countdownEl.innerHTML = `<i class="fas fa-hourglass-half me-1"></i> ${hours}h ${minutes}m ${seconds}s remaining`;
                }
            }
            updateCountdown();
            setInterval(updateCountdown, 1000);
        @endif
    });
</script>
@endsection
