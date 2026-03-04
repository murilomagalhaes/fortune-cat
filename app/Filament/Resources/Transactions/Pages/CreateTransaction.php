<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = TransactionResource::class;

    protected function getSteps(): array
    {
        return TransactionForm::steps();
    }
}
