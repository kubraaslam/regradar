<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS FR14: the admin creates, deactivates and manages accounts and roles.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_only_admins_can_reach_user_management(): void
    {
        foreach ([User::ROLE_QA_ENGINEER, User::ROLE_DEVELOPER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.users.index'))
                ->assertForbidden();
        }

        $this->actingAs($this->admin())->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_creates_an_account_with_a_role(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'New Developer',
            'email' => 'new.dev@example.com',
            'role' => User::ROLE_DEVELOPER,
            'password' => 'a-strong-password-1',
            'password_confirmation' => 'a-strong-password-1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::ROLE_DEVELOPER, User::where('email', 'new.dev@example.com')->value('role'));
    }

    public function test_admin_changes_another_users_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);

        $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_DEVELOPER,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::ROLE_DEVELOPER, $user->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_QA_ENGINEER,
        ])->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.deactivate', $admin))->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->isActive());
    }

    public function test_the_last_active_admin_is_protected(): void
    {
        $only = $this->admin();
        $this->assertTrue($only->isLastActiveAdmin());

        $second = $this->admin();
        $this->assertFalse($only->fresh()->isLastActiveAdmin());

        // With two admins, one may deactivate the other...
        $this->actingAs($only)->post(route('admin.users.deactivate', $second))->assertSessionHasNoErrors();
        $this->assertFalse($second->fresh()->isActive());

        // ...which leaves the first as the last one standing.
        $this->assertTrue($only->fresh()->isLastActiveAdmin());
    }

    public function test_the_last_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertNotNull($admin->fresh());
    }

    public function test_a_deactivated_user_cannot_sign_in(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_QA_ENGINEER, 'deactivated_at' => now()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_signed_in_user_is_signed_out_when_deactivated(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_QA_ENGINEER]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reactivation_restores_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_QA_ENGINEER, 'deactivated_at' => now()]);

        $this->actingAs($this->admin())->post(route('admin.users.reactivate', $user));
        auth()->logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_a_user_cannot_promote_themselves_through_the_profile_form(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DEVELOPER]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertSame(User::ROLE_DEVELOPER, $user->fresh()->role);
    }
}
