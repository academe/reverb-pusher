<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Admin\Resources\UserResource;

class ProfileConsolidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that old profile routes no longer exist.
     */
    public function test_profile_routes_no_longer_exist(): void
    {
        $user = User::factory()->create(['active' => true]);

        // Test GET /profile returns 404
        $response = $this->actingAs($user)->get('/profile');
        $response->assertNotFound();

        // Test PATCH /profile returns 404
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $response->assertNotFound();

        // Test DELETE /profile returns 404
        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);
        $response->assertNotFound();
    }

    /**
     * Test that users can edit their own profile via Filament UserResource.
     */
    public function test_users_can_edit_own_profile_via_filament(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'active' => true,
        ]);

        $this->actingAs($user);

        // User should be able to edit their own record
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    /**
     * Test that Filament user menu functionality remains intact.
     */
    public function test_filament_user_menu_remains_functional(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user);

        // Verify the user can access the UserResource list page
        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertSuccessful();

        // Verify the user can access their own edit page
        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->assertSuccessful();
    }
}
