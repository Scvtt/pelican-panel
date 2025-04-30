<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class ModManagers extends Page
{
    protected static ?string $navigationIcon = 'tabler-tool';
    protected static ?string $navigationGroup = 'Server';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.admin.pages.mod-managers';

    public static function getNavigationLabel(): string
    {
        return 'Mod Managers';
    }
} 