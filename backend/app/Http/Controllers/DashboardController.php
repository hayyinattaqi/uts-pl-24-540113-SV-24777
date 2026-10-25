<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Http\Resources\MedicineResource;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StockService $stock): JsonResponse
    {
        $dashboard = $stock->dashboard();

        return response()->json([
            'data' => [
                'near_expiry' => BatchResource::collection($dashboard['near_expiry'])->resolve($request),
                'expired' => BatchResource::collection($dashboard['expired'])->resolve($request),
                'low_stock' => MedicineResource::collection($dashboard['low_stock'])->resolve($request),
            ],
            'message' => 'Data dashboard berhasil diambil.',
        ]);
    }
}
