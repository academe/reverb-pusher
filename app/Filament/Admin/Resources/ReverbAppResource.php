<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReverbAppResource\Pages;
use App\Models\ReverbApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ReverbAppResource extends Resource
{
    protected static ?string $model = ReverbApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    
    protected static ?string $navigationLabel = 'WebSocket Apps';
    
    protected static ?string $modelLabel = 'WebSocket App';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('App Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if (empty($get('app_id'))) {
                                    $set('app_id', 'app-' . Str::slug($state) . '-' . Str::random(4));
                                }
                            }),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Credentials')
                    ->schema([
                        Forms\Components\TextInput::make('app_id')
                            ->label('App ID')
                            ->helperText('Private internal identifier')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('app_key')
                            ->label('App Key')
                            ->helperText('Public key for client-side use')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->helperText('Secret key for server-side use')
                            ->required()
                            ->maxLength(255)
                            ->password()
                            ->revealable()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generateSecret')
                                    ->icon('heroicon-m-arrow-path')
                                    ->tooltip('Generate New Secret')
                                    ->action(function (Forms\Set $set) {
                                        $set('app_secret', (string) Str::uuid());
                                    })
                            ),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('max_connections')
                            ->label('Max Connections')
                            ->numeric()
                            ->default(1000)
                            ->minValue(1),
                            
                        Forms\Components\TagsInput::make('allowed_origins')
                            ->label('Allowed Origins')
                            ->placeholder('https://yourdomain.com')
                            ->helperText('Leave empty to allow all origins'),
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
                    
                Tables\Columns\TextColumn::make('app_id')
                    ->label('App ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('App ID copied to clipboard'),
                    
                Tables\Columns\TextColumn::make('app_key')
                    ->label('App Key')
                    ->copyable()
                    ->copyMessage('App Key copied to clipboard'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('max_connections')
                    ->label('Max Connections')
                    ->numeric(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListReverbApps::route('/'),
            'create' => Pages\CreateReverbApp::route('/create'),
            'view' => Pages\ViewReverbApp::route('/{record}'),
            'edit' => Pages\EditReverbApp::route('/{record}/edit'),
        ];
    }
}
