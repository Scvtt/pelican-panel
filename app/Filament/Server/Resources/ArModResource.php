<?php

namespace App\Filament\Server\Resources;

use App\Filament\Server\Resources\ArModResource\Pages;
use App\Models\ArMod;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class ArModResource extends Resource
{
    protected static ?string $model = ArMod::class;

    protected static ?string $navigationIcon = 'tabler-cube';
    
    protected static ?string $navigationLabel = 'Mods';
    
    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('server_id', Filament::getTenant()->id)
            ->where('is_installed', true)
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('uuid')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn ($record) => $record),
                        TextInput::make('author')
                            ->maxLength(255),
                        TextInput::make('version')
                            ->maxLength(255),
                        Textarea::make('preview_url')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Toggle::make('is_installed')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('author')
                    ->searchable(),
                TextColumn::make('version')
                    ->searchable(),
                ToggleColumn::make('is_installed'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListArMods::route('/'),
            'create' => Pages\CreateArMod::route('/create'),
            'edit' => Pages\EditArMod::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('server_id', Filament::getTenant()->id);
    }
} 