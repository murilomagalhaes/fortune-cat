<?php

namespace App\Filament\Resources\TransactionPayments\Pages;

use App\Filament\Resources\TransactionPayments\TransactionPaymentResource;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactionPayments extends ListRecords
{
    protected static string $resource = TransactionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->steps(TransactionForm::steps())
                ->label("Nova transação")
                ->model(Transaction::class)
        ];
    }
}
