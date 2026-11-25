@extends('layouts.dashboard')

@section('title', 'My Food Donations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">My Food Donations</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">My Food Donations</h3>
        <p class="text-muted small mb-0">View, update, or remove your listed surplus food donations.</p>
    </div>
    <a href="{{ route('restaurant.donations.create') }}" class="btn btn-primary">
        <i class="fas fa-plus-circle me-1"></i> Add New Donation
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="donationsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Cooked Time</th>
                        <th>Expiry Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donations as $index => $d)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">
                                {{ $d->food_name }}
                                @if($d->image)
                                    <i class="fas fa-image text-muted ms-1" title="Has Image"></i>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $d->category }}</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $d->food_type }}</span></td>
                            <td class="fw-semibold">{{ $d->quantity }} {{ $d->unit }}</td>
                            <td class="small text-muted">{{ $d->cooked_time ? $d->cooked_time->format('M d, H:i') : '-' }}</td>
                            <td class="small text-muted">{{ $d->expiry_time ? $d->expiry_time->format('M d, H:i') : '-' }}</td>
                            <td><span class="badge badge-{{ strtolower($d->status) }}">{{ ucfirst($d->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('restaurant.donations.edit', $d->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form id="delete-donation-{{ $d->id }}" action="{{ route('restaurant.donations.destroy', $d->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('delete-donation-{{ $d->id }}')" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                                    <h5 class="fw-bold text-dark mb-1">No Donations Found</h5>
                                    <p class="text-muted small">You haven't added any surplus food donations yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $donations->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        var table = $('#donationsTable');
        if (table.length && table.find('tbody tr').length > 0 && table.find('tbody tr td[colspan]').length === 0) {
            table.DataTable({
                paging: false,
                info: false,
                searching: true,
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: [-1] }
                ]
            });
        }
    });
</script>
@endsection
