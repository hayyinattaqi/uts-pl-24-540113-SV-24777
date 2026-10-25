<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_creates_a_batch_and_increases_stock(): void
    {
        $user = $this->authenticate();
        $medicine = Medicine::factory()->create();

        $this->postJson('/api/stock-in', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'B-001',
            'expiry_date' => today()->addYear()->toDateString(),
            'quantity' => 50,
            'notes' => 'Kiriman distributor',
        ])->assertCreated()
            ->assertJsonPath('data.batch.batch_number', 'B-001')
            ->assertJsonPath('data.batch.current_quantity', 50)
            ->assertJsonPath('data.movement.type', 'in')
            ->assertJsonPath('data.movement.quantity', 50);

        $this->assertDatabaseHas('batches', ['medicine_id' => $medicine->id, 'batch_number' => 'B-001', 'initial_quantity' => 50]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'in', 'quantity' => 50, 'user_id' => $user->id]);

        $this->getJson("/api/medicines/{$medicine->id}")
            ->assertOk()
            ->assertJsonPath('data.total_stock', 50)
            ->assertJsonPath('data.batches.0.status', 'ok');
    }

    public function test_stock_out_decreases_batch_stock(): void
    {
        $this->authenticate();
        $batch = Batch::factory()->quantity(30)->create();

        $this->postJson('/api/stock-out', [
            'batch_id' => $batch->id,
            'quantity' => 12,
        ])->assertCreated()
            ->assertJsonPath('data.type', 'out')
            ->assertJsonPath('data.quantity', 12)
            ->assertJsonPath('data.batch.current_quantity', 18);

        $this->assertSame(18, $batch->fresh()->current_quantity);
    }

    public function test_stock_out_rejects_quantity_above_batch_stock(): void
    {
        $this->authenticate();
        $batch = Batch::factory()->quantity(5)->create();

        $this->postJson('/api/stock-out', ['batch_id' => $batch->id, 'quantity' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Jumlah melebihi stok batch (sisa 5)']);

        $this->assertSame(5, $batch->fresh()->current_quantity);
    }

    public function test_stock_out_rejects_an_expired_batch(): void
    {
        $this->authenticate();
        $batch = Batch::factory()->expiresIn(-1)->create();

        $this->postJson('/api/stock-out', ['batch_id' => $batch->id, 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('batch_id');
    }

    public function test_stock_out_returns_404_for_a_missing_batch(): void
    {
        $this->authenticate();

        $this->postJson('/api/stock-out', ['batch_id' => 999, 'quantity' => 1])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_stock_in_rejects_a_duplicate_batch_number_for_the_same_medicine(): void
    {
        $this->authenticate();
        $batch = Batch::factory()->create(['batch_number' => 'B-001']);

        $this->postJson('/api/stock-in', [
            'medicine_id' => $batch->medicine_id,
            'batch_number' => 'B-001',
            'expiry_date' => today()->addYear()->toDateString(),
            'quantity' => 10,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('batch_number');
    }

    public function test_stock_in_rejects_an_expiry_date_that_is_not_after_today(): void
    {
        $this->authenticate();
        $medicine = Medicine::factory()->create();

        foreach ([today()->subDay(), today()] as $date) {
            $this->postJson('/api/stock-in', [
                'medicine_id' => $medicine->id,
                'batch_number' => 'B-'.$date->format('md'),
                'expiry_date' => $date->toDateString(),
                'quantity' => 10,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('expiry_date');
        }
    }

    public function test_medicine_with_batches_cannot_be_deleted(): void
    {
        $this->authenticate();
        $batch = Batch::factory()->create();

        $this->deleteJson("/api/medicines/{$batch->medicine_id}")
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Obat tidak dapat dihapus karena masih memiliki batch.']);

        $this->assertDatabaseHas('medicines', ['id' => $batch->medicine_id]);
    }

    public function test_missing_medicine_returns_404_json(): void
    {
        $this->authenticate();

        $this->getJson('/api/medicines/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_stock_movements_are_listed_latest_first_and_filtered_by_type(): void
    {
        $this->authenticate();
        $medicine = Medicine::factory()->create();

        $this->postJson('/api/stock-in', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'B-001',
            'expiry_date' => today()->addYear()->toDateString(),
            'quantity' => 20,
        ])->assertCreated();
        $batchId = Batch::first()->id;
        $this->postJson('/api/stock-out', ['batch_id' => $batchId, 'quantity' => 5])->assertCreated();

        $this->getJson('/api/stock-movements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'out')
            ->assertJsonPath('data.0.batch.medicine.id', $medicine->id);

        $this->getJson('/api/stock-movements?type=in')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'in');

        $this->getJson('/api/stock-movements?type=lain')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_dashboard_lists_near_expiry_expired_and_low_stock(): void
    {
        $this->authenticate();
        $nearExpiry = Batch::factory()->expiresIn(30)->quantity(10)->create();
        $expired = Batch::factory()->expiresIn(-5)->quantity(10)->create();
        Batch::factory()->expiresIn(200)->quantity(10)->create();
        Batch::factory()->expiresIn(10)->quantity(0)->create();

        $lowStock = Medicine::factory()->create(['min_stock' => 20]);
        Batch::factory()->for($lowStock)->expiresIn(365)->quantity(15)->create();
        Batch::factory()->for($lowStock)->expiresIn(-1)->quantity(100)->create();

        $enoughStock = Medicine::factory()->create(['min_stock' => 5]);
        Batch::factory()->for($enoughStock)->expiresIn(365)->quantity(50)->create();

        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertSame([$nearExpiry->id], array_column($response->json('data.near_expiry'), 'id'));
        $this->assertSame('near_expiry', $response->json('data.near_expiry.0.status'));
        $this->assertEqualsCanonicalizing(
            [$expired->id, $lowStock->batches()->whereDate('expiry_date', '<', today())->value('id')],
            array_column($response->json('data.expired'), 'id'),
        );
        $this->assertSame([$lowStock->id], array_column($response->json('data.low_stock'), 'id'));
        $this->assertSame(15, $response->json('data.low_stock.0.total_stock'));
        $this->assertTrue($response->json('data.low_stock.0.is_low_stock'));
    }

    public function test_new_stock_endpoints_require_authentication(): void
    {
        $this->postJson('/api/stock-in')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->postJson('/api/stock-out')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->getJson('/api/stock-movements')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->getJson('/api/dashboard')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    private function authenticate(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }
}
