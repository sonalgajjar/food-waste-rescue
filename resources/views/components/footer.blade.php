<footer class="bg-slate-900 text-slate-300 pt-16 pb-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Brand -->
            <div class="space-y-4">
                <a href="{{ route('home') }}" class="flex items-center space-x-3">
                    <div class="h-9 w-9 bg-emerald-500 rounded-lg flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707m11.314 11.314l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>
                    </div>
                    <span class="font-extrabold text-xl tracking-tight text-white">
                        RescueShare
                    </span>
                </a>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Connecting restaurants with surplus food to NGOs and local communities in need. Together, we fight food waste and hunger.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4">
                <h4 class="text-white font-bold text-sm uppercase tracking-wider">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-emerald-400 transition-colors">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-emerald-400 transition-colors">Contact</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="space-y-4">
                <h4 class="text-white font-bold text-sm uppercase tracking-wider">For Partners</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('register') }}?role=restaurant" class="hover:text-emerald-400 transition-colors">Donate Food (Restaurants)</a></li>
                    <li><a href="{{ route('register') }}?role=ngo" class="hover:text-emerald-400 transition-colors">Claim Food (NGOs)</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-emerald-400 transition-colors">Partner Portal</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="space-y-4">
                <h4 class="text-white font-bold text-sm uppercase tracking-wider">Contact Info</h4>
                <ul class="space-y-3 text-sm text-slate-400">
                    <li class="flex items-center space-x-2">
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>123 Rescue Way, Green City</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>+1 (555) 019-2834</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>support@rescueshare.org</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="border-slate-800 my-8">

        <div class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} RescueShare. All rights reserved.</p>
            <div class="flex space-x-6 mt-4 md:mt-0">
                <a href="#" class="hover:text-emerald-400 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-emerald-400 transition-colors">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
