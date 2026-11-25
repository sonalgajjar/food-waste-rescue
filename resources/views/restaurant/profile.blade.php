@extends('layouts.dashboard')

@section('title', 'Restaurant Profile')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Restaurant Profile</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-store text-success me-2"></i> Restaurant Profile & Business Details</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('restaurant.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Restaurant Name</label>
                            <input type="text" name="restaurant_name" class="form-control" value="{{ old('restaurant_name', $restaurant->restaurant_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Owner / Primary Contact Name</label>
                            <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name', $restaurant->owner_name) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $restaurant->phone) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Food Safety License No. (FSSAI)</label>
                            <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $restaurant->license_number) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">GST Registration No.</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number', $restaurant->gst_number) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Opening Time</label>
                            <input type="time" name="opening_time" class="form-control" value="{{ old('opening_time', $restaurant->opening_time ? $restaurant->opening_time->format('H:i') : '09:00') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Closing Time</label>
                            <input type="time" name="closing_time" class="form-control" value="{{ old('closing_time', $restaurant->closing_time ? $restaurant->closing_time->format('H:i') : '22:00') }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Street Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $restaurant->address) }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $restaurant->city) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', $restaurant->state) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $restaurant->pincode) }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Restaurant Bio / Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $restaurant->description) }}</textarea>
                    </div>

                    <!-- Geolocation Map Picker -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Restaurant Location on Map</label>
                        <div id="restaurantMapPicker" style="height: 260px; border-radius: .5rem;" class="border mb-2"></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" id="latitude" name="latitude" class="form-control form-control-sm bg-light" value="{{ old('latitude', $restaurant->latitude ?? 28.6139) }}" readonly>
                            </div>
                            <div class="col-6">
                                <input type="text" id="longitude" name="longitude" class="form-control form-control-sm bg-light" value="{{ old('longitude', $restaurant->longitude ?? 77.2090) }}" readonly>
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
        const defaultLat = parseFloat(document.getElementById('latitude').value) || 28.6139;
        const defaultLng = parseFloat(document.getElementById('longitude').value) || 77.2090;

        const map = L.map('restaurantMapPicker').setView([defaultLat, defaultLng], 13);
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
