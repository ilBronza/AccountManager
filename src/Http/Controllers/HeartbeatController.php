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

        $userId = $request->input('user_id');

        if ($userId === null || $userId === '' || ! is_scalar($userId)) {
            return response()->json(['ok' => false], 422);
        }

        $ttl = (int) config('accountmanager.heartbeat.ttl_seconds', 60);

        Cache::put('heartbeat-' . $userId, true, $ttl);

        return response()->json(['ok' => true]);
    }
}
