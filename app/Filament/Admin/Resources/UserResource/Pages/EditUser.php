<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendPasswordReset')
                ->label('Send Password Reset Email')
                ->icon('heroicon-o-envelope')
                ->action(function () {
                    // Generate password reset token
                    $token = Password::createToken($this->record);

                    // Send password reset notification
                    $this->record->sendPasswordResetNotification($token);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Password reset email sent')
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set the email_verified toggle state based on email_verified_at
        $data['email_verified'] = $data['email_verified_at'] !== null;

        return $data;
    }

    protected function beforeSave(): void
    {
        // Check for self-deactivation
        if ($this->record->id === auth()->id() && $this->data['active'] === false) {
            Notification::make()
                ->danger()
                ->title('Cannot deactivate your own account')
                ->send();
            $this->halt();
        }

        // Check for last active user deactivation
        if ($this->record->active === true && $this->data['active'] === false) {
            $activeUserCount = User::where('active', true)->count();
            if ($activeUserCount <= 1) {
                Notification::make()
                    ->danger()
                    ->title('Cannot deactivate the last active user')
                    ->send();
                $this->halt();
            }
        }
    }
}
