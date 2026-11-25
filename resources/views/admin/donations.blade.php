@extends('layouts.dashboard')

@section('title', 'Manage Food Donations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Food Donations</li>
@endsection

@section('content')
<div class="mb-4">
    <h3 class="fw-bold mb-1">System-Wide Food Donations</h3>
    <p class="text-muted small mb-0">Monitor all food listings submitted across all registered restaurants.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="adminDonationsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Food Item</th>
                        <th>Restaurant</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Cooked Time</th>
                        <th>Expiry Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($donations as $index => $d)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $d->food_name }}</td>
                            <td>{{ $d->restaurant->restaurant_name ?? 'N/A' }}</td>
                            <td><span class="badge bg-secondary">{{ $d->category }}</span></td>
                            <td class="fw-semibold">{{ $d->quantity }} {{ $d->unit }}</td>
                            <td class="small text-muted">{{ $d->cooked_time ? $d->cooked_time->format('M d, H:i') : '-' }}</td>
                            <td class="small text-muted">{{ $d->expiry_time ? $d->expiry_time->format('M d, H:i') : '-' }}</td>
                            <td><span class="badge badge-{{ strtolower($d->status) }}">{{ ucfirst($d->status) }}</span></td>
                        </tr>
                    @endforeach
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
        var table = $('#adminDonationsTable');
        if (table.length && table.find('tbody tr').length > 0 && table.find('tbody tr td[colspan]').length === 0) {
            table.DataTable({ paging: false, info: false, searching: true });
        }
    });
</script>
@endsection
