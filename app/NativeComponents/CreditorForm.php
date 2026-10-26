<?php

namespace App\NativeComponents;

use App\Models\Creditor;
use App\Services\CreditorService;
use Illuminate\View\View;
use Native\Mobile\Facades\Dialog;

class CreditorForm extends FormScreen
{
    public ?int $creditorId = null;

    public string $name = '';

    public string $phone = '';

    public string $notes = '';

    public string $iconLabel = 'شخص';

    public bool $saved = false;

    public function mount(): void
    {
        $this->creditorId = $this->param('id') ? (int) $this->param('id') : null;
        if ($this->creditorId) {
            $c = Creditor::findOrFail($this->creditorId);
            $this->name = $c->name;
            $this->phone = $c->phone ?? '';
            $this->notes = $c->notes ?? '';
            $this->iconLabel = array_search($c->icon, self::icons(), true) ?: 'شخص';
        }
        $this->rememberInitial();
    }

    public static function icons(): array
    {
        return ['متجر' => 'store', 'منزل' => 'home', 'سيارة' => 'directions_car', 'حافلة' => 'directions_bus', 'شخص' => 'person', 'كهرباء' => 'bolt', 'سوبر ماركت' => 'shopping_cart', 'باص' => 'airport_shuttle', 'أخرى' => 'category'];
    }

    protected function draft(): array
    {
        return [$this->name, $this->phone, $this->notes, $this->iconLabel];
    }

    public function save(): void
    {
        if ($this->saved) {
            return;
        }
        $this->attempt(function (): void {
            $c = app(CreditorService::class)->save([
                'name' => $this->name,
                'phone' => $this->phone,
                'notes' => $this->notes,
                'icon' => self::icons()[$this->iconLabel] ?? 'person',
            ], $this->creditorId ? Creditor::findOrFail($this->creditorId) : null);
            $this->saved = true;
            $this->rememberInitial();
            Dialog::toast('تم حفظ الجهة');
            $this->back();
        });
    }

    public function render(): View
    {
        return view('native.creditor-form', ['duplicate' => trim($this->name) !== '' && Creditor::where('name', trim($this->name))->when($this->creditorId, fn ($q) => $q->where('id', '!=', $this->creditorId))->exists()]);
    }
}
