<?php

declare(strict_types=1);

namespace Tests\Feature\Business;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessBannerTest extends TestCase
{
    use RefreshDatabase;

    private function proAdmin(): User
    {
        $business = Business::factory()->pro()->create();

        return User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
    }

    #[Test]
    public function pro_admin_can_upload_a_business_banner(): void
    {
        Http::fake([
            'api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/banners/banner.png',
                'public_id' => 'zelo/banners/banner',
            ]),
        ]);

        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.png', 10, 'image/png'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.banner_url', 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/banners/banner.png');
        $this->assertSame('zelo/banners/banner', $admin->business->fresh()->banner_public_id);
    }

    #[Test]
    public function uploading_a_new_banner_removes_the_previous_one_from_cloudinary(): void
    {
        Http::fake([
            'api.cloudinary.com/*/image/upload' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/banners/new.png',
                'public_id' => 'zelo/banners/new',
            ]),
            'api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $admin = $this->proAdmin();
        $admin->business->forceFill([
            'banner_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/banners/old.png',
            'banner_public_id' => 'zelo/banners/old',
        ])->save();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.png', 10, 'image/png'),
        ]);

        $response->assertOk();
        Http::assertSent(fn ($request) => str_contains($request->url(), '/image/destroy')
            && $request['public_id'] === 'zelo/banners/old');
    }

    #[Test]
    public function free_plan_admin_cannot_upload_a_business_banner(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.png', 10, 'image/png'),
        ]);

        $response->assertForbidden();
        $this->assertNull($admin->business->fresh()->banner_url);
    }

    #[Test]
    public function client_cannot_upload_a_business_banner(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.png', 10, 'image/png'),
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_upload_a_business_banner(): void
    {
        $response = $this->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.png', 10, 'image/png'),
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function uploading_a_banner_requires_a_valid_image(): void
    {
        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/banner', [
            'banner' => UploadedFile::fake()->create('banner.pdf', 100),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('banner');
    }

    #[Test]
    public function admin_can_remove_the_business_banner(): void
    {
        Http::fake([
            'api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $admin = $this->proAdmin();
        $admin->business->forceFill([
            'banner_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/banners/banner.png',
            'banner_public_id' => 'zelo/banners/banner',
        ])->save();

        $response = $this->actingAs($admin)->deleteJson('/api/admin/business/banner');

        $response->assertOk();
        $response->assertJsonPath('data.banner_url', null);
        $this->assertNull($admin->business->fresh()->banner_public_id);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/image/destroy'));
    }
}
