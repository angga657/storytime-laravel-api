<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ProxyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forward request to external API
        $response = Http::withHeaders($request->headers->all())
            ->post('https://e820-103-100-175-121.ngrok-free.app' . $request->getPathInfo(), $request->all());

        return response($response->body(), $response->status(), $response->headers());
    }
}
