@extends('layouts.dashboard')

@section('title', 'Recurring Donations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Recurring Schedules</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1">Recurring Donations</h3>
        <p class="text-muted small mb-0">Automate daily or weekly surplus food postings for banquets, buffets, and recurring donors.</p>
    </div>
    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createRecurringModal">
        <i class="fas fa-plus me-1"></i> New Recurring Schedule
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><strong>Please correct the errors:</strong>
        <ul class="mb-0 mt-1 small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Active Schedules Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom p-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-alt text-success me-2"></i> Active Recurring Profiles</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Schedule Name</th>
                        <th>Food Item</th>
                        <th>Quantity</th>
                        <th>Frequency</th>
                        <th>Pickup Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $sched)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $sched->schedule_name }}</strong>
                                <small class="text-muted d-block">{{ $sched->pickup_address }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $sched->food_name }}</div>
                                <small class="text-muted">{{ $sched->category }} • {{ $sched->food_type }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $sched->quantity }} {{ $sched->unit }}</span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info fw-bold">
                                    {{ ucfirst($sched->frequency) }}
                                </span>
                                @if($sched->frequency === 'selected_days' && is_array($sched->selected_days))
                                    <small class="d-block text-muted" style="font-size: .7rem;">
                                        {{ implode(', ', array_map(fn($d) => substr($d, 0, 3), $sched->selected_days)) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <small class="text-dark fw-semibold"><i class="fas fa-clock text-muted me-1"></i> {{ \Carbon\Carbon::parse($sched->pickup_time_of_day)->format('h:i A') }}</small>
                            </td>
                            <td>
                                @if($sched->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Paused</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('restaurant.recurring.toggle', $sched->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $sched->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $sched->is_active ? 'Pause' : 'Resume' }}">
                                        <i class="fas {{ $sched->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('restaurant.recurring.destroy', $sched->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-redo fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6>No recurring schedules created yet</h6>
                                <p class="small mb-0">Create automated daily or weekly donation schedules to rescue food automatically without daily manual input.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($schedules->hasPages())
        <div class="card-footer bg-white p-3 border-top">
            {{ $schedules->links() }}
        </div>
    @endif
</div>

<!-- History of Auto-Generated Donations -->
@if(isset($generatedDonations) && $generatedDonations->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-history text-primary me-2"></i> Recent Auto-Generated Donations</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Food Item</th>
                            <th>Quantity</th>
                            <th>Generated Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($generatedDonations as $don)
                            <tr>
                                <td>
                                    <strong>{{ $don->food_name }}</strong>
                                    <small class="text-muted d-block">{{ $don->description }}</small>
                                </td>
                                <td>{{ $don->quantity }} {{ $don->unit }}</td>
                                <td><small class="text-muted">{{ $don->created_at->format('M d, Y h:i A') }}</small></td>
                                <td>
                                    <span class="badge badge-{{ strtolower($don->status) }} px-2 py-1">{{ ucfirst($don->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Create Schedule Modal -->
<div class="modal fade" id="createRecurringModal" tabindex="-1" aria-labelledby="createRecurringModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-3">
                <h5 class="modal-title fw-bold" id="createRecurringModalLabel"><i class="fas fa-redo text-success me-2"></i> Create Recurring Donation Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('restaurant.recurring.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Schedule Name <span class="text-danger">*</span></label>
                            <input type="text" name="schedule_name" class="form-control" placeholder="e.g. Daily Dinner Buffet Surplus" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Food Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="food_name" class="form-control" placeholder="e.g. Cooked Dinner Meals & Rice" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="Veg">Veg</option>
                                <option value="Non-Veg">Non-Veg</option>
                                <option value="Vegan">Vegan</option>
                                <option value="Dairy">Dairy</option>
                                <option value="Bakery">Bakery</option>
                                <option value="Groceries">Groceries</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Food Type <span class="text-danger">*</span></label>
                            <select name="food_type" class="form-select" required>
                                <option value="Cooked Meals">Cooked Meals</option>
                                <option value="Packaged Goods">Packaged Goods</option>
                                <option value="Raw Ingredients">Raw Ingredients</option>
                                <option value="Fresh Produce">Fresh Produce</option>
                                <option value="Baked Goods">Baked Goods</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Typical Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" name="quantity" class="form-control" placeholder="25" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="servings">servings</option>
                                <option value="kg">kg</option>
                                <option value="packets">packets</option>
                                <option value="boxes">boxes</option>
                                <option value="litres">litres</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Repeat Frequency <span class="text-danger">*</span></label>
                            <select name="frequency" id="freqSelect" class="form-select" onchange="toggleDays(this.value)" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="selected_days">Selected Days of the Week</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ready / Pickup Time <span class="text-danger">*</span></label>
                            <input type="time" name="pickup_time_of_day" class="form-control" value="22:30" required>
                        </div>

                        <div class="col-12" id="selectedDaysContainer" style="display: none;">
                            <label class="form-label small fw-semibold d-block">Select Active Days</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="selected_days[]" value="{{ $day }}" id="day_{{ $day }}">
                                        <label class="form-check-label small" for="day_{{ $day }}">{{ substr($day, 0, 3) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">End Date (Optional)</label>
                            <input type="date" name="end_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pickup Address <span class="text-danger">*</span></label>
                            <textarea name="pickup_address" rows="2" class="form-control" required>{{ $restaurant->address }}, {{ $restaurant->city }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notes / Packaging Specifics</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="Allergen info, container bring requirements, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success text-white px-4">
                        <i class="fas fa-check me-1"></i> Save Recurring Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleDays(val) {
        const c = document.getElementById('selectedDaysContainer');
        if (c) {
            c.style.display = (val === 'selected_days') ? 'block' : 'none';
        }
    }
</script>
@endsection
