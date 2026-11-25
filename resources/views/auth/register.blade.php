@section('card_width', '640px')

<x-guest-layout>
<style>
    .role-card { cursor: pointer; border: 2px solid #dee2e6; border-radius: .75rem; padding: 1rem; text-align: center; transition: all .2s ease; }
    .role-card:hover { border-color: #198754; background: #f0fdf4; }
    .role-card.selected { border-color: #198754; background: #d1fae5; color: #065f46; }
    .role-card i { font-size: 1.75rem; display: block; margin-bottom: .4rem; }
    .section-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 2px solid #e2e8f0; padding-bottom: .4rem; margin-bottom: 1rem; }
</style>

<h4 class="fw-bold text-center mb-1 text-dark">Create Partner Account</h4>
<p class="text-muted small text-center mb-4">Join as a Donor Restaurant or a verified NGO to start rescuing food</p>

@if ($errors->any())
    <div class="alert alert-danger mb-4 shadow-sm border-0">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i>Please correct the following:</div>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $selectedRole = old('role', 'restaurant');
@endphp

<form method="POST" action="{{ route('register') }}" id="registerForm" enctype="multipart/form-data">
    @csrf

    {{-- Step 1: Role Selection --}}
    <div class="mb-4">
        <div class="section-title">Choose Your Role</div>
        <div class="row g-3">
            <div class="col-6">
                <div class="role-card {{ $selectedRole === 'restaurant' ? 'selected' : '' }}" id="card-restaurant" onclick="selectRole('restaurant')">
                    <i class="fas fa-utensils text-success"></i>
                    <div class="fw-bold small">Restaurant / Donor</div>
                    <div class="text-muted" style="font-size:.7rem;">Share surplus food</div>
                </div>
            </div>
            <div class="col-6">
                <div class="role-card {{ $selectedRole === 'ngo' ? 'selected' : '' }}" id="card-ngo" onclick="selectRole('ngo')">
                    <i class="fas fa-hands-helping text-success"></i>
                    <div class="fw-bold small">NGO / Distributor</div>
                    <div class="text-muted" style="font-size:.7rem;">Collect & distribute food</div>
                </div>
            </div>
        </div>
        <input type="hidden" name="role" id="roleInput" value="{{ $selectedRole }}">
    </div>

    {{-- Common Fields --}}
    <div class="section-title">Account Information</div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label class="form-label fw-semibold small">Contact Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="John Doe" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="john@example.com" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="Min 8 characters" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-semibold small">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small">Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone') }}" placeholder="+91 99999 99999" required>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Restaurant Fields --}}
    <div id="restaurantFields">
        <div class="section-title">Restaurant Details</div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">Restaurant Name <span class="text-danger">*</span></label>
                <input type="text" id="restaurant_name" name="restaurant_name"
                       class="form-control @error('restaurant_name') is-invalid @enderror"
                       value="{{ old('restaurant_name') }}" placeholder="e.g. Green Grill" required>
                @error('restaurant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">Owner Name <span class="text-danger">*</span></label>
                <input type="text" id="owner_name_rest" name="owner_name"
                       class="form-control @error('owner_name') is-invalid @enderror"
                       value="{{ old('owner_name') }}" placeholder="Owner full name" required>
                @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">FSSAI License No. <span class="text-danger">*</span></label>
                <input type="text" id="license_number" name="license_number"
                       class="form-control @error('license_number') is-invalid @enderror"
                       value="{{ old('license_number') }}" placeholder="e.g. FSSAI-12345" required>
                @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">License Expiry Date</label>
                <input type="date" name="license_expires_at" class="form-control @error('license_expires_at') is-invalid @enderror"
                       value="{{ old('license_expires_at') }}" min="{{ date('Y-m-d') }}">
                @error('license_expires_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small">FSSAI License Document <span class="text-muted fw-normal">(PDF, JPG, PNG - Max 5MB)</span></label>
                <input type="file" name="license_document" class="form-control @error('license_document') is-invalid @enderror" accept=".pdf,image/*">
                @error('license_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">GST Number <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="gst_number" class="form-control"
                       value="{{ old('gst_number') }}" placeholder="e.g. 29AAAAA0000A1Z1">
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">Opening Time</label>
                <input type="time" name="opening_time" class="form-control" value="09:00" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">Closing Time</label>
                <input type="time" name="closing_time" class="form-control" value="22:00" required>
            </div>
        </div>
    </div>

    {{-- NGO Fields --}}
    <div id="ngoFields" class="d-none">
        <div class="section-title">NGO Details</div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">NGO Name <span class="text-danger">*</span></label>
                <input type="text" id="ngo_name" name="ngo_name"
                       class="form-control @error('ngo_name') is-invalid @enderror"
                       value="{{ old('ngo_name') }}" placeholder="e.g. Hope Alliance">
                @error('ngo_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">Registration No. <span class="text-danger">*</span></label>
                <input type="text" id="registration_number" name="registration_number"
                       class="form-control @error('registration_number') is-invalid @enderror"
                       value="{{ old('registration_number') }}" placeholder="e.g. NGO-98765-IN">
                @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold small">FCRA Registration No. <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="fcra_number" class="form-control @error('fcra_number') is-invalid @enderror"
                       value="{{ old('fcra_number') }}" placeholder="e.g. FCRA-12345678">
                @error('fcra_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small">NGO Registration Certificate <span class="text-muted fw-normal">(PDF, JPG, PNG - Max 5MB)</span></label>
                <input type="file" name="registration_document" class="form-control @error('registration_document') is-invalid @enderror" accept=".pdf,image/*">
                @error('registration_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small">Representative Name <span class="text-danger">*</span></label>
                <input type="text" id="owner_name_ngo" name="owner_name_ngo"
                       class="form-control" placeholder="e.g. Daniel Davis">
            </div>
        </div>
    </div>

    {{-- Address & Location --}}
    <div class="section-title">Address & Location</div>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label fw-semibold small">Street Address <span class="text-danger">*</span></label>
            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                   value="{{ old('address') }}" placeholder="123 Hope Lane, Sector 5" required>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-semibold small">City <span class="text-danger">*</span></label>
            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                   value="{{ old('city') }}" placeholder="e.g. New Delhi" required>
            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-semibold small">State <span class="text-danger">*</span></label>
            <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
                   value="{{ old('state') }}" placeholder="e.g. Delhi" required>
            @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-semibold small">Pincode <span class="text-danger">*</span></label>
            <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror"
                   value="{{ old('pincode') }}" placeholder="e.g. 110001" required>
            @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-semibold small mb-0">GPS Coordinates <span class="text-muted fw-normal">(Optional, for nearby search)</span></label>
                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11px;" onclick="detectLocation()">
                    <i class="fas fa-crosshairs me-1"></i> Detect My Location
                </button>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="any" id="latInput" name="latitude" class="form-control form-control-sm" value="{{ old('latitude') }}" placeholder="Latitude (e.g. 28.6139)">
                </div>
                <div class="col-6">
                    <input type="number" step="any" id="lonInput" name="longitude" class="form-control form-control-sm" value="{{ old('longitude') }}" placeholder="Longitude (e.g. 77.2090)">
                </div>
            </div>
            <div id="geoStatus" class="small text-muted mt-1" style="font-size: 11px;"></div>
        </div>

    </div>

    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
        <i class="fas fa-user-plus me-2"></i> Create Account & Submit for Verification
    </button>

    <div class="text-center mt-3 pt-3 border-top">
        <p class="small text-muted mb-0">Already have an account?
            <a href="{{ route('login') }}" class="text-success fw-bold text-decoration-none">Log In</a>
        </p>
    </div>
</form>

<script>
function selectRole(role) {
    document.getElementById('roleInput').value = role;

    const cardR = document.getElementById('card-restaurant');
    const cardN = document.getElementById('card-ngo');
    const restFields = document.getElementById('restaurantFields');
    const ngoFields  = document.getElementById('ngoFields');

    if (role === 'restaurant') {
        cardR.classList.add('selected');
        cardN.classList.remove('selected');
        restFields.classList.remove('d-none');
        ngoFields.classList.add('d-none');

        document.getElementById('restaurant_name').setAttribute('required', '');
        document.getElementById('license_number').setAttribute('required', '');
        document.getElementById('owner_name_rest').setAttribute('required', '');
        document.getElementById('ngo_name').removeAttribute('required');
        document.getElementById('registration_number').removeAttribute('required');
    } else {
        cardN.classList.add('selected');
        cardR.classList.remove('selected');
        ngoFields.classList.remove('d-none');
        restFields.classList.add('d-none');

        document.getElementById('ngo_name').setAttribute('required', '');
        document.getElementById('registration_number').setAttribute('required', '');
        document.getElementById('restaurant_name').removeAttribute('required');
        document.getElementById('license_number').removeAttribute('required');
        document.getElementById('owner_name_rest').removeAttribute('required');
    }
}

function detectLocation() {
    const status = document.getElementById('geoStatus');
    if (!navigator.geolocation) {
        status.innerHTML = '<span class="text-danger">Geolocation is not supported by your browser.</span>';
        return;
    }

    status.innerHTML = '<span class="text-primary"><i class="fas fa-spinner fa-spin me-1"></i> Detecting current location...</span>';

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('latInput').value = position.coords.latitude.toFixed(6);
            document.getElementById('lonInput').value = position.coords.longitude.toFixed(6);
            status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Coordinates detected successfully!</span>';
        },
        function(error) {
            status.innerHTML = '<span class="text-warning">Could not retrieve GPS: ' + error.message + '. You may enter manually or rely on address.</span>';
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

document.addEventListener('DOMContentLoaded', function() {
    selectRole('{{ $selectedRole }}');
});
</script>
</x-guest-layout>
