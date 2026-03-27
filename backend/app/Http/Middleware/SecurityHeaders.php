<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Remove server information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // HTTPS enforcement in production
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            
            // Content Security Policy
            $csp = "default-src 'self'; " .
                   "script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; " . // unsafe-inline/eval needed for Vite builds
                   "style-src 'self' https: 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; " .
                   "img-src 'self' data: https: blob:; " .
                   "font-src 'self' data: https: https://fonts.gstatic.com; " .
                   "connect-src 'self' https: wss: " . env('FRONTEND_URL', 'http://localhost:5173') . " " . env('APP_URL', 'http://localhost:8000') . "; " .
                   "frame-ancestors 'none'; " .
                   "base-uri 'self'; " .
                   "form-action 'self';";
            
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}

