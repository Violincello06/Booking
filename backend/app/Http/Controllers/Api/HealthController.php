<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    /**
     * Check health status of API, Database, and Sanctum.
     */
    public function __invoke(): JsonResponse
    {
        $dbConnected = false;
        $dbError = null;

        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        $sanctumReady = Schema::hasTable('personal_access_tokens');

        $isHealthy = $dbConnected && $sanctumReady;

        return response()->json([
            'success' => $isHealthy,
            'message' => $isHealthy 
                ? 'Sistem Travel Booking API berjalan dengan baik' 
                : 'Sistem mengalami kendala',
            'data' => [
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'app_name' => config('app.name'),
                'environment' => config('app.env'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'database' => [
                    'connected' => $dbConnected,
                    'driver' => config('database.default'),
                    'database_name' => DB::connection()->getDatabaseName(),
                    'error' => $dbError,
                ],
                'sanctum' => [
                    'installed' => class_exists(\Laravel\Sanctum\Sanctum::class),
                    'tokens_table_ready' => $sanctumReady,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ], $isHealthy ? 200 : 503);
    }
}
