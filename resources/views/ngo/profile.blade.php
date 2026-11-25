@extends('layouts.dashboard')

@section('title', 'NGO Profile')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ngo.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">NGO Profile</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-hands-helping text-primary me-2"></i> NGO Organization Profile</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('ngo.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NGO Organization Name</label>
                            <input type="text" name="ngo_name" class="form-control" value="{{ old('ngo_name', $ngo->ngo_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registration / Certificate Number</label>
                            <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number', $ngo->registration_number) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Primary Contact / Representative Name</label>
                            <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name', $ngo->owner_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $ngo->phone) }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Office / Warehouse Street Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $ngo->address) }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $ngo->city) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', $ngo->state) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $ngo->pincode) }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Mission Statement / Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $ngo->description) }}</textarea>
                    </div>

                    <!-- Geolocation Map Picker -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">NGO Location on Map</label>
                        <div id="ngoMapPicker" style="height: 260px; border-radius: .5rem;" class="border mb-2"></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" id="latitude" name="latitude" class="form-control form-control-sm bg-light" value="{{ old('latitude', $ngo->latitude ?? 28.6180) }}" readonly>
                            </div>
                            <div class="col-6">
                                <input type="text" id="longitude" name="longitude" class="form-control form-control-sm bg-light" value="{{ old('longitude', $ngo->longitude ?? 77.2110) }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const defaultLat = parseFloat(document.getElementById('latitude').value) || 28.6180;
        const defaultLng = parseFloat(document.getElementById('longitude').value) || 77.2110;

        const map = L.map('ngoMapPicker').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        marker.on('dragend', function(e) {
            const latlng = marker.getLatLng();
            document.getElementById('latitude').value = latlng.lat.toFixed(6);
            document.getElementById('longitude').value = latlng.lng.toFixed(6);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
        });
    });
</script>
@endsection
