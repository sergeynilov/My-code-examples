<?php

namespace App\Filament\Pages;

use App\Settings\BannerBuilderSettings;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;

class ManageBannerBuilderSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $settings = BannerBuilderSettings::class;
    protected static ?string $navigationGroup = 'Settings';

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('text_font_file_path')
                ->label('Text font file path')
                ->hint('Path under /public subdirectory')
                ->required()
                ->minLength(2)
                ->maxLength(255),
            TextInput::make('text_x_point')
                ->label('Text x indent')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(4),
            TextInput::make('text_y_point')
                ->label('Text y indent')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(4),
            TextInput::make('text_size')
                ->label('Text size')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(2),

            TextInput::make('description_font_file_path')
                ->label('Description font file path')
                ->hint('Path under /public subdirectory')
                ->required()
                ->minLength(2)
                ->maxLength(255),
            TextInput::make('description_x_point')
                ->label('Description x indent')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(4),
            TextInput::make('description_y_point')
                ->label('Description y indent')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(4),
            TextInput::make('description_size')
                ->label('Description size')
                ->hint('Value in pixels')
                ->numeric()
                ->required()
                ->minLength(1)
                ->maxLength(2),
        ];
    }
}
