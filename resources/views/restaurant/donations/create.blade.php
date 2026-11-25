@extends('layouts.dashboard')

@section('title', 'Add Food Donation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('restaurant.donations.index') }}">Donations</a></li>
    <li class="breadcrumb-item active">Add Donation</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-plus-circle text-success me-2"></i> Post Surplus Food Donation</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('restaurant.donations.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Food Title / Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="food_name" class="form-control @error('food_name') is-invalid @enderror" value="{{ old('food_name') }}" placeholder="e.g. 50 Servings Veg Biryani Trays" required>
                            @error('food_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Food Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                <option value="Veg" {{ old('category') === 'Veg' ? 'selected' : '' }}>Vegetarian</option>
                                <option value="Non-Veg" {{ old('category') === 'Non-Veg' ? 'selected' : '' }}>Non-Vegetarian</option>
                                <option value="Vegan" {{ old('category') === 'Vegan' ? 'selected' : '' }}>Vegan</option>
                                <option value="Dairy" {{ old('category') === 'Dairy' ? 'selected' : '' }}>Dairy Products</option>
                                <option value="Bakery" {{ old('category') === 'Bakery' ? 'selected' : '' }}>Bakery & Breads</option>
                                <option value="Groceries" {{ old('category') === 'Groceries' ? 'selected' : '' }}>Raw Groceries</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Food Type <span class="text-danger">*</span></label>
                            <select name="food_type" class="form-select @error('food_type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="Cooked Meals" {{ old('food_type') === 'Cooked Meals' ? 'selected' : '' }}>Cooked Meals</option>
                                <option value="Packaged Goods" {{ old('food_type') === 'Packaged Goods' ? 'selected' : '' }}>Packaged Goods</option>
                                <option value="Raw Ingredients" {{ old('food_type') === 'Raw Ingredients' ? 'selected' : '' }}>Raw Ingredients</option>
                                <option value="Fresh Produce" {{ old('food_type') === 'Fresh Produce' ? 'selected' : '' }}>Fresh Produce</option>
                                <option value="Baked Goods" {{ old('food_type') === 'Baked Goods' ? 'selected' : '' }}>Baked Goods</option>
                            </select>
                            @error('food_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="e.g. 30" required>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit of Measurement <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                <option value="servings" {{ old('unit') === 'servings' ? 'selected' : '' }}>Servings</option>
                                <option value="kg" {{ old('unit') === 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                                <option value="boxes" {{ old('unit') === 'boxes' ? 'selected' : '' }}>Boxes</option>
                                <option value="packets" {{ old('unit') === 'packets' ? 'selected' : '' }}>Packets</option>
                                <option value="plates" {{ old('unit') === 'plates' ? 'selected' : '' }}>Plates</option>
                            </select>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Food Image</label>
                            <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cooked / Prepared Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="cooked_time" name="cooked_time" class="form-control @error('cooked_time') is-invalid @enderror" value="{{ old('cooked_time', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('cooked_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="expiry_time" name="expiry_time" class="form-control @error('expiry_time') is-invalid @enderror" value="{{ old('expiry_time', now()->addHours(12)->format('Y-m-d\TH:i')) }}" required>
                            <div id="expiryWarning" class="form-text text-danger d-none fw-semibold"><i class="fas fa-exclamation-triangle me-1"></i> Warning: Expiry time must be in the future.</div>
                            @error('expiry_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Food Safety & Handling Details --}}
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-shield-alt text-success me-2"></i> Food Safety & Storage Standards</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Storage Instructions</label>
                                <select name="storage_instructions" class="form-select form-select-sm">
                                    <option value="Keep Refrigerated (Below 5°C)" {{ old('storage_instructions') === 'Keep Refrigerated (Below 5°C)' ? 'selected' : '' }}>Keep Refrigerated (Below 5°C)</option>
                                    <option value="Keep Hot (Above 60°C)" {{ old('storage_instructions') === 'Keep Hot (Above 60°C)' ? 'selected' : '' }}>Keep Hot (Above 60°C)</option>
                                    <option value="Store in Cool Dry Place" {{ old('storage_instructions') === 'Store in Cool Dry Place' ? 'selected' : '' }}>Store in Cool Dry Place</option>
                                    <option value="Ambient Room Temperature" {{ old('storage_instructions') === 'Ambient Room Temperature' ? 'selected' : '' }}>Ambient Room Temperature</option>
                                    <option value="Deep Freeze (-18°C)" {{ old('storage_instructions') === 'Deep Freeze (-18°C)' ? 'selected' : '' }}>Deep Freeze (-18°C)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Packaging Condition</label>
                                <select name="packaging_condition" class="form-select form-select-sm">
                                    <option value="Sealed Food-Grade Containers" {{ old('packaging_condition') === 'Sealed Food-Grade Containers' ? 'selected' : '' }}>Sealed Food-Grade Containers</option>
                                    <option value="Sterile Aluminum Foil Packs" {{ old('packaging_condition') === 'Sterile Aluminum Foil Packs' ? 'selected' : '' }}>Sterile Aluminum Foil Packs</option>
                                    <option value="Original Sealed Packaging" {{ old('packaging_condition') === 'Original Sealed Packaging' ? 'selected' : '' }}>Original Commercial Packaging</option>
                                    <option value="Clean Insulated Thermal Casks" {{ old('packaging_condition') === 'Clean Insulated Thermal Casks' ? 'selected' : '' }}>Clean Insulated Thermal Casks</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Allergen Information <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="allergen_info" class="form-control form-control-sm" value="{{ old('allergen_info') }}" placeholder="e.g. Contains Nuts, Dairy, Gluten, Soy (or None)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Pickup Instructions for Volunteer</label>
                                <input type="text" name="pickup_instructions" class="form-control form-control-sm" value="{{ old('pickup_instructions') }}" placeholder="e.g. Enter through kitchen back gate, ask for Chef Ramesh">
                            </div>
                        </div>

                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="food_safety_declaration" id="safetyCheck" value="1" checked required>
                            <label class="form-check-label small fw-semibold text-dark" for="safetyCheck">
                                <i class="fas fa-check-circle text-success me-1"></i> I declare that this food has been prepared hygienically in compliance with FSSAI regulations, was properly preserved, and is safe for human consumption.
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Detailed Description / Food Item Notes</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Specify ingredients, quantities, or handling instructions">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pickup Address <span class="text-danger">*</span></label>
                        <textarea name="pickup_address" class="form-control @error('pickup_address') is-invalid @enderror" rows="2" required>{{ old('pickup_address', $restaurant->address) }}</textarea>
                        @error('pickup_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Geolocation Map Picker -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pinpoint Location on Map (Leaflet.js)</label>
                        <div id="mapPicker" style="height: 280px; border-radius: .5rem;" class="border mb-2"></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" id="latitude" name="latitude" class="form-control form-control-sm bg-light" value="{{ old('latitude', $restaurant->latitude ?? 28.6139) }}" readonly placeholder="Latitude">
                            </div>
                            <div class="col-6">
                                <input type="text" id="longitude" name="longitude" class="form-control form-control-sm bg-light" value="{{ old('longitude', $restaurant->longitude ?? 77.2090) }}" readonly placeholder="Longitude">
                            </div>
                        </div>
                        <small class="text-muted">Click or drag the marker on the map to specify exact pickup coordinates.</small>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('restaurant.donations.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-check me-1"></i> Post Donation</button>
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

        const map = L.map('mapPicker').setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

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

        // Expiry date validation
        const expiryInput = document.getElementById('expiry_time');
        const cookedInput = document.getElementById('cooked_time');
        const warning = document.getElementById('expiryWarning');

        function validateTimes() {
            if (expiryInput && cookedInput) {
                const expiry = new Date(expiryInput.value);
                const cooked = new Date(cookedInput.value);
                const now = new Date();
                if (expiry <= cooked || expiry <= now) {
                    warning.classList.remove('d-none');
                } else {
                    warning.classList.add('d-none');
                }
            }
        }
        if (expiryInput) expiryInput.addEventListener('change', validateTimes);
        if (cookedInput) cookedInput.addEventListener('change', validateTimes);
    });
</script>
@endsection
