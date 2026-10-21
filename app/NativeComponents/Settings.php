<?php

namespace App\NativeComponents;

use App\Models\AppSetting;
use App\Services\AppearanceService;
use App\Services\BackupService;
use Deyoni\Device\DeyoniDevice;
use Deyoni\Device\Events\DocumentResult;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;

class Settings extends FormScreen
{
    public string $appearance = 'حسب النظام';

    public string $currency = 'ريال يمني';

    public string $password = '';

    public string $status = '';

    public string $summary = '';

    public bool $busy = false;

    protected string $pendingBytes = '';

    protected string $pendingPassword = '';

    protected string $exportPath = '';

    public function mount(): void
    {
        $this->appearance = ['system' => 'حسب النظام', 'light' => 'فاتح', 'dark' => 'داكن'][AppSetting::read('appearance', 'system')];
        $this->currency = AppSetting::read('currency', 'YER') === 'SAR' ? 'ريال سعودي' : 'ريال يمني';
        $this->rememberInitial();
    }

    protected function draft(): array
    {
        return [$this->appearance, $this->currency];
    }

    public function save(): void
    {
        AppSetting::updateOrCreate(['key' => 'appearance'], ['value' => ['حسب النظام' => 'system', 'فاتح' => 'light', 'داكن' => 'dark'][$this->appearance] ?? 'system']);
        AppSetting::updateOrCreate(['key' => 'currency'], ['value' => $this->currency === 'ريال سعودي' ? 'SAR' : 'YER']);
        app(AppearanceService::class)->apply();
        $this->rememberInitial();
        $this->status = 'تم حفظ الإعدادات';
    }

    public function export(): void
    {
        if ($this->busy) {
            return;
        }
        $this->attempt(function (): void {
            $bytes = app(BackupService::class)->export($this->password);
            $directory = storage_path('app/private/backups');
            if (! is_dir($directory)) {
                mkdir($directory, 0700, true);
            }
            $this->exportPath = $directory.'/export.deyoni';
            if (file_put_contents($this->exportPath, $bytes, LOCK_EX) === false) {
                throw ValidationException::withMessages(['backup' => 'تعذر إنشاء ملف النسخة.']);
            }
            $this->busy = app(DeyoniDevice::class)->documents('export', $this->exportPath);
            $this->status = $this->busy ? 'اختر مكان حفظ النسخة على جهازك' : 'حفظ الملف يتطلب تشغيل نسخة Android';
            $this->password = '';
        });
    }

    public function import(): void
    {
        if ($this->busy) {
            return;
        }
        $this->summary = '';
        $this->pendingBytes = '';
        $this->attempt(function (): void {
            Validator::make(['password' => $this->password], ['password' => 'required|min:8|max:1024'])->validate();
            $this->pendingPassword = $this->password;
            $this->busy = app(DeyoniDevice::class)->documents('import');
            $this->status = $this->busy ? 'اختر ملف النسخة الاحتياطية' : 'اختيار الملف يتطلب تشغيل نسخة Android';
            $this->password = '';
        });
    }

    #[On(DocumentResult::class)]
    public function documentResult(bool $success, string $path, string $action): void
    {
        $this->busy = false;
        if ($this->exportPath && is_file($this->exportPath)) {
            unlink($this->exportPath);
            $this->exportPath = '';
        }
        if (! $success) {
            $this->status = 'لم تكتمل العملية؛ يمكنك المحاولة مجددًا.';
            $this->pendingPassword = '';

            return;
        }
        if ($action === 'export') {
            $this->status = 'تم حفظ النسخة المشفرة بنجاح';

            return;
        }
        $this->attempt(function () use ($path): void {
            $real = realpath($path);
            if (! $real || ! str_starts_with(basename($real), 'deyoni-import-') || filesize($real) > BackupService::MAX_BYTES) {
                throw ValidationException::withMessages(['backup' => 'تعذر قراءة ملف النسخة.']);
            }
            try {
                $this->pendingBytes = file_get_contents($real);
                $data = app(BackupService::class)->inspect($this->pendingBytes, $this->pendingPassword);
                $this->summary = count($data['creditors']).' جهة و'.count($data['ledger_entries']).' حركة. ستستبدل هذه النسخة جميع البيانات الحالية.';
            } finally {
                unlink($real);
            }
        });
        if ($this->errors) {
            $this->pendingBytes = '';
            $this->pendingPassword = '';
        }
    }

    public function restore(): void
    {
        if (! $this->pendingBytes || ! $this->summary) {
            return;
        }
        $this->attempt(function (): void {
            app(BackupService::class)->restore($this->pendingBytes, $this->pendingPassword);
            $this->dismissRestore();
            $this->mount();
            app(AppearanceService::class)->apply();
            $this->status = 'تمت الاستعادة. حُفظت نقطة استرجاع مشفرة داخل التطبيق.';
        });
    }

    public function dismissRestore(): void
    {
        $this->summary = '';
        $this->pendingBytes = '';
        $this->pendingPassword = '';
    }

    public function render(): View
    {
        return view('native.settings');
    }
}
