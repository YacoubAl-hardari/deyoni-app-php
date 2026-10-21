<?php

namespace App\NativeComponents;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Services\AccountStatementService;
use App\Services\BalanceService;
use App\Services\CreditorService;
use Deyoni\Device\DeyoniDevice;
use Deyoni\Device\Events\DocumentResult;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;

class CreditorDetails extends NativeComponent
{
    public int $creditorId;

    public int $page = 1;

    public bool $exporting = false;

    public string $exportStatus = '';

    protected string $statementPath = '';

    public function exportStatement(): void
    {
        $this->prepareStatement('export-pdf');
    }

    public function shareStatement(): void
    {
        $this->prepareStatement('share-pdf');
    }

    private function prepareStatement(string $action): void
    {
        if ($this->exporting) {
            return;
        }
        $this->exporting = true;
        try {
            $this->statementPath = app(AccountStatementService::class)->create(Creditor::findOrFail($this->creditorId));
            $this->exporting = app(DeyoniDevice::class)->documents($action, $this->statementPath);
            $this->exportStatus = $this->exporting ? 'اختر مكان الحفظ أو تطبيق المشاركة.' : 'حفظ ومشاركة PDF متاحان في تطبيق Android.';
            if (! $this->exporting) {
                $this->removeStatement();
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->exporting = false;
            $this->exportStatus = 'تعذر تجهيز الكشف. حاول مرة أخرى.';
            $this->removeStatement();
        }
    }

    #[On(DocumentResult::class)]
    public function statementResult(bool $success, string $path, string $action): void
    {
        if (! in_array($action, ['export-pdf', 'share-pdf'], true) || ! $this->exporting) {
            return;
        }
        $this->exporting = false;
        $this->exportStatus = ! $success ? 'لم يكتمل التصدير؛ يمكنك المحاولة مجددًا.' : ($action === 'export-pdf' ? 'تم حفظ كشف الحساب.' : 'تم فتح خيارات المشاركة.');
        $this->removeStatement();
    }

    private function removeStatement(): void
    {
        if ($this->statementPath !== '' && is_file($this->statementPath)) {
            unlink($this->statementPath);
        }
        $this->statementPath = '';
    }

    public function newDebt(): void
    {
        $this->navigate('/entries/create', ['kind' => 'debt', 'creditor_id' => $this->creditorId]);
    }

    public function newPayment(): void
    {
        $this->navigate('/entries/create', ['kind' => 'payment', 'creditor_id' => $this->creditorId]);
    }

    public function mount(): void
    {
        $this->creditorId = (int) $this->param('id');
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function archive(): void
    {
        $c = Creditor::findOrFail($this->creditorId);
        app(CreditorService::class)->archive($c, ! $c->archived_at);
    }

    public function render(): View
    {
        return view('native.creditor-details', [
            'creditor' => Creditor::findOrFail($this->creditorId),
            'balances' => app(BalanceService::class)->grouped()->where('creditor_id', $this->creditorId),
            'entries' => LedgerEntry::where('creditor_id', $this->creditorId)->orderByDesc('occurred_at')->orderByDesc('id')->paginate(30, ['*'], 'page', $this->page),
        ]);
    }
}
