<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'Atanmış Şirketler';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Şirket Adı'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Şirket Ekle')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                DetachAction::make()->label('Kaldır'),
            ])
            ->bulkActions([
                DetachBulkAction::make()->label('Seçilenleri Kaldır'),
            ]);
    }
}
