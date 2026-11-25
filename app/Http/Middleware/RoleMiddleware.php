<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is logged in
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Check if user has one of the allowed roles
        if (! in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized access. You do not have the required permissions.');
        }

        // Check if user account is active
        if ($request->user()->status !== 'active') {
            $status = $request->user()->status;
            auth()->logout();

            $message = match ($status) {
                'pending' => 'Your account is pending administrator approval. Please wait for activation.',
                'rejected' => 'Your account application has been rejected by the administrator.',
                default => 'Your account is currently inactive. Please contact the administrator.',
            };

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
