@extends('layouts.dashboard')

@section('title', 'Donation Certificates')

@section('breadcrumb')
    <li class="breadcrumb-item active">Certificates Archive</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Donation Certificates</h3>
        <p class="text-muted small mb-0">Official acknowledgement certificates generated for completed food rescue handovers.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Certificate #</th>
                        <th>Donation Item</th>
                        <th>Recipient NGO</th>
                        <th>Meals Rescued</th>
                        <th>Date Issued</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificates as $cert)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $cert->certificate_number }}</strong>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $cert->foodDonation->food_name ?? 'Surplus Food' }}</div>
                                <small class="text-muted">{{ $cert->foodDonation->category ?? '' }}</small>
                            </td>
                            <td>
                                <div class="text-dark">{{ $cert->ngo->ngo_name ?? '-' }}</div>
                                <small class="text-muted">{{ $cert->ngo->city ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 fw-bold">
                                    {{ $cert->meals_count }} Meals
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $cert->issued_at->format('M d, Y') }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('certificates.verify', $cert->verification_token) }}" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Public Verification View">
                                    <i class="fas fa-qrcode me-1"></i> Verify
                                </a>
                                <a href="{{ route('certificates.download', $cert->id) }}" class="btn btn-sm btn-success text-white" title="Download Official PDF">
                                    <i class="fas fa-download me-1"></i> PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-certificate fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6>No certificates generated yet</h6>
                                <p class="small mb-0">Certificates are automatically generated as soon as an NGO completes a verified food handover.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($certificates->hasPages())
        <div class="card-footer bg-white p-3 border-top">
            {{ $certificates->links() }}
        </div>
    @endif
</div>
@endsection
