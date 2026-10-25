<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Resources\BatchResource;
use App\Http\Resources\StockMovementResource;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function stockIn(StockInRequest $request): JsonResponse
    {
        $result = $this->stock->stockIn($request->validated(), $request->user());

        return response()->json([
            'data' => [
                'batch' => (new BatchResource($result['batch']))->resolve($request),
                'movement' => (new StockMovementResource($result['movement']))->resolve($request),
            ],
            'message' => 'Obat masuk berhasil dicatat.',
        ], 201);
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        $movement = $this->stock->stockOut(
            (int) $request->validated('batch_id'),
            (int) $request->validated('quantity'),
            $request->validated('notes'),
            $request->user(),
        );

        return response()->json([
            'data' => (new StockMovementResource($movement))->resolve($request),
            'message' => 'Obat keluar berhasil dicatat.',
        ], 201);
    }

    public function movements(StockMovementIndexRequest $request): JsonResponse
    {
        $movements = $this->stock->latestMovements($request->validated('type'));

        return response()->json([
            'data' => StockMovementResource::collection($movements)->resolve($request),
            'message' => 'Riwayat transaksi berhasil diambil.',
        ]);
    }
}
