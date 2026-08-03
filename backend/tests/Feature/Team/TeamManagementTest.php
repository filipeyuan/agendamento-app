<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\UserRole;
use App\Mail\TeamInviteMail;
use App\Models\Business;
use App\Models\BusinessInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_invite_a_new_team_member(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/team/invite', [
            'email' => 'novo@example.com',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.email', 'novo@example.com');
        $this->assertDatabaseHas('business_invites', [
            'business_id' => $admin->business_id,
            'email' => 'novo@example.com',
        ]);
        Mail::assertSent(TeamInviteMail::class);
    }

    #[Test]
    public function inviting_an_email_that_is_already_a_user_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/team/invite', [
            'email' => $existing->email,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    #[Test]
    public function inviting_the_same_email_again_refreshes_the_pending_invite(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/admin/team/invite', ['email' => 'novo@example.com']);
        $firstToken = BusinessInvite::where('email', 'novo@example.com')->first()->token;

        $this->actingAs($admin)->postJson('/api/admin/team/invite', ['email' => 'novo@example.com']);
        $secondToken = BusinessInvite::where('email', 'novo@example.com')->first()->token;

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertSame(1, BusinessInvite::where('email', 'novo@example.com')->count());
    }

    #[Test]
    public function a_client_cannot_invite_team_members(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/team/invite', [
            'email' => 'novo@example.com',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_invite_team_members(): void
    {
        $this->postJson('/api/admin/team/invite', ['email' => 'novo@example.com'])->assertUnauthorized();
    }

    #[Test]
    public function admin_can_list_members_and_pending_invites(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
        BusinessInvite::factory()->create([
            'business_id' => $business->id,
            'invited_by' => $admin->id,
            'email' => 'pendente@example.com',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/team');

        $response->assertOk();
        $response->assertJsonCount(2, 'members');
        $response->assertJsonCount(1, 'invites');
        $response->assertJsonPath('invites.0.email', 'pendente@example.com');
        $this->assertContains($otherAdmin->id, array_column($response->json('members'), 'id'));
    }

    #[Test]
    public function expired_invites_are_not_listed(): void
    {
        $admin = User::factory()->admin()->create();
        BusinessInvite::factory()->create([
            'business_id' => $admin->business_id,
            'invited_by' => $admin->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/team');

        $response->assertOk();
        $response->assertJsonCount(0, 'invites');
    }

    #[Test]
    public function admin_can_cancel_a_pending_invite(): void
    {
        $admin = User::factory()->admin()->create();
        $invite = BusinessInvite::factory()->create([
            'business_id' => $admin->business_id,
            'invited_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/team/invites/{$invite->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('business_invites', ['id' => $invite->id]);
    }

    #[Test]
    public function admin_cannot_cancel_an_invite_from_another_business(): void
    {
        $admin = User::factory()->admin()->create();
        $otherInvite = BusinessInvite::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/team/invites/{$otherInvite->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('business_invites', ['id' => $otherInvite->id]);
    }

    #[Test]
    public function admin_can_remove_another_team_member(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
        $member = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
        $member->createToken('api');

        $response = $this->actingAs($admin)->deleteJson("/api/admin/team/members/{$member->id}");

        $response->assertOk();
        $member->refresh();
        $this->assertNull($member->business_id);
        $this->assertSame(UserRole::Client, $member->role);
        $this->assertCount(0, $member->tokens);
    }

    #[Test]
    public function admin_cannot_remove_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/team/members/{$admin->id}");

        $response->assertUnprocessable();
        $this->assertSame($admin->business_id, $admin->fresh()->business_id);
    }

    #[Test]
    public function admin_cannot_remove_a_member_from_another_business(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/team/members/{$otherAdmin->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function a_client_cannot_manage_team(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)->getJson('/api/admin/team')->assertForbidden();
    }
}
