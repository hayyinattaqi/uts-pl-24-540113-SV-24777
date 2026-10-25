<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine_id' => $this->medicine_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date->toDateString(),
            'initial_quantity' => $this->initial_quantity,
            'current_quantity' => $this->current_quantity,
            'status' => $this->status(),
            'medicine' => $this->whenLoaded('medicine', fn () => [
                'id' => $this->medicine->id,
                'code' => $this->medicine->code,
                'name' => $this->medicine->name,
                'unit' => $this->medicine->unit,
            ]),
        ];
    }
}
