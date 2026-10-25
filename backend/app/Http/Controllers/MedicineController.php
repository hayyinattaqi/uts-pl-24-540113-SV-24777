<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $medicines = Medicine::query()
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
        return response()->json([
            'data' => (new MedicineResource($medicine))->resolve($request),
            'message' => 'Detail obat berhasil diambil.',
        ]);
    }

    public function store(MedicineRequest $request): JsonResponse
    {
        $medicine = Medicine::create($request->validated());

        return response()->json([
            'data' => (new MedicineResource($medicine))->resolve($request),
            'message' => 'Obat berhasil ditambahkan.',
        ], 201);
    }

    public function update(MedicineRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine->update($request->validated());

        return response()->json([
            'data' => (new MedicineResource($medicine->refresh()))->resolve($request),
            'message' => 'Obat berhasil diperbarui.',
        ]); 
    }

    public function destroy(Medicine $medicine): JsonResponse
    {
        $medicine->delete();

        return response()->json([
            'message' => 'Obat berhasil dihapus.',
        ]);
    }
}
