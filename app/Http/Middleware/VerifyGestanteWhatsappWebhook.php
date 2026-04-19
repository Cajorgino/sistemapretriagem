<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGestanteWhatsappWebhook
{
    /**
     * Valida token opcional (WAHA/n8n). Se WAHA_WEBHOOK_SECRET não estiver definido, o webhook permanece aberto.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.waha.webhook_secret');

        if ($secret === null || $secret === '') {
            return $next($request);
        }

        $header = (string) $request->header('X-Webhook-Secret', '');
        $bearer = (string) ($request->bearerToken() ?? '');

        $ok = hash_equals((string) $secret, $header) || hash_equals((string) $secret, $bearer);

        if (! $ok) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
