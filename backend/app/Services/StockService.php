<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Medicine;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Catat obat masuk: buat batch baru beserta movement "in".
     *
     * @param  array{medicine_id: int, batch_number: string, expiry_date: string, quantity: int, notes?: string|null}  $data
     * @return array{batch: Batch, movement: StockMovement}
     */
    public function stockIn(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            // Kunci baris obat agar dua stok masuk untuk obat yang sama berjalan berurutan.
            Medicine::query()->whereKey($data['medicine_id'])->lockForUpdate()->firstOrFail();

            $batch = Batch::create([
                'medicine_id' => $data['medicine_id'],
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'initial_quantity' => $data['quantity'],
                'current_quantity' => $data['quantity'],
            ]);

            $movement = $this->recordMovement($batch, $user, StockMovement::TYPE_IN, $data['quantity'], $data['notes'] ?? null);

            return ['batch' => $batch->load('medicine'), 'movement' => $movement];
        });
    }

    /**
     * Catat obat keluar dari batch yang dipilih petugas.
     *
     * @throws ModelNotFoundException jika batch tidak ada (404)
     * @throws ValidationException jika batch kedaluwarsa atau stok tidak cukup (422)
     */
    public function stockOut(int $batchId, int $quantity, ?string $notes, User $user): StockMovement
    {
        return DB::transaction(function () use ($batchId, $quantity, $notes, $user): StockMovement {
            $batch = Batch::query()->lockForUpdate()->findOrFail($batchId);

            if ($batch->isExpired()) {
                throw ValidationException::withMessages([
                    'batch_id' => 'Batch sudah kedaluwarsa dan tidak dapat dikeluarkan.',
                ]);
            }

            if ($quantity > $batch->current_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Jumlah melebihi stok batch (sisa {$batch->current_quantity})",
                ]);
            }

            $batch->decrement('current_quantity', $quantity);

            $movement = $this->recordMovement($batch, $user, StockMovement::TYPE_OUT, $quantity, $notes);

            return $movement->load('batch.medicine', 'user');
        });
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function latestMovements(?string $type, int $limit = 100): Collection
    {
        return StockMovement::query()
            ->with(['batch.medicine', 'user'])
            ->when($type, fn ($query, string $type) => $query->where('type', $type))
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{near_expiry: Collection<int, Batch>, expired: Collection<int, Batch>, low_stock: \Illuminate\Support\Collection<int, Medicine>}
     */
    public function dashboard(): array
    {
        $today = today()->toDateString();
        $nearExpiryLimit = today()->addDays(config('inventory.near_expiry_days'))->toDateString();

        $availableBatches = fn () => Batch::query()
            ->with('medicine')
            ->where('current_quantity', '>', 0)
            ->orderBy('expiry_date');

        return [
            'near_expiry' => $availableBatches()
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $nearExpiryLimit)
                ->get(),
            'expired' => $availableBatches()
                ->whereDate('expiry_date', '<', $today)
                ->get(),
            'low_stock' => Medicine::query()
                ->withTotalStock()
                ->where('min_stock', '>', 0)
                ->orderBy('name')
                ->get()
                ->filter(fn (Medicine $medicine) => $medicine->isLowStock())
                ->values(),
        ];
    }

    private function recordMovement(Batch $batch, User $user, string $type, int $quantity, ?string $notes): StockMovement
    {
        return StockMovement::create([
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity' => $quantity,
            'notes' => $notes,
            'moved_at' => now(),
        ]);
    }
}
