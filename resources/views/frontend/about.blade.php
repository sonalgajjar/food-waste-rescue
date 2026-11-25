@extends('layouts.frontend')

@section('title', 'About Us — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">About Food Waste Rescue</h1>
        <p class="lead opacity-90 mb-0">Bridging the gap between food abundance and community hunger through smart technology.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-success fw-bold text-uppercase small">Our Mission</span>
                <h2 class="fw-bold mb-3">Zero Food Waste, Zero Hunger</h2>
                <p class="text-secondary leading-relaxed">
                    Millions of tons of perfectly wholesome food are discarded every day by commercial kitchens, restaurants, and event caterers while millions of people suffer from nutritional insufficiency.
                </p>
                <p class="text-secondary leading-relaxed">
                    Food Waste Rescue provides a automated, real-time digital bridging platform that empowers donors to broadcast surplus food in seconds, while empowering registered NGOs and relief organizations to claim and collect fresh food safely.
                </p>
                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <div class="border-start border-4 border-success ps-3">
                            <h4 class="fw-bold text-dark mb-0">100%</h4>
                            <small class="text-muted">Non-Profit Focus</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-start border-4 border-success ps-3">
                            <h4 class="fw-bold text-dark mb-0">Real-Time</h4>
                            <small class="text-muted">GPS Matching</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bg-light p-5 rounded-4 border text-center">
                    <div class="display-1 text-success mb-3"><i class="fas fa-heartbeat"></i></div>
                    <h4 class="fw-bold">Our Core Philosophy</h4>
                    <p class="text-muted small">
                        "Food is a fundamental human right. Waste is an oversight that technology and community collaboration can eliminate entirely."
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
