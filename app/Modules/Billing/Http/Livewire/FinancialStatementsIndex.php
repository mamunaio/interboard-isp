<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use Livewire\Component;
use App\Modules\Billing\Services\FinancialStatementService;
use App\Core\Tenant\TenantManager;

class FinancialStatementsIndex extends Component
{
    public ?int $tenantId = null;
    public string $reportType = 'pl'; // 'pl' or 'bs'
    public string $startDate;
    public string $endDate;

    public array $reportData = [];

    public function mount()
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->generateReport();
    }

    public function generateReport()
    {
        $service = app(FinancialStatementService::class);

        if ($this->reportType === 'pl') {
            $this->reportData = $service->generateProfitAndLoss($this->tenantId, $this->startDate, $this->endDate);
        } else {
            $this->reportData = $service->generateBalanceSheet($this->tenantId, $this->endDate);
        }
    }

    public function render()
    {
        return view('billing::livewire.financial-statements-index');
    }
}
