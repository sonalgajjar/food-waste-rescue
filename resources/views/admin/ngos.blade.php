@extends('layouts.dashboard')

@section('title', 'Manage NGOs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">NGOs</li>
@endsection

@section('content')
<div class="mb-4">
    <h3 class="fw-bold mb-1">Registered NGO Partners</h3>
    <p class="text-muted small mb-0">Verify NGO certificates, inspect registration documents, and manage platform distribution approvals.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>NGO Name</th>
                        <th>Registration & Certificate</th>
                        <th>Representative</th>
                        <th>Phone / Email</th>
                        <th>City</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ngos as $index => $n)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $n->ngo_name }}</div>
                                <small class="text-muted">Registered {{ $n->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div><code>{{ $n->registration_number }}</code></div>
                                @if($n->fcra_number)
                                    <div class="small text-muted" style="font-size: 11px;">FCRA: {{ $n->fcra_number }}</div>
                                @endif
                                @if($n->registration_document)
                                    <a href="{{ route('admin.documents.view', ['type' => 'ngo', 'id' => $n->id]) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 mt-1" style="font-size: 11px;">
                                        <i class="fas fa-file-pdf me-1"></i> View Certificate
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border mt-1" style="font-size: 10px;">No Doc</span>
                                @endif
                            </td>
                            <td>{{ $n->owner_name }}</td>
                            <td>
                                <div class="small">{{ $n->phone }}</div>
                                <div class="small text-muted">{{ $n->email }}</div>
                            </td>
                            <td>{{ $n->city }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($n->status) }}">{{ ucfirst($n->status) }}</span>
                                @if($n->status === 'rejected' && $n->rejection_reason)
                                    <div class="small text-danger mt-1" style="max-width: 180px; font-size: 11px;" title="{{ $n->rejection_reason }}">
                                        <i class="fas fa-info-circle me-1"></i>{{ Str::limit($n->rejection_reason, 45) }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($n->status !== 'active')
                                    <form action="{{ route('admin.ngos.approve', $n->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-sm btn-success me-1"><i class="fas fa-check me-1"></i> Approve</button>
                                    </form>
                                @endif
                                @if($n->status !== 'rejected')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModalNGO{{ $n->id }}">
                                        <i class="fas fa-ban me-1"></i> Reject
                                    </button>

                                    <!-- Rejection Modal -->
                                    <div class="modal fade text-start" id="rejectModalNGO{{ $n->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title fw-bold"><i class="fas fa-ban me-2"></i> Reject NGO Application</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.ngos.approve', $n->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="rejected">
                                                    <div class="modal-body p-4">
                                                        <p class="small text-muted mb-3">Please specify why <strong>{{ $n->ngo_name }}</strong> cannot be approved. The NGO representative will receive this reason in their notification.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Society registration certificate invalid or expired." required></textarea>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label fw-semibold small">Internal Admin Notes <span class="text-muted fw-normal">(optional)</span></label>
                                                            <input type="text" name="verification_notes" class="form-control form-control-sm" placeholder="For audit logs">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $ngos->links() }}
        </div>
    </div>
</div>
@endsection
