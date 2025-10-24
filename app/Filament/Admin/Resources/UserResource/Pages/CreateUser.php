<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Hook called before filling the form data.
     * Sets a temporary random password if none provided.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If no password provided, generate a temporary random one
        // The user will receive an invitation email to set their own password
        if (empty($data['password'])) {
            $data['password'] = bcrypt(Str::random(32));
        }

        return $data;
    }

    /**
     * Hook called after a user is created.
     * Sends invitation email if no password was provided.
     */
    protected function afterCreate(): void
    {
        // Check if password was provided during creation
        // If no password provided, send invitation email with password reset link
        if (empty($this->data['password'])) {
            // Generate password reset token
            $token = Password::createToken($this->record);

            // Send password reset notification (invitation email)
            $this->record->sendPasswordResetNotification($token);
        }
    }
}
