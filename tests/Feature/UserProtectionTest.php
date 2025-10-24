<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prevents_self_deletion_via_policy(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user);

        // Policy should prevent self-deletion
        $this->assertFalse($user->can('delete', $user));
    }

    public function test_it_prevents_deletion_of_last_active_user_via_policy(): void
    {
        $lastActiveUser = User::factory()->create(['active' => true]);
        $inactiveUser = User::factory()->create(['active' => false]);

        $this->actingAs($inactiveUser);

        // Policy should prevent deletion of last active user
        $this->assertFalse($inactiveUser->can('delete', $lastActiveUser));
    }

    public function test_it_allows_deletion_when_multiple_active_users_exist_via_policy(): void
    {
        $user1 = User::factory()->create(['active' => true]);
        $user2 = User::factory()->create(['active' => true]);
        $userToDelete = User::factory()->create(['active' => true]);

        $this->actingAs($user1);

        // Policy should allow deletion when multiple active users exist
        $this->assertTrue($user1->can('delete', $userToDelete));
    }

    public function test_is_last_active_user_helper_works_correctly(): void
    {
        $lastUser = User::factory()->create(['active' => true]);
        $inactiveUser = User::factory()->create(['active' => false]);

        $this->assertTrue($lastUser->isLastActiveUser());
        $this->assertFalse($inactiveUser->isLastActiveUser());

        // Create another active user
        $anotherActiveUser = User::factory()->create(['active' => true]);

        $this->assertFalse($lastUser->isLastActiveUser());
        $this->assertFalse($anotherActiveUser->isLastActiveUser());
    }

    public function test_edit_page_prevents_self_deactivation(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'active' => false,
            ])
            ->call('save')
            ->assertNotified();

        // User should still be active (save was halted)
        $user->refresh();
        $this->assertTrue($user->active);
    }

    public function test_edit_page_prevents_last_active_user_deactivation(): void
    {
        $lastActiveUser = User::factory()->create(['active' => true]);
        $inactiveUser = User::factory()->create(['active' => false]);

        $this->actingAs($inactiveUser);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $lastActiveUser->id])
            ->fillForm([
                'name' => $lastActiveUser->name,
                'email' => $lastActiveUser->email,
                'active' => false,
            ])
            ->call('save')
            ->assertNotified();

        // User should still be active (save was halted)
        $lastActiveUser->refresh();
        $this->assertTrue($lastActiveUser->active);
    }

    public function test_edit_page_allows_deactivation_when_multiple_active_users_exist(): void
    {
        $user1 = User::factory()->create(['active' => true]);
        $user2 = User::factory()->create(['active' => true]);
        $userToDeactivate = User::factory()->create(['active' => true]);

        $this->actingAs($user1);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $userToDeactivate->id])
            ->fillForm([
                'name' => $userToDeactivate->name,
                'email' => $userToDeactivate->email,
                'active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $userToDeactivate->refresh();
        $this->assertFalse($userToDeactivate->active);
    }

    public function test_active_toggle_is_disabled_for_own_record(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->assertFormFieldIsDisabled('active');
    }
}
