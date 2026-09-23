<?php

namespace App\Filament\Resources\Sales\Pages;

class DashboardSale extends CreateSale
{
    protected string $view = 'filament.sales.dashboard-sale';

    public function create(bool $another = false): void
    {
        parent::create(another: true);
    }

    protected function getFormActions(): array
    {
        return [$this->getCreateFormAction()->label('Complete sale')];
    }
}
