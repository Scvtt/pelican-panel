<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModManagerResource\Pages;
use App\Filament\Admin\Resources\ModManagerResource\RelationManagers;
use App\Models\ModManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModManagerResource extends Resource
{
    protected static ?string $model = ModManager::class;

    protected static ?string $navigationIcon = 'tabler-tool';
    protected static ?string $navigationGroup = 'Server';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Mod Managers';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('eggs.name')
                    ->label('Eggs')
                    ->listWithLineBreaks()
                    ->bulleted(),
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListModManagers::route('/'),
            'create' => Pages\CreateModManager::route('/create'),
            'edit' => Pages\EditModManager::route('/{record}/edit'),
        ];
    }
}
