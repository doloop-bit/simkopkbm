<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce role selection for protected routes (admin, teacher, financial, etc.)
        // and avoid redirecting for public routes or the select-role page itself.
        $isProtectedRoute = $request->is('admin*', 'teacher*', 'financial*', 'ptk*', 'settings*', 'users*', 'registrations*');

        if ($user && $isProtectedRoute && ! $request->session()->has('active_role_id')) {
            $roles = $user->roles;

            if ($roles->count() === 1) {
                // Automatically set if only one role
                $request->session()->put('active_role_id', $roles->first()->id);
            } elseif ($roles->count() > 1 && ! $request->routeIs('select-role')) {
                // Redirect to selection if multiple roles and not already there
                return redirect()->route('select-role');
            }
        }

        return $next($request);
    }
}
