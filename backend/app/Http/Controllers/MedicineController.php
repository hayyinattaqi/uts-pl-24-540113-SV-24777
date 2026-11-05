<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $medicines = Medicine::query()
            ->withTotalStock()
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => MedicineResource::collection($medicines)->resolve($request),
            'message' => 'Daftar obat berhasil diambil.',
        ]);
    }

    public function show(Request $request, Medicine $medicine): JsonResponse
    {
        $medicine = $this->withStock($medicine)->load([
            'batches' => fn ($query) => $query->orderBy('expiry_date'),
        ]);

        return response()->json([
            'data' => (new MedicineResource($medicine))->resolve($request),
            'message' => 'Detail obat berhasil diambil.',
        ]);
    }

    public function store(MedicineRequest $request): JsonResponse
    {
        try {
            $medicine = Medicine::create($request->validated());

            Log::info('Data obat baru berhasil disimpan ke basis data.', [
                'action' => 'store_medicine',
                'user_id' => $request->user()?->id ?? 'system',
                'ip' => $request->ip(),
                'medicine_id' => $medicine->id,
                'medicine_code' => $medicine->code,
                'reason' => 'Pengguna menambahkan item obat baru ke inventaris klinik.',
            ]);

            return response()->json([
                'data' => (new MedicineResource($this->withStock($medicine)))->resolve($request),
                'message' => 'Obat berhasil ditambahkan.',
            ], 201);
        } catch (QueryException $e) {
            Log::error('Gagal menghubungi basis data saat menyimpan obat.', [
                'action' => 'store_medicine',
                'user_id' => $request->user()?->id ?? 'system',
                'ip' => $request->ip(),
                'error_code' => $e->getCode(),
                'reason' => 'Terjadi kegagalan komunikasi atau eksekusi query pada basis data: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function update(MedicineRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine->update($request->validated());

        return response()->json([
            'data' => (new MedicineResource($this->withStock($medicine)))->resolve($request),
            'message' => 'Obat berhasil diperbarui.',
        ]);
    }

    public function destroy(Medicine $medicine): JsonResponse
    {
        if ($medicine->batches()->exists()) {
            return response()->json([
                'message' => 'Obat tidak dapat dihapus karena masih memiliki batch.',
            ], 409);
        }

        $medicine->delete();

        return response()->json([
            'message' => 'Obat berhasil dihapus.',
        ]);
    }

    private function withStock(Medicine $medicine): Medicine
    {
        return Medicine::query()->withTotalStock()->findOrFail($medicine->id);
    }
}
