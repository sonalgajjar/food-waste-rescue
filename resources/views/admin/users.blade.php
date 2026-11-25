@extends('layouts.dashboard')

@section('title', 'Manage Users')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
<div class="mb-4">
    <h3 class="fw-bold mb-1">User Account Governance</h3>
    <p class="text-muted small mb-0">View all registered users, roles, and status controls.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $u)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @if($u->role === 'admin')
                                    <span class="badge bg-danger">Admin</span>
                                @elseif($u->role === 'restaurant')
                                    <span class="badge bg-success">Restaurant</span>
                                @else
                                    <span class="badge bg-info text-dark">NGO</span>
                                @endif
                            </td>
                            <td>{{ $u->phone ?? 'N/A' }}</td>
                            <td>{{ $u->city ?? 'N/A' }}</td>
                            <td><span class="badge badge-{{ strtolower($u->status) }}">{{ ucfirst($u->status) }}</span></td>
                            <td class="text-end">
                                @if($u->role !== 'admin')
                                    <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $u->status === 'active' ? 'warning' : 'success' }}">
                                            <i class="fas fa-power-off me-1"></i> {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
