<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'unit',
        'min_stock',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Tambahkan atribut total_stock: jumlah stok batch yang belum kedaluwarsa.
     */
    public function scopeWithTotalStock(Builder $query): void
    {
        $query->withSum([
            'batches as total_stock' => fn (Builder $batches) => $batches
                ->whereDate('expiry_date', '>=', today()->toDateString()),
        ], 'current_quantity');
    }

    public function totalStock(): int
    {
        return (int) ($this->total_stock ?? 0);
    }

    public function isLowStock(): bool
    {
        return $this->min_stock > 0 && $this->totalStock() <= $this->min_stock;
    }
}
