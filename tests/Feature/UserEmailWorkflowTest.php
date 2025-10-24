<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Admin\Resources\UserResource;

class UserEmailWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test invitation email is sent when creating user without password.
     */
    public function test_invitation_email_sent_when_creating_user_without_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['active' => true]);
        $this->actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'active' => true,
            'email_verified' => false,
        ];

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Test no email is sent when creating user with password.
     */
    public function test_no_email_sent_when_creating_user_with_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['active' => true]);
        $this->actingAs($admin);

        $userData = [
            'name' => 'New User With Password',
            'email' => 'userpass@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'active' => true,
            'email_verified' => false,
        ];

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'userpass@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertNothingSent();
    }

    /**
     * Test password reset email is sent from action button.
     */
    public function test_password_reset_email_sent_from_action_button(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['active' => true]);
        $user = User::factory()->create(['email' => 'resetuser@example.com']);

        $this->actingAs($admin);

        Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->callAction('sendPasswordReset');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Test password reset link contains valid token.
     */
    public function test_password_reset_link_is_valid(): void
    {
        $user = User::factory()->create(['email' => 'resetuser@example.com']);

        $token = app('auth.password.broker')->createToken($user);

        $this->assertNotEmpty($token);
        $this->assertTrue(
            app('auth.password.broker')->tokenExists($user, $token)
        );
    }
}
