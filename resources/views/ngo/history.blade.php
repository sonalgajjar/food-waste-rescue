@extends('layouts.dashboard')

@section('title', 'NGO Pickup History & Tracking')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ngo.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pickup History</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Pickup Coordination & History</h3>
        <p class="text-muted small mb-0">Manage active food rescue pickups, communicate with food donors, verify handovers with OTP, and submit feedback.</p>
    </div>
    <a href="{{ route('ngo.listings') }}" class="btn btn-primary shadow-sm">
        <i class="fas fa-search-location me-1"></i> Browse More Food
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Requested Item</th>
                        <th>Donor Restaurant</th>
                        <th>Pickup Address</th>
                        <th>Progress Stepper</th>
                        <th>Status</th>
                        <th class="text-end">Actions & Verification</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $index => $req)
                        @php
                            $hasReviewed = $req->reviews->where('reviewer_role', 'ngo')->isNotEmpty();
                            $unreadChatCount = $req->messages->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
                        @endphp
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td style="min-width: 170px;">
                                <strong class="text-dark d-block">{{ $req->foodDonation->food_name ?? 'Food Item' }}</strong>
                                <small class="text-muted">
                                    {{ $req->foodDonation->quantity ?? '' }} {{ $req->foodDonation->unit ?? '' }}
                                    @if($req->foodDonation)
                                        • <span class="badge bg-secondary" style="font-size: 10px;">{{ $req->foodDonation->category }}</span>
                                    @endif
                                </small>
                                <small class="d-block text-muted mt-1"><i class="fas fa-clock me-1"></i>{{ $req->pickup_time ? $req->pickup_time->format('M d, H:i') : '-' }}</small>
                            </td>
                            <td style="min-width: 150px;">
                                <div class="fw-semibold text-dark">{{ $req->foodDonation->restaurant->restaurant_name ?? 'Restaurant' }}</div>
                                <div class="small text-muted"><i class="fas fa-phone-alt me-1 text-success"></i>{{ $req->foodDonation->restaurant->phone ?? '-' }}</div>
                            </td>
                            <td style="min-width: 180px;">
                                <small class="text-muted d-block">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                    {{ $req->foodDonation->pickup_address ?? '-' }}
                                </small>
                            </td>
                            <td style="min-width: 320px;">
                                <x-pickup-stepper :request="$req" />
                            </td>
                            <td>
                                <span class="badge badge-{{ strtolower($req->status) }} px-2.5 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td class="text-end" style="min-width: 220px;">
                                <div class="d-inline-flex flex-wrap gap-1 justify-content-end align-items-center">
                                    {{-- Chat button --}}
                                    <a href="{{ route('chat.show', $req->id) }}" class="btn btn-sm btn-outline-primary position-relative" title="Coordination Chat">
                                        <i class="fas fa-comment-dots me-1"></i> Chat
                                        @if($unreadChatCount > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .65rem;">
                                                {{ $unreadChatCount }}
                                            </span>
                                        @endif
                                    </a>

                                    @if($req->status === 'pending')
                                        <form action="{{ route('ngo.requests.cancel', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this pickup request?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </button>
                                        </form>

                                    @elseif($req->status === 'accepted')
                                        {{-- Start Pickup Journey --}}
                                        <form action="{{ route('handover.start', $req->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Notify restaurant you are on your way">
                                                <i class="fas fa-truck me-1"></i> Start Pickup
                                            </button>
                                        </form>

                                    @elseif($req->status === 'out_for_pickup')
                                        {{-- Enter Handover OTP modal trigger --}}
                                        <button type="button" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm" onclick="openOtpVerifyModal({{ $req->id }}, '{{ addslashes($req->foodDonation->restaurant->restaurant_name ?? '') }}')">
                                            <i class="fas fa-key me-1"></i> Enter Handover OTP
                                        </button>

                                    @elseif(in_array($req->status, ['handover_confirmed', 'picked']))
                                        {{-- Final Complete --}}
                                        <form action="{{ route('handover.complete', $req->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Confirm distribution and generate certificate">
                                                <i class="fas fa-check-double me-1"></i> Complete
                                            </button>
                                        </form>

                                    @elseif($req->status === 'completed')
                                        @if($req->certificate)
                                            <a href="{{ route('certificates.download', $req->certificate->id) }}" class="btn btn-sm btn-outline-success" title="Download Official Certificate">
                                                <i class="fas fa-certificate me-1"></i> Certificate
                                            </a>
                                        @endif

                                        @if(!$hasReviewed)
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="openReviewModal({{ $req->id }}, '{{ addslashes($req->foodDonation->restaurant->restaurant_name ?? 'Restaurant') }}')">
                                                <i class="fas fa-star me-1"></i> Rate Donor
                                            </button>
                                        @else
                                            <span class="badge bg-light text-warning border"><i class="fas fa-star me-1"></i> Rated</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-hands-helping fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6>No pickup history recorded yet</h6>
                                <p class="small mb-0">Browse nearby available surplus food listings and submit pickup requests to start rescuing meals!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white p-3 border-top">
            {{ $requests->links() }}
        </div>
    @endif
</div>

<!-- Enter Handover OTP Modal (For NGO volunteer to submit restaurant's OTP) -->
<div class="modal fade" id="otpVerifyModal" tabindex="-1" aria-labelledby="otpVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-3 border-0">
                <h5 class="modal-title fw-bold" id="otpVerifyModalLabel">
                    <i class="fas fa-key me-2"></i> Enter Handover OTP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="otpVerifyForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4 text-center">
                    <p class="text-muted small mb-3">
                        Ask <strong id="otpRestaurantName" class="text-dark">the Restaurant Staff</strong> for their 6-digit Handover OTP and enter it below:
                    </p>

                    <div class="mb-3">
                        <input type="text" name="otp" id="otpInputField" class="form-control form-control-lg text-center fw-bolder letter-spacing-lg rounded-pill border-2 border-success" placeholder="● ● ● ● ● ●" maxlength="6" pattern="\d{6}" required autocomplete="off" style="font-size: 1.8rem; letter-spacing: 8px;">
                    </div>

                    <div class="small text-muted mb-2">
                        <i class="fas fa-shield-alt text-success me-1"></i> Handover verification changes donation status to <strong>Handover Confirmed</strong>.
                    </div>
                </div>
                <div class="modal-footer border-top p-3 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success text-white px-4">
                        <i class="fas fa-check-circle me-1"></i> Verify Handover
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Two-Way Rating & Review Modal (NGO rating Restaurant) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-3">
                <h5 class="modal-title fw-bold" id="reviewModalLabel"><i class="fas fa-star text-warning me-2"></i> Rate & Review Donor Restaurant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        How was the food quality and handover experience with <strong id="reviewTargetName">Restaurant</strong>?
                    </p>

                    <!-- Overall Star Rating -->
                    <div class="text-center mb-3">
                        <label class="form-label fw-bold small text-dark d-block">Overall Rating</label>
                        <div class="star-rating d-inline-flex gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star fa-2x star-icon" data-value="{{ $i }}" onclick="setRating({{ $i }})" style="cursor: pointer; color: #cbd5e1;"></i>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5" required>
                    </div>

                    <!-- Criteria breakdown -->
                    <div class="row g-2 mb-3 bg-light p-3 rounded-3">
                        <div class="col-4 text-center">
                            <label class="small text-muted d-block" style="font-size: .75rem;">Food Condition</label>
                            <select name="criterion_1" class="form-select form-select-sm">
                                <option value="5">5 ★ Excellent</option>
                                <option value="4">4 ★ Good</option>
                                <option value="3">3 ★ Acceptable</option>
                            </select>
                        </div>
                        <div class="col-4 text-center">
                            <label class="small text-muted d-block" style="font-size: .75rem;">Handover Quality</label>
                            <select name="criterion_2" class="form-select form-select-sm">
                                <option value="5">5 ★ Fresh</option>
                                <option value="4">4 ★ Warm/Safe</option>
                                <option value="3">3 ★ Standard</option>
                            </select>
                        </div>
                        <div class="col-4 text-center">
                            <label class="small text-muted d-block" style="font-size: .75rem;">Packaging</label>
                            <select name="criterion_3" class="form-select form-select-sm">
                                <option value="5">5 ★ Sealed</option>
                                <option value="4">4 ★ Good</option>
                                <option value="3">3 ★ Basic</option>
                            </select>
                        </div>
                    </div>

                    <!-- Written Review -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Written Comments (Optional)</label>
                        <textarea name="review" class="form-control" rows="3" placeholder="Share observations regarding food hygiene, packaging cleanliness, and handover readiness."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                        <i class="fas fa-paper-plane me-1"></i> Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openOtpVerifyModal(requestId, restName) {
        document.getElementById('otpRestaurantName').innerText = restName;
        document.getElementById('otpVerifyForm').action = `/handover/${requestId}/verify-otp`;
        document.getElementById('otpInputField').value = '';
        const modal = new bootstrap.Modal(document.getElementById('otpVerifyModal'));
        modal.show();
    }

    function openReviewModal(requestId, restName) {
        document.getElementById('reviewTargetName').innerText = restName;
        document.getElementById('reviewForm').action = `/reviews/${requestId}`;
        setRating(5);
        const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
        modal.show();
    }

    function setRating(val) {
        document.getElementById('ratingInput').value = val;
        document.querySelectorAll('.star-icon').forEach(el => {
            const starVal = parseInt(el.getAttribute('data-value'), 10);
            if (starVal <= val) {
                el.style.color = '#f59e0b';
            } else {
                el.style.color = '#cbd5e1';
            }
        });
    }
</script>
@endsection
