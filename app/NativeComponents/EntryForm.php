<?php

namespace App\NativeComponents;

use App\Models\AppSetting;
use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Services\BalanceService;
use App\Services\LedgerService;
use App\Support\Enums\Currency;
use App\Support\Money\Money;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Native\Mobile\Facades\Dialog;

class EntryForm extends FormScreen
{
    public ?int $entryId = null;

    public string $creditorChoice = '';

    public string $kindLabel = 'دين عليك';

    public const OPERATIONS = ['دين عليك' => ['debt', 'payable'], 'سداد دفعته' => ['payment', 'payable'], 'مبلغ لك لدى الجهة' => ['debt', 'receivable'], 'سداد استلمته' => ['payment', 'receivable']];

    public string $amount = '';

    public string $currencyLabel = 'ريال يمني';

    public string $occurredAt = '';

    public string $notes = '';

    public string $reason = '';

    public string $requestId = '';

    public bool $saved = false;

    public bool $cancelledEntry = false;

    public string $confirmation = '';

    protected array $pending = [];

    public function mount(): void
    {
        $this->requestId = (string) Str::uuid();
        $this->occurredAt = now()->format('Y-m-d');
        $this->currencyLabel = Currency::from(AppSetting::read('currency', 'YER'))->label();
        $this->kindLabel = $this->data('kind', 'debt') === 'payment' ? 'سداد دفعته' : 'دين عليك';
        if ($this->data('direction') === 'receivable') {
            $this->kindLabel = $this->data('kind') === 'payment' ? 'سداد استلمته' : 'مبلغ لك لدى الجهة';
        }
        if ($id = $this->data('creditor_id')) {
            $c = Creditor::findOrFail($id);
            $this->creditorChoice = $c->id.' · '.$c->name;
        }
        $this->entryId = $this->param('id') ? (int) $this->param('id') : null;
        if ($this->entryId) {
            $e = LedgerEntry::with('creditor')->findOrFail($this->entryId);
            $this->creditorChoice = $e->creditor_id.' · '.$e->creditor->name;
            $this->kindLabel = $e->operationLabel();
            $this->currencyLabel = $e->currency->label();
            $this->amount = Money::input($e->amount_minor, $e->currency);
            $this->occurredAt = $e->occurred_at->format('Y-m-d');
            $this->notes = $e->notes ?? '';
            $this->cancelledEntry = $e->voided_at !== null;
        }
        $this->rememberInitial();
    }

    protected function draft(): array
    {
        return [$this->creditorChoice, $this->kindLabel, $this->amount, $this->currencyLabel, $this->occurredAt, $this->notes, $this->reason];
    }

    public function save(): void
    {
        if ($this->saved || $this->cancelledEntry) {
            return;
        }
        $this->attempt(function (): void {
            $input = ['creditor_id' => (int) $this->creditorChoice, 'kind' => self::OPERATIONS[$this->kindLabel][0] ?? '', 'direction' => self::OPERATIONS[$this->kindLabel][1] ?? '',
                'currency' => $this->currencyLabel === 'ريال سعودي' ? 'SAR' : 'YER', 'amount' => $this->amount,
                'occurred_at' => $this->occurredAt, 'notes' => $this->notes, 'request_id' => $this->requestId];
            $valid = app(LedgerService::class)->validated($input);
            $this->pending = $input;
            $balance = app(BalanceService::class)->balance($valid['creditor_id'], Currency::from($valid['currency']));
            if ($this->entryId) {
                $old = LedgerEntry::findOrFail($this->entryId);
                if ($old->currency->value === $valid['currency']) {
                    $balance -= $old->signedAmount();
                }
            }
            $overpay = $valid['kind'] === 'payment' && $valid['amount_minor'] > max(0, $valid['direction'] === 'receivable' ? -$balance : $balance);
            if ($this->entryId || $overpay) {
                $this->confirmation = ($this->entryId ? 'سيُلغى أثر الحركة القديمة وتُحفظ حركة بديلة مع سبب التصحيح. ' : '').
                    ($overpay ? ($valid['direction'] === 'receivable' ? 'السداد المستلم أكبر من المبلغ الذي لك. سيصبح الفرق مبلغًا عليك للجهة.' : 'السداد المدفوع أكبر من المبلغ الذي عليك. سيصبح الفرق رصيدًا لك لدى الجهة.') : 'هل تؤكد التصحيح؟');

                return;
            }
            $this->commit();
        });
    }

    public function commit(): void
    {
        if ($this->saved || ! $this->pending) {
            return;
        }
        $this->attempt(function (): void {
            $this->entryId ? app(LedgerService::class)->correct($this->entryId, $this->pending, $this->reason) : app(LedgerService::class)->record($this->pending);
            $this->saved = true;
            $this->confirmation = '';
            $this->rememberInitial();
            Dialog::toast('تم حفظ العملية');
            $this->back();
        });
    }

    public function requestVoid(): void
    {
        $this->confirmation = 'هل تؤكد إلغاء الحركة؟ سيبقى أثرها في السجل.';
        $this->pending = ['void' => true];
    }

    public function confirm(): void
    {
        if (isset($this->pending['void'])) {
            $this->attempt(function (): void {
                app(LedgerService::class)->void($this->entryId, $this->reason);
                $this->saved = true;
                $this->confirmation = '';
                $this->rememberInitial();
                $this->back();
            });
        } else {
            $this->commit();
        }
    }

    public function dismiss(): void
    {
        $this->confirmation = '';
        $this->pending = [];
    }

    public function render(): View
    {
        return view('native.entry-form', ['choices' => Creditor::whereNull('archived_at')->orderBy('name')->get()->map(fn ($c) => $c->id.' · '.$c->name)->all()]);
    }
}
