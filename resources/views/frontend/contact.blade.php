@extends('layouts.frontend')

@section('title', 'Contact Us — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">Get In Touch</h1>
        <p class="lead opacity-90 mb-0">Have questions, feedback, or want to partner with us? We'd love to hear from you.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4 text-dark">Send Us A Message</h3>
                <form action="{{ route('contact.store') }}" method="POST" class="needs-validation">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" placeholder="Partnership Inquiry" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" placeholder="How can we assist you?" required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-rescue btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i> Submit Message
                    </button>
                </form>
            </div>

            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <h4 class="fw-bold mb-4 text-dark">Contact Details</h4>
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="bg-success text-white rounded-circle p-3">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Headquarters</h6>
                            <p class="text-muted small mb-0">100 Rescue Way, Sector 12, Metropolis City, 100001</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="bg-success text-white rounded-circle p-3">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Email Us</h6>
                            <p class="text-muted small mb-0">support@foodrescue.com / contact@foodrescue.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="bg-success text-white rounded-circle p-3">
                            <i class="fas fa-phone fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Call Us</h6>
                            <p class="text-muted small mb-0">+1 800-555-0199 (Mon-Sat, 9AM - 8PM)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
