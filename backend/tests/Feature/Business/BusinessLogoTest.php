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

class BusinessLogoTest extends TestCase
{
    use RefreshDatabase;

    private function proAdmin(): User
    {
        $business = Business::factory()->pro()->create();

        return User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
    }

    #[Test]
    public function pro_admin_can_upload_a_business_logo(): void
    {
        Http::fake([
            'api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/logo.png',
                'public_id' => 'zelo/logo',
            ]),
        ]);

        $admin = $this->proAdmin();
        $file = UploadedFile::fake()->create('logo.png', 10, 'image/png');

        $response = $this->actingAs($admin)->postJson('/api/admin/business/logo', ['logo' => $file]);

        $response->assertOk();
        $response->assertJsonPath('data.logo_url', 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/logo.png');
        $this->assertSame('zelo/logo', $admin->business->fresh()->logo_public_id);
    }

    #[Test]
    public function uploading_a_new_logo_removes_the_previous_one_from_cloudinary(): void
    {
        Http::fake([
            'api.cloudinary.com/*/image/upload' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/new-logo.png',
                'public_id' => 'zelo/new-logo',
            ]),
            'api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $admin = $this->proAdmin();
        $admin->business->forceFill([
            'logo_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/old-logo.png',
            'logo_public_id' => 'zelo/old-logo',
        ])->save();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertOk();
        Http::assertSent(fn ($request) => str_contains($request->url(), '/image/destroy')
            && $request['public_id'] === 'zelo/old-logo');
    }

    #[Test]
    public function free_plan_admin_cannot_upload_a_business_logo(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertForbidden();
        $this->assertNull($admin->business->fresh()->logo_url);
    }

    #[Test]
    public function client_cannot_upload_a_business_logo(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_upload_a_business_logo(): void
    {
        $response = $this->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function uploading_a_logo_requires_a_valid_image(): void
    {
        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.pdf', 100),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('logo');
    }

    #[Test]
    public function upload_failure_from_cloudinary_returns_a_friendly_error(): void
    {
        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid image file']], 400),
        ]);

        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/business/logo', [
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertStatus(502);
    }

    #[Test]
    public function admin_can_remove_the_business_logo(): void
    {
        Http::fake([
            'api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $admin = $this->proAdmin();
        $admin->business->forceFill([
            'logo_url' => 'https://res.cloudinary.com/zdjtmnwt/image/upload/v1/zelo/logo.png',
            'logo_public_id' => 'zelo/logo',
        ])->save();

        $response = $this->actingAs($admin)->deleteJson('/api/admin/business/logo');

        $response->assertOk();
        $response->assertJsonPath('data.logo_url', null);
        $this->assertNull($admin->business->fresh()->logo_public_id);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/image/destroy'));
    }
}
