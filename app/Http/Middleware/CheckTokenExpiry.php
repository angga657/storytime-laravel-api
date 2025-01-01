<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token && $token->expires_at && Carbon::now()->greaterThan(Carbon::parse($token->expires_at))) {
            $token->delete(); // Hapus token jika sudah kedaluwarsa
            return response()->json([
                'status' => 'Gagal',
                'message' => 'Token expired. Please login again.',
            ], 401);
        }

        return $next($request);
    }
}
