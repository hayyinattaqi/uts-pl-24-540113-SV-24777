<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_medicine(): void
    {
        $this->authenticate();

        $this->postJson('/api/medicines', [
            'code' => 'MED-001',
            'name' => 'Paracetamol',
            'unit' => 'tablet',
            'min_stock' => 10,
        ])->assertCreated()
            ->assertJsonPath('data.code', 'MED-001')
            ->assertJsonPath('data.name', 'Paracetamol')
            ->assertJsonPath('data.min_stock', 10)
            ->assertJsonStructure(['data', 'message']);

        $this->assertDatabaseHas('medicines', ['code' => 'MED-001']);
    }

    public function test_it_rejects_a_duplicate_medicine_code(): void
    {
        $this->authenticate();
        Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol',
            'unit' => 'tablet',
        ]);

        $this->postJson('/api/medicines', [
            'code' => 'MED-001',
            'name' => 'Obat lain',
            'unit' => 'botol',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_it_requires_medicine_fields(): void
    {
        $this->authenticate();

        $this->postJson('/api/medicines', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'unit']);
    }

    public function test_it_updates_a_medicine_without_rejecting_its_own_code(): void
    {
        $this->authenticate();
        $medicine = Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol',
            'unit' => 'tablet',
        ]);

        $this->putJson("/api/medicines/{$medicine->id}", [
            'code' => 'MED-001',
            'name' => 'Paracetamol 500 mg',
            'unit' => 'tablet',
            'min_stock' => 20,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Paracetamol 500 mg')
            ->assertJsonPath('data.min_stock', 20)
            ->assertJsonStructure(['data', 'message']);

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'name' => 'Paracetamol 500 mg',
        ]);
    }

    public function test_it_deletes_a_medicine_with_a_json_success_response(): void
    {
        $this->authenticate();
        $medicine = Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol',
            'unit' => 'tablet',
        ]);

        $this->deleteJson("/api/medicines/{$medicine->id}")
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('medicines', ['id' => $medicine->id]);
    }

    public function test_medicine_list_requires_authentication(): void
    {
        $this->get('/api/medicines')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_medicine_detail_requires_authentication(): void
    {
        $medicine = Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol',
            'unit' => 'tablet',
        ]);

        $this->get("/api/medicines/{$medicine->id}")
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    private function authenticate(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }
}
