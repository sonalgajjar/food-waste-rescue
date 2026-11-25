@extends('layouts.dashboard')

@section('title', 'Edit Food Donation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('restaurant.donations.index') }}">Donations</a></li>
    <li class="breadcrumb-item active">Edit Donation</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-edit text-primary me-2"></i> Edit Food Donation Details</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('restaurant.donations.update', $donation->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Food Title / Item Name</label>
                            <input type="text" name="food_name" class="form-control" value="{{ old('food_name', $donation->food_name) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="Veg" {{ $donation->category === 'Veg' ? 'selected' : '' }}>Vegetarian</option>
                                <option value="Non-Veg" {{ $donation->category === 'Non-Veg' ? 'selected' : '' }}>Non-Vegetarian</option>
                                <option value="Vegan" {{ $donation->category === 'Vegan' ? 'selected' : '' }}>Vegan</option>
                                <option value="Dairy" {{ $donation->category === 'Dairy' ? 'selected' : '' }}>Dairy Products</option>
                                <option value="Bakery" {{ $donation->category === 'Bakery' ? 'selected' : '' }}>Bakery & Breads</option>
                                <option value="Groceries" {{ $donation->category === 'Groceries' ? 'selected' : '' }}>Raw Groceries</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Food Type</label>
                            <select name="food_type" class="form-select" required>
                                <option value="Cooked Meals" {{ $donation->food_type === 'Cooked Meals' ? 'selected' : '' }}>Cooked Meals</option>
                                <option value="Packaged Goods" {{ $donation->food_type === 'Packaged Goods' ? 'selected' : '' }}>Packaged Goods</option>
                                <option value="Raw Ingredients" {{ $donation->food_type === 'Raw Ingredients' ? 'selected' : '' }}>Raw Ingredients</option>
                                <option value="Fresh Produce" {{ $donation->food_type === 'Fresh Produce' ? 'selected' : '' }}>Fresh Produce</option>
                                <option value="Baked Goods" {{ $donation->food_type === 'Baked Goods' ? 'selected' : '' }}>Baked Goods</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" step="0.1" name="quantity" class="form-control" value="{{ old('quantity', $donation->quantity) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <input type="text" name="unit" class="form-control" value="{{ old('unit', $donation->unit) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Change Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cooked Time</label>
                            <input type="datetime-local" name="cooked_time" class="form-control" value="{{ old('cooked_time', $donation->cooked_time ? $donation->cooked_time->format('Y-m-d\TH:i') : '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry Time</label>
                            <input type="datetime-local" name="expiry_time" class="form-control" value="{{ old('expiry_time', $donation->expiry_time ? $donation->expiry_time->format('Y-m-d\TH:i') : '') }}" required>
                        </div>
                    </div>

                    {{-- Food Safety & Handling Details --}}
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-shield-alt text-success me-2"></i> Food Safety & Storage Standards</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Storage Instructions</label>
                                <select name="storage_instructions" class="form-select form-select-sm">
                                    <option value="Keep Refrigerated (Below 5°C)" {{ old('storage_instructions', $donation->storage_instructions) === 'Keep Refrigerated (Below 5°C)' ? 'selected' : '' }}>Keep Refrigerated (Below 5°C)</option>
                                    <option value="Keep Hot (Above 60°C)" {{ old('storage_instructions', $donation->storage_instructions) === 'Keep Hot (Above 60°C)' ? 'selected' : '' }}>Keep Hot (Above 60°C)</option>
                                    <option value="Store in Cool Dry Place" {{ old('storage_instructions', $donation->storage_instructions) === 'Store in Cool Dry Place' ? 'selected' : '' }}>Store in Cool Dry Place</option>
                                    <option value="Ambient Room Temperature" {{ old('storage_instructions', $donation->storage_instructions) === 'Ambient Room Temperature' ? 'selected' : '' }}>Ambient Room Temperature</option>
                                    <option value="Deep Freeze (-18°C)" {{ old('storage_instructions', $donation->storage_instructions) === 'Deep Freeze (-18°C)' ? 'selected' : '' }}>Deep Freeze (-18°C)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Packaging Condition</label>
                                <select name="packaging_condition" class="form-select form-select-sm">
                                    <option value="Sealed Food-Grade Containers" {{ old('packaging_condition', $donation->packaging_condition) === 'Sealed Food-Grade Containers' ? 'selected' : '' }}>Sealed Food-Grade Containers</option>
                                    <option value="Sterile Aluminum Foil Packs" {{ old('packaging_condition', $donation->packaging_condition) === 'Sterile Aluminum Foil Packs' ? 'selected' : '' }}>Sterile Aluminum Foil Packs</option>
                                    <option value="Original Sealed Packaging" {{ old('packaging_condition', $donation->packaging_condition) === 'Original Sealed Packaging' ? 'selected' : '' }}>Original Commercial Packaging</option>
                                    <option value="Clean Insulated Thermal Casks" {{ old('packaging_condition', $donation->packaging_condition) === 'Clean Insulated Thermal Casks' ? 'selected' : '' }}>Clean Insulated Thermal Casks</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Allergen Information</label>
                                <input type="text" name="allergen_info" class="form-control form-control-sm" value="{{ old('allergen_info', $donation->allergen_info) }}" placeholder="e.g. Contains Nuts, Dairy, Gluten (or None)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Pickup Instructions for Volunteer</label>
                                <input type="text" name="pickup_instructions" class="form-control form-control-sm" value="{{ old('pickup_instructions', $donation->pickup_instructions) }}" placeholder="e.g. Enter through kitchen back gate">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $donation->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pickup Address</label>
                        <textarea name="pickup_address" class="form-control" rows="2" required>{{ old('pickup_address', $donation->pickup_address) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('restaurant.donations.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
