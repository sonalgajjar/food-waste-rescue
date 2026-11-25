@extends('layouts.frontend')

@section('title', 'Verify Donation Certificate — FoodRescue')

@section('content')
<div class="container py-5 my-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9 col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-4 text-center">
                    <div class="d-inline-flex p-3 rounded-circle {{ $isValid ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }} mb-3">
                        <i class="fas {{ $isValid ? 'fa-shield-check' : 'fa-times-circle' }} fa-3x"></i>
                    </div>
                    <h4 class="fw-bold mb-1 {{ $isValid ? 'text-success' : 'text-danger' }}">
                        {{ $isValid ? 'Authentic Donation Certificate' : 'Certificate Not Found' }}
                    </h4>
                    <p class="text-muted small mb-0">
                        {{ $isValid ? 'Verified against Food Waste Rescue official platform records.' : 'No active certificate found matching this verification token.' }}
                    </p>
                </div>

                <div class="card-body p-4">
                    @if($isValid)
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4 rounded-3">
                            <i class="fas fa-check-circle fa-lg"></i>
                            <div>
                                <strong>Status: Valid & Verified</strong><br>
                                <small>Issued on {{ $certificate->issued_at->format('M d, Y') }}</small>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle small mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted" style="width: 35%;">Certificate Number</th>
                                        <td class="fw-bold text-dark">{{ $certificate->certificate_number }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Donor Restaurant</th>
                                        <td>{{ $certificate->restaurant->restaurant_name ?? '-' }} ({{ $certificate->restaurant->city ?? '' }})</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Beneficiary NGO</th>
                                        <td>{{ $certificate->ngo->ngo_name ?? '-' }} (Reg: {{ $certificate->ngo->registration_number ?? '-' }})</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Food Item Donated</th>
                                        <td>{{ $certificate->foodDonation->food_name ?? '-' }} ({{ $certificate->foodDonation->quantity ?? '' }} {{ $certificate->foodDonation->unit ?? '' }})</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Meals Rescued</th>
                                        <td><span class="badge bg-success px-2 py-1">{{ $certificate->meals_count }} Meals</span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Handover Verification</th>
                                        <td>
                                            <span class="text-success fw-semibold"><i class="fas fa-badge-check me-1"></i> Digitally Confirmed</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if(auth()->check() && (auth()->user()->role === 'restaurant' || auth()->user()->role === 'admin'))
                            <div class="mt-4 text-center">
                                <a href="{{ route('certificates.download', $certificate->id) }}" class="btn btn-success px-4 rounded-pill shadow-sm">
                                    <i class="fas fa-file-pdf me-2"></i> Download Official PDF
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger mb-0 text-center">
                            <strong>Invalid Verification Token:</strong> <code>{{ $token }}</code><br>
                            This certificate code does not exist in our registry or may have been revoked.
                        </div>
                    @endif
                </div>

                <div class="card-footer bg-light p-3 text-center small text-muted">
                    Food Waste Rescue Platform • Authentic Surplus Food Rescue Registry
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
