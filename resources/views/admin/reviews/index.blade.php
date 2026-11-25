@extends('layouts.dashboard')

@section('title', 'Manage Reviews & Ratings')

@section('breadcrumb')
    <li class="breadcrumb-item active">Reviews Moderation</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Reviews & Ratings Moderation</h3>
        <p class="text-muted small mb-0">Oversee feedback exchanged between food donor restaurants and partner NGOs.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reviewer</th>
                        <th>Reviewed Target</th>
                        <th>Donation Item</th>
                        <th>Rating</th>
                        <th>Written Review</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $rev)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $rev->reviewer->name ?? 'User' }}</strong>
                                <span class="badge bg-light text-secondary border d-block mt-1" style="font-size: .65rem; width: fit-content;">
                                    {{ ucfirst($rev->reviewer_role) }}
                                </span>
                            </td>
                            <td>
                                @if($rev->reviewer_role === 'restaurant')
                                    <span class="text-dark fw-semibold">{{ $rev->ngo->ngo_name ?? 'NGO' }}</span>
                                    <small class="text-muted d-block">(NGO Partner)</small>
                                @else
                                    <span class="text-dark fw-semibold">{{ $rev->restaurant->restaurant_name ?? 'Restaurant' }}</span>
                                    <small class="text-muted d-block">(Food Donor)</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-dark">{{ $rev->foodDonation->food_name ?? '-' }}</small>
                            </td>
                            <td>
                                <div class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $rev->rating ? '' : 'text-muted opacity-25' }}" style="font-size: .8rem;"></i>
                                    @endfor
                                    <span class="text-dark fw-bold ms-1" style="font-size: .85rem;">{{ $rev->rating }}/5</span>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">{{ $rev->review ?: 'No written comment.' }}</small>
                            </td>
                            <td>
                                @if($rev->status === 'approved')
                                    <span class="badge bg-success">Visible</span>
                                @else
                                    <span class="badge bg-danger">Hidden</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.reviews.toggle', $rev->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $rev->status === 'approved' ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $rev->status === 'approved' ? 'Hide Review' : 'Approve & Show' }}">
                                        <i class="fas {{ $rev->status === 'approved' ? 'fa-eye-slash' : 'fa-eye' }} me-1"></i>
                                        {{ $rev->status === 'approved' ? 'Hide' : 'Show' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-star-half-alt fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6>No reviews submitted yet</h6>
                                <p class="small mb-0">Two-way ratings and reviews submitted after completed handovers will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reviews->hasPages())
        <div class="card-footer bg-white p-3 border-top">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
@endsection
