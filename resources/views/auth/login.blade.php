<x-guest-layout>
    {{-- Back to Home --}}
    <div class="mb-3">
        <a href="{{ route('home') }}" class="btn btn-light btn-sm border d-inline-flex align-items-center gap-1 rounded-3">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <h4 class="fw-bold text-center mb-1 text-dark">Welcome Back</h4>
    <p class="text-muted small text-center mb-4">Log in to manage your food donations or claim listings</p>

    <!-- Session Status & Flash Messages -->
    @if (session('status'))
        <div class="alert alert-success small mb-4">
            {{ session('status') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success small mb-4">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning small mb-4">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('warning') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger small mb-4">
            <i class="fas fa-times-circle me-1"></i> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="needs-validation">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold small text-muted">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label fw-semibold small text-muted mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-decoration-none small text-success" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-4 form-check">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label small text-muted">
                Remember me on this device
            </label>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2.5 fw-bold rounded-3">
            <i class="fas fa-sign-in-alt me-2"></i> Log In
        </button>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="small text-muted mb-0">Don't have an account? 
                <a href="{{ route('register') }}" class="text-success fw-bold text-decoration-none">Register Now</a>
            </p>
        </div>
    </form>
</x-guest-layout>
