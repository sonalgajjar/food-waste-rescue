@props(['request'])

@php
    $status = $request->status;
    $isRejected = in_array($status, ['rejected', 'cancelled']);

    $stages = [
        'pending' => ['label' => 'Requested', 'icon' => 'fa-clipboard-list'],
        'accepted' => ['label' => 'Accepted', 'icon' => 'fa-check-circle'],
        'out_for_pickup' => ['label' => 'Out for Pickup', 'icon' => 'fa-truck'],
        'handover_confirmed' => ['label' => 'Handover Confirmed', 'icon' => 'fa-qrcode'],
        'completed' => ['label' => 'Completed', 'icon' => 'fa-box-check'],
    ];

    $stageOrder = ['pending', 'accepted', 'out_for_pickup', 'handover_confirmed', 'completed'];

    // Map 'picked' to 'handover_confirmed'
    $currentKey = ($status === 'picked') ? 'handover_confirmed' : $status;
    $currentIndex = array_search($currentKey, $stageOrder);
    if ($currentIndex === false && !$isRejected) {
        $currentIndex = 0;
    }
@endphp

<div class="stepper-wrapper mb-3 py-2 px-1">
    @if($isRejected)
        <div class="alert alert-danger py-2 px-3 mb-0 d-flex align-items-center gap-2 rounded-3 small">
            <i class="fas fa-times-circle fa-lg"></i>
            <div>
                <strong>Request {{ ucfirst($status) }}:</strong>
                <span class="text-muted">{{ $request->remarks ?: 'This pickup request has been declined or cancelled.' }}</span>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center position-relative stepper-line-container">
            <div class="stepper-progress-bg"></div>
            <div class="stepper-progress-bar" style="width: {{ ($currentIndex / (count($stageOrder) - 1)) * 100 }}%;"></div>

            @foreach($stageOrder as $idx => $key)
                @php
                    $isDone = ($idx < $currentIndex);
                    $isActive = ($idx === $currentIndex);
                    $isUpcoming = ($idx > $currentIndex);
                @endphp
                <div class="stepper-step text-center {{ $isDone ? 'step-done' : '' }} {{ $isActive ? 'step-active' : '' }} {{ $isUpcoming ? 'step-upcoming' : '' }}">
                    <div class="stepper-circle mx-auto d-flex align-items-center justify-content-center shadow-sm">
                        @if($isDone)
                            <i class="fas fa-check small"></i>
                        @else
                            <i class="fas {{ $stages[$key]['icon'] }} small"></i>
                        @endif
                    </div>
                    <div class="stepper-label mt-1">
                        <span class="d-block fw-semibold">{{ $stages[$key]['label'] }}</span>
                        @if($isActive)
                            <span class="badge bg-success bg-opacity-75" style="font-size: .65rem;">Current</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .stepper-line-container {
        position: relative;
        padding: 0 10px;
    }
    .stepper-progress-bg {
        position: absolute;
        top: 18px;
        left: 20px;
        right: 20px;
        height: 3px;
        background: #e2e8f0;
        z-index: 1;
    }
    .stepper-progress-bar {
        position: absolute;
        top: 18px;
        left: 20px;
        height: 3px;
        background: #10b981;
        transition: width .4s ease;
        z-index: 2;
    }
    .stepper-step {
        position: relative;
        z-index: 3;
        flex: 1;
    }
    .stepper-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #cbd5e1;
        color: #94a3b8;
        font-size: .85rem;
        transition: all .3s ease;
    }
    .step-done .stepper-circle {
        background: #10b981;
        border-color: #10b981;
        color: #fff;
    }
    .step-active .stepper-circle {
        background: #fff;
        border-color: #059669;
        color: #059669;
        box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.2) !important;
    }
    .stepper-label {
        font-size: .75rem;
        color: #64748b;
    }
    .step-active .stepper-label {
        color: #0f172a;
        font-weight: 700 !important;
    }
    .step-done .stepper-label {
        color: #059669;
    }
</style>
