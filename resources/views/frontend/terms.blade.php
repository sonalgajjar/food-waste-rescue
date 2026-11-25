@extends('layouts.frontend')

@section('title', 'Terms & Conditions — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">Terms & Conditions</h1>
        <p class="lead opacity-90 mb-0">Rules and guidelines governing the use of Food Waste Rescue platform.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container max-w-4xl">
        <h3 class="fw-bold mb-3">1. Food Hygiene & Safety Compliance</h3>
        <p class="text-secondary leading-relaxed">
            All registered donor restaurants, caterers, and bakeries warrant that any food listed for redistribution is wholesome, edible, stored under sanitary conditions, and accurately labeled with preparation and expiration times.
        </p>

        <h3 class="fw-bold mb-3 mt-4">2. Non-Commercial Redistribution</h3>
        <p class="text-secondary leading-relaxed">
            NGOs claiming food donations agree that all items will be redistributed free of charge to communities, shelters, and individuals in need. Resale or commercial exploitation of rescued food is strictly prohibited.
        </p>

        <h3 class="fw-bold mb-3 mt-4">3. Account Suspension</h3>
        <p class="text-secondary leading-relaxed">
            System administrators reserve the right to suspend or deactivate any account found to violate food safety standards, post fraudulent listings, or engage in abusive platform behavior.
        </p>
    </div>
</section>
@endsection
