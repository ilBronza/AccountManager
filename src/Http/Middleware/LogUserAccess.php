<?php

namespace IlBronza\AccountManager\Http\Middleware;

use Closure;
use IlBronza\AccountManager\Models\UserAccessLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra gli accessi in terminate() così la request è già passata da auth:
 * può essere aggiunto al gruppo middleware `web` dell’applicazione senza ordinare
 * le route (non va messo nel file delle route del pacchetto).
 */
class LogUserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('accountmanager.logUserAccess.enabled', true)) {
            return;
        }

        if ($request->ajax()) {
            return;
        }

        if (! $request->user()) {
            return;
        }

        UserAccessLog::query()->create([
            'user_id' => $request->user()->getKey(),
            'visited_at' => now(),
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
