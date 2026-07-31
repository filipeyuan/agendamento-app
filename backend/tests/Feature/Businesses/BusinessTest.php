<?php

declare(strict_types=1);

namespace Tests\Feature\Businesses;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_can_list_businesses(): void
    {
        Business::factory()->create(['name' => 'Studio Zelo']);
        Business::factory()->create(['name' => 'Clínica Bem Estar']);

        $response = $this->getJson('/api/businesses');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function anyone_can_view_a_business_by_slug(): void
    {
        $business = Business::factory()->create(['name' => 'Studio Zelo', 'slug' => 'studio-zelo']);

        $response = $this->getJson('/api/businesses/studio-zelo');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Studio Zelo');
        $response->assertJsonPath('data.slug', 'studio-zelo');
    }

    #[Test]
    public function viewing_an_unknown_business_slug_returns_404(): void
    {
        $response = $this->getJson('/api/businesses/nao-existe');

        $response->assertNotFound();
    }
}
