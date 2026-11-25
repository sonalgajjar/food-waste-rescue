<nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-100 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    <div class="h-10 w-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:scale-105 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707m11.314 11.314l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tight bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent group-hover:opacity-90 transition-opacity">
                        RescueShare
                    </span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' }} text-sm transition-colors duration-200">Home</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' }} text-sm transition-colors duration-200">About Us</a>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' }} text-sm transition-colors duration-200">Contact</a>
                
                @auth
                    <a href="{{ route('dashboard') }}" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium text-sm hover:bg-emerald-700 shadow-md shadow-emerald-600/10 hover:shadow-lg hover:shadow-emerald-600/20 transition-all duration-200">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-slate-700 hover:text-emerald-600 text-sm font-medium transition-colors duration-200">Login</a>
                    <a href="{{ route('register') }}" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium text-sm hover:bg-emerald-700 shadow-md shadow-emerald-600/10 hover:shadow-lg hover:shadow-emerald-600/20 transition-all duration-200">Register</a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden" x-data="{ open: false }">
                <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-emerald-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!open">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="open" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Mobile Menu Panel -->
                <div x-show="open" @click.away="open = false" class="absolute top-20 left-0 w-full bg-white border-b border-slate-100 shadow-lg py-4 px-6 space-y-3 flex flex-col md:hidden transition-all duration-300">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-emerald-600 font-semibold' : 'text-slate-600' }} text-base py-2">Home</a>
                    <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-emerald-600 font-semibold' : 'text-slate-600' }} text-base py-2">About Us</a>
                    <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-emerald-600 font-semibold' : 'text-slate-600' }} text-base py-2">Contact</a>
                    <hr class="border-slate-100">
                    @auth
                        <a href="{{ route('dashboard') }}" class="bg-emerald-600 text-white text-center py-2.5 rounded-xl font-medium text-base">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-center text-slate-700 font-medium py-2">Login</a>
                        <a href="{{ route('register') }}" class="bg-emerald-600 text-white text-center py-2.5 rounded-xl font-medium text-base">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>
