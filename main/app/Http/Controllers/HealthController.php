<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
    }

    public function ready(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
        } catch (Throwable) {
            // Provider exceptions can contain credentials, SQL, or internal hosts.
            Log::warning('Database readiness check failed.');

            return response()->json(['status' => 'unavailable'], 503)->header('Cache-Control', 'no-store');
        }

        return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
    }
}
