<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Periksa kesehatan aplikasi: basis data dan cache secara nyata.
     */
    public function check(Request $request): JsonResponse
    {
        $status = [
            'status' => 'healthy',
            'database' => 'ok',
            'cache' => 'ok',
        ];

        $errors = [];

        // 1. Periksa koneksi dan query ke basis data
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $status['database'] = 'ok';
        } catch (Throwable $e) {
            $status['database'] = 'unhealthy';
            $errors['database'] = 'Gagal menghubungi basis data: ' . $e->getMessage();

            Log::error('Healthcheck: Gagal menghubungi basis data.', [
                'action' => 'health_check_database',
                'user_id' => 'system',
                'ip' => $request->ip(),
                'error_code' => $e->getCode(),
                'reason' => $e->getMessage(),
            ]);
        }

        // 2. Periksa fungsi cache (read, write, delete)
        try {
            $probeKey = 'health_probe_' . bin2hex(random_bytes(4));
            Cache::put($probeKey, 'working', 10);
            $probeValue = Cache::get($probeKey);
            Cache::forget($probeKey);

            if ($probeValue !== 'working') {
                throw new \RuntimeException('Cache probe mismatch: nilai yang dibaca tidak sesuai.');
            }

            $status['cache'] = 'ok';
        } catch (Throwable $e) {
            $status['cache'] = 'unhealthy';
            $errors['cache'] = 'Gagal melakukan operasi cache: ' . $e->getMessage();

            Log::error('Healthcheck: Gagal melakukan operasi cache.', [
                'action' => 'health_check_cache',
                'user_id' => 'system',
                'ip' => $request->ip(),
                'reason' => $e->getMessage(),
            ]);
        }

        // 3. Jika ada layanan yang bermasalah, kembalikan 503 dengan rincian
        if (!empty($errors)) {
            $status['status'] = 'unhealthy';
            $status['errors'] = $errors;

            return response()->json($status, 503);
        }

        return response()->json($status, 200);
    }
}

