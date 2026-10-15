<?php

namespace App\Http\Middleware;

use App\Support\Eirv3Access;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEirv3SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Eirv3Access::role($request->user()) === 'super_admin', 403);

        return $next($request);
    }
}
