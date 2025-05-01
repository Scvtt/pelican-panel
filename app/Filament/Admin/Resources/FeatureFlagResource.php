<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FeatureFlagResource\Pages;
use App\Filament\Admin\Resources\FeatureFlagResource\RelationManagers;
use App\Models\FeatureFlag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $navigationIcon = 'tabler-flag';
    protected static ?string $navigationGroup = 'Server';

    public static function getNavigationLabel(): string
    {
        return 'Feature Flags';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('enabled', true)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getModelLabel(): string
    {
        return 'Feature Flag';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Feature Flags';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('flag')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('A unique identifier for this flag (e.g., AR_WORKSHOP)'),
                Forms\Components\Select::make('eggs')
                    ->label('Eggs')
                    ->multiple()
                    ->relationship('eggs', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                Forms\Components\Toggle::make('enabled')
                    ->label('Enabled')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('flag')->sortable()->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('eggs.name')
                    ->label('Eggs')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\ToggleColumn::make('enabled')->label('Enabled'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Enable selected')
                        ->icon('tabler-toggle-right')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['enabled' => true]);
                        }),
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Disable selected')
                        ->icon('tabler-toggle-left')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['enabled' => false]);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('tabler-flag')
            ->emptyStateHeading('No Feature Flags')
            ->emptyStateDescription('')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Feature Flag')
            ]);
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
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
