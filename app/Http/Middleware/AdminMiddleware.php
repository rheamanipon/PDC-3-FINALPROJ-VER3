<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 1. Add this import
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // This will force the error page to show us what's happening
        if (!$user->isAdmin()) {
            $actualRole = $user->role ?? 'NULL';
            abort(403, "Access Denied! Your role is: '{$actualRole}'. Checking against: 'admin'");
        }

        return $next($request);
    }
}