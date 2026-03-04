<?php

namespace App\Filament\Resources\CreditCards;

use App\Enums\NavigationGroup;
use App\Filament\Resources\CreditCards\Pages\CreateCreditCard;
use App\Filament\Resources\CreditCards\Pages\EditCreditCard;
use App\Filament\Resources\CreditCards\Pages\ListCreditCards;
use App\Filament\Resources\CreditCards\Schemas\CreditCardForm;
use App\Filament\Resources\CreditCards\Tables\CreditCardsTable;
use App\Models\CreditCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CreditCardResource extends Resource
{
    protected static ?string $model = CreditCard::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Cartão de crédito';

    protected static ?string $pluralLabel = 'Cartões de crédito';

    protected static ?int $navigationSort = 2;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::WALLETS;

    public static function form(Schema $schema): Schema
    {
        return CreditCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditCardsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditCards::route('/'),
            'create' => CreateCreditCard::route('/create'),
            'edit' => EditCreditCard::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
