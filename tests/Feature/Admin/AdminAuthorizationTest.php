<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_admin_can_access_protected_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-registrations.index'))
            ->assertOk();
    }

    public function test_pending_admin_cannot_access_protected_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-registrations.index'))
            ->assertForbidden();
    }

    public function test_declined_admin_cannot_access_protected_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'declined',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-registrations.index'))
            ->assertForbidden();
    }

    public function test_farmer_cannot_access_protected_admin_routes(): void
    {
        $farmer = User::factory()->create([
            'role' => 'farmer',
            'admin_registration_status' => 'approved',
        ]);

        $this->actingAs($farmer)
            ->get(route('admin.admin-registrations.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_visitor_is_redirected_to_login(): void
    {
        $this->get(route('admin.admin-registrations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_approved_admin_can_approve_pending_admin(): void
    {
        $approvedAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
        $pendingAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($approvedAdmin)
            ->patch(route('admin.admin-registrations.approve', $pendingAdmin))
            ->assertRedirect(route('admin.admin-registrations.index'));

        $this->assertDatabaseHas('users', [
            'id' => $pendingAdmin->id,
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
    }

    public function test_approved_admin_can_decline_pending_admin(): void
    {
        $approvedAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
        $pendingAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($approvedAdmin)
            ->patch(route('admin.admin-registrations.decline', $pendingAdmin))
            ->assertRedirect(route('admin.admin-registrations.index'));

        $this->assertDatabaseHas('users', [
            'id' => $pendingAdmin->id,
            'role' => 'admin',
            'admin_registration_status' => 'declined',
        ]);
    }

    public function test_pending_admin_cannot_approve_pending_admin(): void
    {
        $pendingActor = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);
        $pendingTarget = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($pendingActor)
            ->patch(route('admin.admin-registrations.approve', $pendingTarget))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $pendingTarget->id,
            'admin_registration_status' => 'pending',
        ]);
    }

    public function test_pending_admin_cannot_decline_pending_admin(): void
    {
        $pendingActor = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);
        $pendingTarget = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($pendingActor)
            ->patch(route('admin.admin-registrations.decline', $pendingTarget))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $pendingTarget->id,
            'admin_registration_status' => 'pending',
        ]);
    }

    public function test_farmer_cannot_approve_or_decline_admins(): void
    {
        $farmer = User::factory()->create([
            'role' => 'farmer',
            'admin_registration_status' => 'approved',
        ]);
        $pendingAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($farmer)
            ->patch(route('admin.admin-registrations.approve', $pendingAdmin))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->patch(route('admin.admin-registrations.decline', $pendingAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $pendingAdmin->id,
            'admin_registration_status' => 'pending',
        ]);
    }

    public function test_approved_admin_cannot_approve_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.admin-registrations.approve', $admin))
            ->assertForbidden();
    }

    public function test_approved_admin_cannot_decline_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.admin-registrations.decline', $admin))
            ->assertForbidden();
    }

    public function test_target_farmer_cannot_be_approved_or_declined_as_admin(): void
    {
        $approvedAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
        $farmer = User::factory()->create([
            'role' => 'farmer',
            'admin_registration_status' => 'declined',
        ]);

        $this->actingAs($approvedAdmin)
            ->patch(route('admin.admin-registrations.approve', $farmer))
            ->assertNotFound();

        $this->actingAs($approvedAdmin)
            ->patch(route('admin.admin-registrations.decline', $farmer))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $farmer->id,
            'role' => 'farmer',
            'admin_registration_status' => 'declined',
        ]);
    }
}
