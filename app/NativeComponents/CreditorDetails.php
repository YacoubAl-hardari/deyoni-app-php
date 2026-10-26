<?php

namespace App\NativeComponents;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Services\AccountStatementService;
use App\Services\BalanceService;
use App\Services\CreditorService;
use App\Services\LedgerService;
use App\Services\PeriodFilter;
use Deyoni\Device\DeyoniDevice;
use Deyoni\Device\Events\DocumentResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;

class CreditorDetails extends NativeComponent
{
    public int $creditorId;

    public int $page = 1;

    public string $fromDate = '';

    public string $toDate = '';

    public string $appliedFrom = '';

    public string $appliedTo = '';

    public string $entryFilter = 'كل العمليات';

    public string $appliedKind = 'كل العمليات';

    public string $filterError = '';

    public array $loadedIds = [];

    public bool $hasMore = false;

    public int $snapshotId = 0;

    public string $cursorDate = '';

    public int $cursorId = 0;

    public bool $showFilters = false;

    public bool $showRemoval = false;

    public string $deleteFrom = '';

    public string $deleteTo = '';

    public string $deleteReason = '';

    public int $deleteCount = 0;

    protected array $removalIds = [];

    public string $period = 'كل الفترات';

    public function updatedPeriod(): void
    {
        if ($this->period !== 'فترة مخصصة') {
            [$this->fromDate, $this->toDate] = PeriodFilter::preset($this->period);
            $this->applyFilters();
        }
    }

    public function onResume(): void
    {
        $this->refreshEntries();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function applyFilters(): void
    {
        try {
            PeriodFilter::dates($this->fromDate, $this->toDate);
            $this->appliedFrom = $this->fromDate;
            $this->appliedTo = $this->toDate;
            $this->appliedKind = $this->entryFilter;
            $this->filterError = '';
            $this->refreshEntries();
        } catch (ValidationException $exception) {
            $this->filterError = $exception->validator->errors()->first();
        }
    }

    public function resetFilters(): void
    {
        $this->fromDate = $this->toDate = $this->appliedFrom = $this->appliedTo = $this->filterError = '';
        $this->entryFilter = $this->appliedKind = 'كل العمليات';
        $this->period = 'كل الفترات';
        $this->refreshEntries();
    }

    private function entriesQuery(): Builder
    {
        $query = PeriodFilter::apply(LedgerEntry::where('creditor_id', $this->creditorId)->where('id', '<=', $this->snapshotId), $this->appliedFrom, $this->appliedTo);
        if ($this->appliedKind === 'الملغاة') {
            return $query->whereNotNull('voided_at');
        }
        $query->whereNull('voided_at');
        $operation = EntryForm::OPERATIONS[$this->appliedKind] ?? null;

        return $operation ? $query->where('kind', $operation[0])->where('direction', $operation[1]) : $query;
    }

    public function refreshEntries(): void
    {
        $this->snapshotId = (int) LedgerEntry::where('creditor_id', $this->creditorId)->max('id');
        $this->loadedIds = [];
        $this->cursorDate = '';
        $this->cursorId = 0;
        $this->hasMore = true;
        $this->loadMore();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }
        $query = $this->entriesQuery();
        if ($this->cursorDate !== '') {
            $query->where(fn (Builder $query) => $query->where('occurred_at', '<', $this->cursorDate)->orWhere(fn (Builder $query) => $query->where('occurred_at', $this->cursorDate)->where('id', '<', $this->cursorId)));
        }
        $batch = $query->orderByDesc('occurred_at')->orderByDesc('id')->limit(31)->get();
        $this->hasMore = $batch->count() > 30;
        $batch = $batch->take(30);
        $this->loadedIds = array_values(array_unique([...$this->loadedIds, ...$batch->modelKeys()]));
        if ($last = $batch->last()) {
            $this->cursorDate = $last->occurred_at->format('Y-m-d');
            $this->cursorId = $last->id;
        }
    }

    public function openRemoval(): void
    {
        $this->showRemoval = true;
        $this->deleteCount = 0;
        $this->removalIds = [];
    }

    public function cancelRemoval(): void
    {
        $this->showRemoval = false;
        $this->deleteCount = 0;
        $this->removalIds = [];
    }

    public function previewRemoval(): void
    {
        try {
            Validator::make(['from' => $this->deleteFrom, 'to' => $this->deleteTo, 'reason' => trim($this->deleteReason)], ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from', 'reason' => 'required|string|max:1000'])->validate();
            $this->removalIds = PeriodFilter::apply(LedgerEntry::where('creditor_id', $this->creditorId)->whereNull('voided_at'), $this->deleteFrom, $this->deleteTo)->pluck('id')->all();
            $this->deleteCount = count($this->removalIds);
            $this->filterError = $this->deleteCount ? '' : 'لا توجد قيود فعالة في هذه الفترة.';
        } catch (ValidationException $exception) {
            $this->filterError = $exception->validator->errors()->first();
        }
    }

    public function confirmRemoval(): void
    {
        if (! $this->removalIds) {
            return;
        }
        DB::transaction(function (): void {
            foreach ($this->removalIds as $id) {
                app(LedgerService::class)->void($id, $this->deleteReason);
            }
        });
        $this->cancelRemoval();
        $this->refreshEntries();
    }

    public function newReceivable(): void
    {
        $this->navigate('/entries/create', ['kind' => 'debt', 'direction' => 'receivable', 'creditor_id' => $this->creditorId]);
    }

    public function receivePayment(): void
    {
        $this->navigate('/entries/create', ['kind' => 'payment', 'direction' => 'receivable', 'creditor_id' => $this->creditorId]);
    }

    public function schedulePayment(): void
    {
        $this->navigate('/payment-promises', ['creditor_id' => $this->creditorId]);
    }

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
            $this->statementPath = app(AccountStatementService::class)->create(Creditor::findOrFail($this->creditorId), $this->appliedFrom, $this->appliedTo);
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
        $this->refreshEntries();
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
            'entries' => LedgerEntry::where('creditor_id', $this->creditorId)->whereIn('id', $this->loadedIds)->orderByDesc('occurred_at')->orderByDesc('id')->get(),
            'entryCount' => $this->entriesQuery()->count(),
        ]);
    }
}
