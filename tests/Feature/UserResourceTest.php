<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Admin\Resources\UserResource;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user creation with all required fields.
     */
    public function test_user_can_be_created_with_required_fields(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $this->actingAs($admin);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'active' => true,
        ];

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => true,
        ]);
    }

    /**
     * Test user update functionality.
     */
    public function test_user_can_be_updated(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($admin);

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
     * Test user deletion.
     */
    public function test_user_can_be_deleted(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test email uniqueness validation.
     */
    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin);

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }
}
