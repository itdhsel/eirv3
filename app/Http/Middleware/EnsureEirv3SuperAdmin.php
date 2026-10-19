<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEirv3SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is logged in and has the exact 'superadmin' role slug
        if ($request->user() && $request->user()->role && $request->user()->role->slug === 'superadmin') {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}