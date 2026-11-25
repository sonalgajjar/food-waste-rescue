@extends('layouts.frontend')

@section('title', 'How It Works — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">How Food Waste Rescue Works</h1>
        <p class="lead opacity-90 mb-0">A transparent, step-by-step guide for Donors, NGOs, and Volunteers.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container max-w-4xl">
        <div class="row g-4 align-items-center mb-5">
            <div class="col-md-2 text-center">
                <span class="display-4 fw-black text-success">01</span>
            </div>
            <div class="col-md-10">
                <h3 class="fw-bold mb-2">Donor Registration & Verification</h3>
                <p class="text-secondary mb-0">
                    Restaurants, hotels, or caterers register with their business license/FSSAI details. System administrators review and activate accounts to maintain food quality standards.
                </p>
            </div>
        </div>

        <div class="row g-4 align-items-center mb-5">
            <div class="col-md-2 text-center">
                <span class="display-4 fw-black text-success">02</span>
            </div>
            <div class="col-md-10">
                <h3 class="fw-bold mb-2">Listing Surplus Food</h3>
                <p class="text-secondary mb-0">
                    Donors log surplus food items, specify cooked time, estimated shelf life, portion count, and confirm pickup location using our Leaflet map picker.
                </p>
            </div>
        </div>

        <div class="row g-4 align-items-center mb-5">
            <div class="col-md-2 text-center">
                <span class="display-4 fw-black text-success">03</span>
            </div>
            <div class="col-md-10">
                <h3 class="fw-bold mb-2">NGO Notification & Claim</h3>
                <p class="text-secondary mb-0">
                    Nearby NGOs receive instant notifications of available food in their vicinity. NGOs review food details and submit a pickup request.
                </p>
            </div>
        </div>

        <div class="row g-4 align-items-center">
            <div class="col-md-2 text-center">
                <span class="display-4 fw-black text-success">04</span>
            </div>
            <div class="col-md-10">
                <h3 class="fw-bold mb-2">Collection & Redistribution</h3>
                <p class="text-secondary mb-0">
                    Upon donor acceptance, NGO volunteers collect the packaged food and distribute it to shelters, community centers, or families immediately.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
