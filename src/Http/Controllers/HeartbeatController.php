<?php

namespace IlBronza\AccountManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HeartbeatController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('accountmanager.heartbeat.enabled', true)) {
            return response()->json(['ok' => false], 404);
        }

        $userId = $request->user()?->getAuthIdentifier();

        if ($userId === null || $userId === '' || ! is_scalar($userId)) {
            return response()->json(['ok' => false], 401);
        }

        $ttl = max(1, (int) config('accountmanager.heartbeat.ttl_seconds', 60));

        Cache::put('heartbeat-' . $userId, true, $ttl);

        return response()->json(['ok' => true]);
    }
}
