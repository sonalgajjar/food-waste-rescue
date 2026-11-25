@extends('layouts.frontend')

@section('title', 'Privacy Policy — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">Privacy Policy</h1>
        <p class="lead opacity-90 mb-0">Your privacy and data protection are paramount to us.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container max-w-4xl">
        <h3 class="fw-bold mb-3">1. Information Collection</h3>
        <p class="text-secondary leading-relaxed">
            Food Waste Rescue collects user account details (name, email address, phone number, physical address, and GPS coordinates) necessary to facilitate food surplus matching and logistics between registered donors and NGOs.
        </p>

        <h3 class="fw-bold mb-3 mt-4">2. Use of Geolocation Data</h3>
        <p class="text-secondary leading-relaxed">
            GPS coordinates uploaded by donors and NGOs are exclusively utilized to display nearby food listings on interactive OpenStreetMap Leaflet layers. We do not sell or monetize location data.
        </p>

        <h3 class="fw-bold mb-3 mt-4">3. Data Security & Storage</h3>
        <p class="text-secondary leading-relaxed">
            All user credentials and passwords are encrypted using strong bcrypt hashing. Storage files are secured using standard Laravel filesystem abstractions and policy authorization checks.
        </p>
    </div>
</section>
@endsection
