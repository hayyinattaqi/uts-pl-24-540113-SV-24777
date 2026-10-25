<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'medicine_id',
        'batch_number',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->lt(today());
    }

    /**
     * Status dihitung dari expiry_date: ok, near_expiry, atau expired.
     */
    public function status(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->expiry_date->lte(today()->addDays(config('inventory.near_expiry_days')))) {
            return 'near_expiry';
        }

        return 'ok';
    }
}
