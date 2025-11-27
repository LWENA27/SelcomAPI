<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->verifyApiKey($request)) {
            Log::warning('API Key verification failed', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        // Only verify signature for POST/PUT/PATCH requests
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            if (!$this->verifyHmacSignature($request)) {
                Log::warning('HMAC signature verification failed', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'signature_sent' => $request->header('X-SIGNATURE'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }
        }

        return $next($request);
    }

    private function verifyApiKey(Request $request): bool
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return false;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }

        $providedKey = $matches[1];
        $validKey = config('app.api_key', env('API_KEY'));

        return hash_equals($validKey, $providedKey);
    }

    private function verifyHmacSignature(Request $request): bool
    {
        $providedSignature = $request->header('X-SIGNATURE');
        
        if (!$providedSignature) {
            return false;
        }

        $payload = $request->getContent();
        $secret = config('app.api_secret', env('API_SECRET'));
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $providedSignature);
    }
}

