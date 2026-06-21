<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => '未授权'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['message' => '没有权限访问'], 403);
        }

        return $next($request);
    }
}
