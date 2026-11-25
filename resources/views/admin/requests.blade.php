@extends('layouts.dashboard')

@section('title', 'Manage Pickup Requests')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pickup Requests</li>
@endsection

@section('content')
<div class="mb-4">
    <h3 class="fw-bold mb-1">Pickup Requests Log</h3>
    <p class="text-muted small mb-0">System-wide monitoring of NGO pickup requests and fulfillment statuses.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Requested Item</th>
                        <th>Claiming NGO</th>
                        <th>Donor Restaurant</th>
                        <th>Scheduled Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $index => $req)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $req->foodDonation->food_name ?? 'Item' }}</td>
                            <td>{{ $req->ngo->ngo_name ?? 'NGO' }}</td>
                            <td>{{ $req->foodDonation->restaurant->restaurant_name ?? 'Restaurant' }}</td>
                            <td class="small text-muted">{{ $req->pickup_time ? $req->pickup_time->format('M d, Y H:i') : '-' }}</td>
                            <td><span class="badge badge-{{ strtolower($req->status) }}">{{ ucfirst($req->status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
