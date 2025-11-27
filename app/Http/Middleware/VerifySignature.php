<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * VerifySignature Middleware
 * 
 * Implements two-layer security:
 * 1. API Key verification (Authorization: Bearer)
 * 2. HMAC-SHA256 signature verification (X-SIGNATURE header)
 * 
 * This is how Selcom, Stripe, and other payment gateways secure their APIs.
 */
class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Step 1: Verify API Key (Bearer Token)
        // This checks "WHO is making the request"
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

        // Step 2: Verify HMAC Signature (only for requests with body)
        // GET/DELETE requests typically don't have bodies, so skip signature check
        // This checks "Has the request been tampered with?"
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

        // Both checks passed - allow request to proceed
        return $next($request);
    }

    /**
     * Verify API Key from Authorization header
     * 
     * Expected format: Authorization: Bearer sk_live_selcom_test_key_12345678
     */
    private function verifyApiKey(Request $request): bool
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return false;
        }

        // Extract token from "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }

        $providedKey = $matches[1];
        $validKey = config('app.api_key', env('API_KEY'));

        // Use hash_equals to prevent timing attacks
        // Interview gold: "Why hash_equals?" → "Constant-time comparison"
        return hash_equals($validKey, $providedKey);
    }

    /**
     * Verify HMAC-SHA256 Signature
     * 
     * How it works:
     * 1. Get raw request body (JSON)
     * 2. Compute HMAC-SHA256 using API_SECRET
     * 3. Compare with X-SIGNATURE header
     * 
     * This ensures:
     * - Request body hasn't been modified in transit
     * - Request comes from someone who knows the secret
     */
    private function verifyHmacSignature(Request $request): bool
    {
        // Get signature from header
        $providedSignature = $request->header('X-SIGNATURE');
        
        if (!$providedSignature) {
            return false;
        }

        // Get raw request body (JSON string)
        $payload = $request->getContent();
        
        // Compute expected signature using HMAC-SHA256
        $secret = config('app.api_secret', env('API_SECRET'));
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Compare signatures (constant-time to prevent timing attacks)
        return hash_equals($expectedSignature, $providedSignature);
    }
}

