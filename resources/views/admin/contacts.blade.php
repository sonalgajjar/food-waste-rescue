@extends('layouts.dashboard')

@section('title', 'Manage Contact Messages')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Contact Messages</li>
@endsection

@section('content')
<div class="mb-4">
    <h3 class="fw-bold mb-1">Public Inquiries & Messages</h3>
    <p class="text-muted small mb-0">Review messages submitted via the website contact form.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Sender Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message Snippet</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $index => $c)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $c->name }}</td>
                            <td><a href="mailto:{{ $c->email }}" class="text-decoration-none">{{ $c->email }}</a></td>
                            <td class="fw-semibold">{{ $c->subject }}</td>
                            <td class="small text-muted" title="{{ $c->message }}">{{ Str::limit($c->message, 50) }}</td>
                            <td class="small text-muted">{{ $c->created_at ? $c->created_at->format('M d, H:i') : '-' }}</td>
                            <td><span class="badge bg-{{ $c->status === 'replied' ? 'success' : 'warning' }}">{{ ucfirst($c->status) }}</span></td>
                            <td class="text-end">
                                @if($c->status !== 'replied')
                                    <form action="{{ route('admin.contacts.reply', $c->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check me-1"></i> Mark Replied
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="fas fa-check-double text-success"></i> Handled</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $contacts->links() }}
        </div>
    </div>
</div>
@endsection
