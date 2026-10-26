<?php

namespace App\NativeComponents;

use App\Models\Creditor;
use App\Models\PaymentPromise;
use App\Services\PaymentPromiseService;
use App\Support\Money\Money;
use Illuminate\View\View;

class PaymentPromises extends FormScreen
{
    public ?int $promiseId = null;

    public string $creditorChoice = '';

    public string $directionLabel = 'سداد أستلمه';

    public string $currencyLabel = 'ريال يمني';

    public string $amount = '';

    public string $dueAt = '';

    public string $notes = '';

    public string $filter = 'المستحقة والمتأخرة';

    public bool $showForm = false;

    public int $page = 1;

    public int $settleId = 0;

    public string $settledAt = '';

    public function mount(): void
    {
        $this->dueAt = $this->settledAt = today()->toDateString();
        if ($id = $this->data('creditor_id')) {
            $creditor = Creditor::findOrFail($id);
            $this->creditorChoice = $creditor->id.' · '.$creditor->name;
            $this->showForm = true;
        }
        $this->rememberInitial();
    }

    protected function draft(): array
    {
        return [$this->creditorChoice, $this->directionLabel, $this->currencyLabel, $this->amount, $this->dueAt, $this->notes];
    }

    public function newPromise(): void
    {
        $this->promiseId = null;
        $this->amount = $this->notes = '';
        $this->dueAt = today()->toDateString();
        $this->showForm = true;
        $this->rememberInitial();
    }

    public function editPromise(int $id): void
    {
        $promise = PaymentPromise::with('creditor')->where('status', 'pending')->findOrFail($id);
        $this->promiseId = $id;
        $this->creditorChoice = $promise->creditor_id.' · '.$promise->creditor->name;
        $this->directionLabel = $promise->direction === 'receivable' ? 'سداد أستلمه' : 'سداد أدفعه';
        $this->currencyLabel = $promise->currency->label();
        $this->amount = Money::input($promise->amount_minor, $promise->currency);
        $this->dueAt = $promise->due_at->toDateString();
        $this->notes = $promise->notes ?? '';
        $this->showForm = true;
        $this->rememberInitial();
    }

    public function save(): void
    {
        if (! $this->showForm) {
            return;
        }
        $this->attempt(function (): void {
            app(PaymentPromiseService::class)->save([
                'creditor_id' => (int) $this->creditorChoice, 'direction' => $this->directionLabel === 'سداد أستلمه' ? 'receivable' : 'payable',
                'currency' => $this->currencyLabel === 'ريال سعودي' ? 'SAR' : 'YER', 'amount' => $this->amount, 'due_at' => $this->dueAt, 'notes' => $this->notes,
            ], $this->promiseId);
            $this->showForm = false;
            $this->rememberInitial();
        });
    }

    public function requestSettlement(int $id): void
    {
        $this->settleId = $id;
        $this->settledAt = today()->toDateString();
    }

    public function dismissSettlement(): void
    {
        $this->settleId = 0;
    }

    public function confirmSettlement(): void
    {
        if (! $this->settleId) {
            return;
        }
        $this->attempt(function (): void {
            app(PaymentPromiseService::class)->settle($this->settleId, $this->settledAt);
            $this->settleId = 0;
        });
    }

    public function cancelPromise(): void
    {
        if ($this->promiseId) {
            app(PaymentPromiseService::class)->cancel($this->promiseId);
            $this->showForm = false;
            $this->rememberInitial();
        }
    }

    public function updatedFilter(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function render(): View
    {
        $query = PaymentPromise::with('creditor')->whereHas('creditor', fn ($query) => $query->whereNull('archived_at'));
        match ($this->filter) {
            'المكتملة' => $query->where('status', 'fulfilled'),
            'الملغاة' => $query->where('status', 'cancelled'),
            'القادمة' => $query->where('status', 'pending')->whereDate('due_at', '>', today()),
            'كل المواعيد' => $query,
            default => $query->where('status', 'pending')->whereDate('due_at', '<=', today()),
        };

        return view('native.payment-promises', [
            'promises' => $query->orderBy('due_at')->orderBy('id')->paginate(20, ['*'], 'page', $this->page),
            'choices' => Creditor::whereNull('archived_at')->orderBy('name')->get()->map(fn (Creditor $creditor) => $creditor->id.' · '.$creditor->name)->all(),
            'settling' => $this->settleId ? PaymentPromise::with('creditor')->find($this->settleId) : null,
        ]);
    }
}
