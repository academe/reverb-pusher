<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->confirmed()
                            ->requiredWith('password_confirmation'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->requiredWith('password'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->disabled(fn ($record) => $record?->id === auth()->id()),

                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->default(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('email_verified_at', $state ? now() : null);
                            })
                            ->dehydrated(false)
                            ->reactive(),

                        Forms\Components\Hidden::make('email_verified_at')
                            ->dehydrateStateUsing(fn ($state, Forms\Get $get) =>
                                $get('email_verified') ? now() : null
                            ),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied to clipboard'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        // Self-protection
                        if ($record->id === auth()->id()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete your own account')
                                ->send();
                            $action->cancel();
                            return;
                        }

                        // Last user protection
                        if ($record->isLastActiveUser()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete the last active user')
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $protected = [];
                            $deleted = [];

                            foreach ($records as $record) {
                                // Check if record is protected
                                if ($record->id === auth()->id()) {
                                    $protected[] = $record->name . ' (yourself)';
                                    continue;
                                }

                                if ($record->isLastActiveUser()) {
                                    $protected[] = $record->name . ' (last active user)';
                                    continue;
                                }

                                // Delete the record
                                $record->delete();
                                $deleted[] = $record->name;
                            }

                            // Show notifications
                            if (count($deleted) > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Users deleted')
                                    ->body(count($deleted) . ' user(s) deleted successfully')
                                    ->send();
                            }

                            if (count($protected) > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Some users were skipped')
                                    ->body('Protected users: ' . implode(', ', $protected))
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
