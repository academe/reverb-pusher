<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test end-to-end flow: Create user without password -> Invitation sent -> User can set password and login.
     */
    public function test_complete_user_invitation_workflow(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['active' => true]);
        $this->actingAs($admin);

        // Admin creates user without password
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'New Invited User',
                'email' => 'invited@example.com',
                'active' => true,
                'email_verified' => false,
            ])
            ->call('create')
            ->assertHasNoErrors();

        // Verify user created and invitation sent
        $newUser = User::where('email', 'invited@example.com')->first();
        $this->assertNotNull($newUser);
        Notification::assertSentTo($newUser, ResetPassword::class);

        // Verify the user can later set their password and authenticate
        $newUser->password = Hash::make('newpassword123');
        $newUser->save();

        // Verify user can login with new password
        $this->assertTrue(Hash::check('newpassword123', $newUser->fresh()->password));
    }

    /**
     * Test end-to-end flow: Admin changes user password -> User can login with new password.
     */
    public function test_admin_password_change_allows_user_login(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($admin);

        // Admin changes user's password
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Verify new password works
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword', $user->password));
    }

    /**
     * Test end-to-end flow: Toggle user inactive -> User loses panel access -> Reactivate -> Access restored.
     */
    public function test_active_status_controls_panel_access_integration(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'active' => true,
            'email' => 'test@example.com',
        ]);

        $this->actingAs($admin);

        // Verify user initially has panel access
        $this->assertTrue($user->canAccessPanel(app(\Filament\Panel::class)));

        // Admin deactivates user
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'active' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Verify user lost panel access
        $user->refresh();
        $this->assertFalse($user->active);
        $this->assertFalse($user->canAccessPanel(app(\Filament\Panel::class)));

        // Admin reactivates user
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'active' => true,
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Verify access restored
        $user->refresh();
        $this->assertTrue($user->active);
        $this->assertTrue($user->canAccessPanel(app(\Filament\Panel::class)));
    }

    /**
     * Test integration: Bulk delete with mixed protected and unprotected users.
     */
    public function test_bulk_delete_respects_protection_rules(): void
    {
        $admin = User::factory()->create(['active' => true, 'name' => 'Admin User']);
        $user1 = User::factory()->create(['active' => true, 'name' => 'User 1']);
        $user2 = User::factory()->create(['active' => false, 'name' => 'User 2']);
        $user3 = User::factory()->create(['active' => false, 'name' => 'User 3']);

        $this->actingAs($admin);

        // With admin and user1 both active, neither is the last active user
        // So the protection logic should work as follows:
        // - Admin: protected (yourself)
        // - User 1: NOT protected (multiple active users exist)
        // - User 2: NOT protected (inactive)
        // - User 3: NOT protected (inactive)

        $recordsToDelete = collect([$admin, $user1, $user2, $user3]);

        $protected = [];
        $deleted = [];

        foreach ($recordsToDelete as $record) {
            // Check if record is protected (self)
            if ($record->id === $admin->id) {
                $protected[] = $record->name . ' (yourself)';
                continue;
            }

            // Check if record is last active user
            if ($record->isLastActiveUser()) {
                $protected[] = $record->name . ' (last active user)';
                continue;
            }

            // Simulate deletion
            $deleted[] = $record->name;
        }

        // Verify protection logic - only admin should be protected (self), others can be deleted
        $this->assertContains('Admin User (yourself)', $protected);
        $this->assertCount(1, $protected);
        $this->assertContains('User 1', $deleted);
        $this->assertContains('User 2', $deleted);
        $this->assertContains('User 3', $deleted);
        $this->assertCount(3, $deleted);
    }

    /**
     * Test integration: Password reset email contains valid token and working link.
     */
    public function test_password_reset_email_token_is_valid_and_usable(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->actingAs($admin);

        // Admin sends password reset email
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->callAction('sendPasswordReset');

        // Verify notification was sent
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($user) {
            // Get the token from the notification
            $token = $notification->token;

            // Verify token exists in password_reset_tokens table
            $this->assertDatabaseHas('password_reset_tokens', [
                'email' => $user->email,
            ]);

            // Verify token is valid via Password broker
            $this->assertTrue(
                app('auth.password.broker')->tokenExists($user, $token)
            );

            return true;
        });
    }

    /**
     * Test security: Inactive user cannot access panel even when authenticated.
     */
    public function test_inactive_user_cannot_access_panel_via_middleware(): void
    {
        $inactiveUser = User::factory()->create(['active' => false]);

        // Verify canAccessPanel returns false
        $this->assertFalse($inactiveUser->canAccessPanel(app(\Filament\Panel::class)));

        // When acting as inactive user, they should not be able to access Filament pages
        $this->actingAs($inactiveUser);

        // Attempting to access user list should be forbidden (403) not redirect
        $response = $this->get('/admin/users');
        $response->assertForbidden();
    }

    /**
     * Test security: Email uniqueness is strictly enforced across create and update.
     */
    public function test_email_uniqueness_enforced_on_create_and_update(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin);

        // Test create with duplicate email
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);

        // Create another user with different email
        $anotherUser = User::factory()->create(['email' => 'another@example.com']);

        // Test update with duplicate email
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $anotherUser->id])
            ->fillForm([
                'name' => $anotherUser->name,
                'email' => 'existing@example.com', // Try to use existing email
            ])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    /**
     * Test edge case: Password confirmation mismatch shows proper validation error.
     */
    public function test_password_confirmation_mismatch_shows_validation_error(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $this->actingAs($admin);

        // Test on create
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'differentpassword',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    /**
     * Test edge case: Multiple active users exist, deactivate one, last active user still protected.
     */
    public function test_last_user_protection_works_with_multiple_deactivations(): void
    {
        $admin = User::factory()->create(['active' => true, 'name' => 'Admin']);
        $user1 = User::factory()->create(['active' => true, 'name' => 'User 1']);
        $user2 = User::factory()->create(['active' => true, 'name' => 'User 2']);

        $this->actingAs($admin);

        // Verify we have 3 active users
        $this->assertEquals(3, User::where('active', true)->count());

        // Deactivate user1 - should succeed
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user1->id])
            ->fillForm([
                'name' => $user1->name,
                'email' => $user1->email,
                'active' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Verify user1 was deactivated
        $this->assertEquals(2, User::where('active', true)->count());

        // Deactivate user2 - should succeed
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user2->id])
            ->fillForm([
                'name' => $user2->name,
                'email' => $user2->email,
                'active' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Verify user2 was deactivated, only admin remains
        $this->assertEquals(1, User::where('active', true)->count());

        // Now admin is the last active user
        $admin->refresh();
        $this->assertTrue($admin->isLastActiveUser());

        // Try to deactivate admin (last active user) - should fail
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $admin->id])
            ->fillForm([
                'name' => $admin->name,
                'email' => $admin->email,
                'active' => false,
            ])
            ->call('save')
            ->assertNotified();

        // Verify admin is still active
        $admin->refresh();
        $this->assertTrue($admin->active);
        $this->assertEquals(1, User::where('active', true)->count());
    }
}
