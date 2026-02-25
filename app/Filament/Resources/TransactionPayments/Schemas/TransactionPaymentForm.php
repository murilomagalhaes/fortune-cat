<?php

namespace App\Filament\Resources\TransactionPayments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->default('PENDING')
                    ->required(),
                DatePicker::make('billing_date'),
                TextInput::make('payment_number')
                    ->required()
                    ->numeric(),
                TextInput::make('billable_type'),
                TextInput::make('billable_id')
                    ->numeric(),
                Select::make('transaction_id')
                    ->relationship('transaction', 'name')
                    ->required(),
            ]);
    }
}
