<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\BusinessPlan;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string}
     */
    private function signedPayload(array $payload): array
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $json = (string) json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", 'whsec_test_secret');

        return [$json, "t={$timestamp},v1={$signature}"];
    }

    #[Test]
    public function admin_can_start_a_checkout_session_to_upgrade_to_pro(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'cs_test_sub_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_sub_123',
            ]),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/billing/checkout');

        $response->assertOk();
        $response->assertJsonPath('checkout_url', 'https://checkout.stripe.com/pay/cs_test_sub_123');
    }

    #[Test]
    public function checkout_is_blocked_when_the_business_is_already_pro(): void
    {
        $business = Business::factory()->pro()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);

        $response = $this->actingAs($admin)->postJson('/api/admin/billing/checkout');

        $response->assertUnprocessable();
    }

    #[Test]
    public function a_client_cannot_start_a_checkout_session(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/billing/checkout');

        $response->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_start_a_checkout_session(): void
    {
        $this->postJson('/api/admin/billing/checkout')->assertUnauthorized();
    }

    #[Test]
    public function admin_can_open_the_billing_portal_once_subscribed(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['url' => 'https://billing.stripe.com/session/xyz']),
        ]);

        $business = Business::factory()->pro()->create();
        $business->forceFill(['stripe_customer_id' => 'cus_123'])->save();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);

        $response = $this->actingAs($admin)->postJson('/api/admin/billing/portal');

        $response->assertOk();
        $response->assertJsonPath('portal_url', 'https://billing.stripe.com/session/xyz');
    }

    #[Test]
    public function billing_portal_is_blocked_without_an_active_subscription(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/billing/portal');

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_dismiss_the_premium_prompt(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/billing/dismiss-premium-prompt');

        $response->assertOk();
        $this->assertNotNull($admin->business->fresh()->premium_prompt_seen_at);
    }

    #[Test]
    public function webhook_activates_the_pro_plan_when_a_subscription_checkout_completes(): void
    {
        $business = Business::factory()->create();

        [$payload, $signature] = $this->signedPayload([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_sub_456',
                'mode' => 'subscription',
                'customer' => 'cus_456',
                'subscription' => 'sub_456',
                'metadata' => ['business_id' => (string) $business->id],
            ]],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $business->refresh();
        $this->assertSame(BusinessPlan::Pro, $business->plan);
        $this->assertSame('cus_456', $business->stripe_customer_id);
        $this->assertSame('sub_456', $business->stripe_subscription_id);
    }

    #[Test]
    public function webhook_downgrades_to_free_when_the_subscription_is_deleted(): void
    {
        $business = Business::factory()->pro()->create();
        $business->forceFill(['stripe_subscription_id' => 'sub_789'])->save();

        [$payload, $signature] = $this->signedPayload([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_789', 'status' => 'canceled']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertSame(BusinessPlan::Free, $business->fresh()->plan);
    }

    #[Test]
    public function the_free_plan_blocks_creating_a_second_service(): void
    {
        $admin = User::factory()->admin()->create();
        Service::factory()->create(['business_id' => $admin->business_id]);

        $response = $this->actingAs($admin)->postJson('/api/admin/services', [
            'name' => 'Segundo serviço',
            'duration_minutes' => 30,
            'price' => 50,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan');
    }

    #[Test]
    public function the_pro_plan_allows_creating_more_than_one_service(): void
    {
        $business = Business::factory()->pro()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
        Service::factory()->create(['business_id' => $business->id]);

        $response = $this->actingAs($admin)->postJson('/api/admin/services', [
            'name' => 'Segundo serviço',
            'duration_minutes' => 30,
            'price' => 50,
        ]);

        $response->assertCreated();
    }

    #[Test]
    public function the_free_plan_allows_a_limited_number_of_daily_assistant_messages(): void
    {
        config(['services.gemini.api_key' => null]);

        $business = Business::factory()->create();
        $client = User::factory()->create();

        for ($i = 0; $i < Business::FREE_ASSISTANT_DAILY_LIMIT; $i++) {
            $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
                'business' => $business->slug,
                'messages' => [['role' => 'user', 'content' => 'Oi']],
            ]);

            $response->assertOk();
            $response->assertJsonPath('assistant_quota_remaining', Business::FREE_ASSISTANT_DAILY_LIMIT - $i - 1);
        }

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $business->slug,
            'messages' => [['role' => 'user', 'content' => 'Oi']],
        ]);

        $response->assertForbidden();
        $this->assertSame(
            Business::FREE_ASSISTANT_DAILY_LIMIT,
            $business->fresh()->assistant_daily_count
        );
    }

    #[Test]
    public function the_pro_plan_has_no_assistant_message_limit(): void
    {
        config(['services.gemini.api_key' => null]);

        $business = Business::factory()->pro()->create();
        $client = User::factory()->create();

        for ($i = 0; $i < Business::FREE_ASSISTANT_DAILY_LIMIT + 2; $i++) {
            $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
                'business' => $business->slug,
                'messages' => [['role' => 'user', 'content' => 'Oi']],
            ]);

            $response->assertOk();
            $response->assertJsonPath('assistant_quota_remaining', null);
        }
    }
}
