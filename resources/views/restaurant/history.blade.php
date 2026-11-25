@extends('layouts.dashboard')

@section('title', 'Pickup Management & History')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pickup Management</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Pickup Requests & Handover Tracking</h3>
        <p class="text-muted small mb-0">Track real-time pickup lifecycle, coordinate with volunteers, verify handovers with OTP/QR, and download certificates.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('restaurant.recurring.index') }}" class="btn btn-outline-success shadow-sm">
            <i class="fas fa-redo me-1"></i> Recurring Schedules
        </a>
        <a href="{{ route('restaurant.donations.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Add Food Donation
        </a>
    </div>
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
                        <th>Claiming NGO</th>
                        <th>Progress Stepper</th>
                        <th>Status</th>
                        <th class="text-end">Actions & Handover</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $index => $req)
                        @php
                            $hasReviewed = $req->reviews->where('reviewer_role', 'restaurant')->isNotEmpty();
                            $unreadChatCount = $req->messages->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
                        @endphp
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td style="min-width: 170px;">
                                <strong class="text-dark d-block">{{ $req->foodDonation->food_name ?? 'Surplus Food' }}</strong>
                                <small class="text-muted">{{ $req->foodDonation->quantity ?? '' }} {{ $req->foodDonation->unit ?? '' }} ({{ $req->foodDonation->category ?? '' }})</small>
                                <small class="d-block text-muted mt-1"><i class="fas fa-clock me-1"></i>{{ $req->pickup_time ? $req->pickup_time->format('M d, H:i') : '-' }}</small>
                            </td>
                            <td style="min-width: 160px;">
                                <div class="fw-semibold text-dark">{{ $req->ngo->ngo_name ?? 'NGO' }}</div>
                                <div class="small text-muted"><i class="fas fa-phone-alt me-1 text-success"></i>{{ $req->ngo->phone ?? '-' }}</div>
                                <small class="text-muted">Reg: {{ $req->ngo->registration_number ?? 'Verified' }}</small>
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
                                    {{-- Chat Button with unread indicator --}}
                                    <a href="{{ route('chat.show', $req->id) }}" class="btn btn-sm btn-outline-primary position-relative" title="Coordination Chat">
                                        <i class="fas fa-comment-dots me-1"></i> Chat
                                        @if($unreadChatCount > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .65rem;">
                                                {{ $unreadChatCount }}
                                            </span>
                                        @endif
                                    </a>

                                    @if($req->status === 'pending')
                                        <form action="{{ route('restaurant.requests.status', $req->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="accepted">
                                            <button type="submit" class="btn btn-sm btn-success text-white">
                                                <i class="fas fa-check me-1"></i> Accept
                                            </button>
                                        </form>
                                        <form action="{{ route('restaurant.requests.status', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Decline this request? The food will become available again.');">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times me-1"></i> Decline
                                            </button>
                                        </form>

                                    @elseif(in_array($req->status, ['accepted', 'out_for_pickup']))
                                        {{-- Handover OTP/QR verification modal trigger --}}
                                        <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="openHandoverModal({{ $req->id }}, '{{ addslashes($req->foodDonation->food_name ?? '') }}', '{{ addslashes($req->ngo->ngo_name ?? '') }}')">
                                            <i class="fas fa-qrcode me-1"></i> Handover OTP / QR
                                        </button>

                                    @elseif(in_array($req->status, ['handover_confirmed', 'picked']))
                                        <form action="{{ route('handover.complete', $req->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Confirm distribution and generate certificate">
                                                <i class="fas fa-check-double me-1"></i> Mark Completed
                                            </button>
                                        </form>

                                    @elseif($req->status === 'completed')
                                        @if($req->certificate)
                                            <a href="{{ route('certificates.download', $req->certificate->id) }}" class="btn btn-sm btn-outline-success" title="Download Official Certificate">
                                                <i class="fas fa-certificate me-1"></i> Certificate
                                            </a>
                                        @endif

                                        @if(!$hasReviewed)
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="openReviewModal({{ $req->id }}, '{{ addslashes($req->ngo->ngo_name ?? 'NGO') }}')">
                                                <i class="fas fa-star me-1"></i> Rate NGO
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6>No pickup requests found</h6>
                                <p class="small mb-0">When nearby NGOs request your surplus food, they will appear here with complete verification and live chat tools.</p>
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

<!-- Handover Verification Modal (For Restaurant to show OTP & QR to volunteer) -->
<div class="modal fade" id="handoverModal" tabindex="-1" aria-labelledby="handoverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-3 border-0">
                <h5 class="modal-title fw-bold" id="handoverModalLabel">
                    <i class="fas fa-shield-alt me-2"></i> Handover Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted small mb-3">
                    Show this OTP or present the QR code to the NGO volunteer from <strong id="handoverNgoName" class="text-dark">NGO</strong> to verify food collection.
                </p>

                <!-- OTP Display Box -->
                <div class="p-3 rounded-4 mb-3" style="background: #f0fdf4; border: 2px dashed #059669;">
                    <div class="text-uppercase text-muted fw-bold small mb-1" style="letter-spacing: 1px;">6-Digit Handover OTP</div>
                    <div class="display-4 fw-bolder text-success letter-spacing-lg mb-2" id="handoverOtpCode">------</div>
                    <div class="small text-muted" id="handoverTimerText">
                        <i class="fas fa-hourglass-half text-warning me-1"></i> Expires in: <span id="handoverTimer">--:--</span>
                    </div>
                </div>

                <!-- QR Code Display Box -->
                <div class="mb-3">
                    <div class="text-muted small fw-semibold mb-2">Or volunteer can scan this QR code directly:</div>
                    <div id="handoverQrContainer" class="p-2 bg-white rounded-3 shadow-sm d-inline-block border">
                        <img id="handoverQrImage" src="" alt="Handover QR Code" style="width: 180px; height: 180px;">
                    </div>
                </div>

                <!-- Resend / Regenerate Button -->
                <form id="resendOtpForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" id="resendOtpBtn">
                        <i class="fas fa-sync-alt me-1"></i> Generate New OTP
                    </button>
                </form>
            </div>
            <div class="modal-footer bg-light p-3 border-0 justify-content-between">
                <small class="text-muted"><i class="fas fa-lock me-1 text-success"></i> Secure Token Handover</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Two-Way Rating & Review Modal (Restaurant rating NGO) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-3">
                <h5 class="modal-title fw-bold" id="reviewModalLabel"><i class="fas fa-star text-warning me-2"></i> Rate & Review NGO Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        How was your pickup experience with <strong id="reviewTargetName">NGO</strong>? Your feedback helps maintain trust and hygiene in the rescue network.
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
                            <label class="small text-muted d-block" style="font-size: .75rem;">Punctuality</label>
                            <select name="criterion_1" class="form-select form-select-sm">
                                <option value="5">5 ★ High</option>
                                <option value="4">4 ★ Good</option>
                                <option value="3">3 ★ Fair</option>
                            </select>
                        </div>
                        <div class="col-4 text-center">
                            <label class="small text-muted d-block" style="font-size: .75rem;">Volunteer Behaviour</label>
                            <select name="criterion_2" class="form-select form-select-sm">
                                <option value="5">5 ★ Polite</option>
                                <option value="4">4 ★ Decent</option>
                                <option value="3">3 ★ Average</option>
                            </select>
                        </div>
                        <div class="col-4 text-center">
                            <label class="small text-muted d-block" style="font-size: .75rem;">Hygiene & Care</label>
                            <select name="criterion_3" class="form-select form-select-sm">
                                <option value="5">5 ★ Clean</option>
                                <option value="4">4 ★ Standard</option>
                                <option value="3">3 ★ Basic</option>
                            </select>
                        </div>
                    </div>

                    <!-- Written Review -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Written Comments (Optional)</label>
                        <textarea name="review" class="form-control" rows="3" placeholder="Share specific details about the handover (e.g., 'Arrived on time with sanitized insulated carriers')."></textarea>
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
    let timerInterval = null;

    function openHandoverModal(requestId, foodName, ngoName) {
        document.getElementById('handoverNgoName').innerText = ngoName;
        document.getElementById('resendOtpForm').action = `/handover/${requestId}/resend-otp`;

        // Fetch OTP and QR data from server
        fetch(`/handover/${requestId}/verification-data`)
            .then(res => res.json())
            .then(data => {
                if (data.otp_code) {
                    document.getElementById('handoverOtpCode').innerText = data.otp_code;
                }
                if (data.qr_data_uri) {
                    document.getElementById('handoverQrImage').src = data.qr_data_uri;
                }

                // Start Countdown Timer
                let seconds = data.seconds_remaining || 600;
                clearInterval(timerInterval);
                updateTimerDisplay(seconds);

                timerInterval = setInterval(() => {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(timerInterval);
                        document.getElementById('handoverTimer').innerText = 'Expired';
                    } else {
                        updateTimerDisplay(seconds);
                    }
                }, 1000);

                const modal = new bootstrap.Modal(document.getElementById('handoverModal'));
                modal.show();
            })
            .catch(err => {
                console.error(err);
                alert('Could not load verification data.');
            });
    }

    function updateTimerDisplay(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        document.getElementById('handoverTimer').innerText = `${m}:${s < 10 ? '0' : ''}${s}`;
    }

    function openReviewModal(requestId, ngoName) {
        document.getElementById('reviewTargetName').innerText = ngoName;
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
