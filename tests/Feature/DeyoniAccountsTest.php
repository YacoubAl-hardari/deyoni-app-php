<?php

use App\Models\AppSetting;
use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\NativeComponents\CreditorDetails;
use App\NativeComponents\CreditorForm;
use App\NativeComponents\Creditors;
use App\NativeComponents\EntryForm;
use App\NativeComponents\Home;
use App\NativeComponents\Settings;
use App\Services\AccountStatementService;
use App\Services\DashboardService;
use App\Services\LedgerService;
use App\Support\Enums\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

function recordFinanceTestMovement(Creditor $creditor, string $kind, string $amount, string $date = '2026-10-21', string $currency = 'YER'): LedgerEntry
{
    return app(LedgerService::class)->record([
        'creditor_id' => $creditor->id, 'kind' => $kind, 'amount' => $amount,
        'occurred_at' => $date, 'currency' => $currency, 'request_id' => (string) Str::uuid(),
    ]);
}

test('the application returns a successful response', function () {
    $this->get('/')->assertStatus(200);
});

it('opens creation from the dashboard', function () {
    Native::test(Home::class)->assertSee('المتبقي عليك')->call('openCreation')->assertSet('creationVisible', true);
});

it('validates and saves creditor details', function () {
    Native::test(CreditorForm::class)->tap('حفظ الجهة')->assertSee('أدخل اسم الجهة.')->set('name', 'متجر الاختبار')->tap('حفظ الجهة');
    expect(Creditor::query()->where('name', 'متجر الاختبار')->exists())->toBeTrue();
});

it('persists appearance preferences', function (string $label, string $mode) {
    Native::test(Settings::class)->set('appearance', $label)->tap('حفظ الإعدادات');
    expect(AppSetting::read('appearance', 'system'))->toBe($mode);
    Native::test(Settings::class)->assertSet('appearance', $label);
})->with([['فاتح', 'light'], ['داكن', 'dark'], ['حسب النظام', 'system']]);

it('renders the transaction empty state', function () {
    Native::test(EntryForm::class)->assertSee('تفاصيل العملية')->assertSee('أضف جهة أولًا لتسجيل عملية مالية.');
});

it('opens payment from creditor details', function () {
    $creditor = Creditor::factory()->create(['name' => 'حساب تجريبي']);
    Native::test(CreditorDetails::class, ['id' => $creditor->id])->assertSee('حساب تجريبي')->assertSee('الحساب مسدد بالكامل')->tap('تسجيل سداد')->assertNavigatedTo('/entries/create');
});

it('refreshes cached colors without restarting', function () {
    $screen = Native::test(Settings::class);
    $screen->set('appearance', 'فاتح')->tap('حفظ الإعدادات');
    $light = TailwindParser::parse('bg-theme-background text-theme-on-surface');
    $screen->set('appearance', 'داكن')->tap('حفظ الإعدادات');
    $dark = TailwindParser::parse('bg-theme-background text-theme-on-surface');
    expect($dark['bg'])->not->toBe($light['bg']);
    expect($dark['color'])->not->toBe($light['color']);
    $screen->set('appearance', 'فاتح')->tap('حفظ الإعدادات');
    expect(TailwindParser::parse('bg-theme-background text-theme-on-surface'))->toBe($light);
});

it('separates the dashboard from searchable accounts', function () {
    Creditor::factory()->create(['name' => 'سليمان']);
    Creditor::factory()->create(['name' => 'متجر المدينة']);
    Native::test(Home::class)->assertSee('الديون والسداد')->assertDontSee('سليمان')->call('showCreditors')->assertReplacedWith('/creditors');
    Native::test(Creditors::class)->assertSet('showFilters', false)->assertSee('سليمان')
        ->set('search', 'متجر')->assertSee('متجر المدينة')->assertDontSee('سليمان')
        ->call('toggleFilters')->assertSet('showFilters', true);
});

it('validates a sheet and keeps its draft through close confirmation', function () {
    Native::test(Creditors::class)->call('openCreation')->tap('حفظ الجهة')->assertSee('أدخل اسم الجهة.')
        ->set('creationName', 'سليمان')->call('requestCloseCreation')->assertSet('discardCreation', true)->assertSet('creationVisible', false)
        ->call('keepCreation')->assertSet('creationVisible', true)->assertSet('creationName', 'سليمان')
        ->tap('حفظ الجهة')->assertSet('creationVisible', false)->assertSee('سليمان');
    expect(Creditor::query()->count())->toBe(1);
});

it('discards a sheet only after confirmation', function () {
    Native::test(Home::class)->call('openCreation')->call('requestCloseCreation')->assertSet('creationVisible', false)
        ->call('openCreation')->set('creationName', 'مسودة')->call('requestCloseCreation')->assertSet('discardCreation', true)
        ->call('onBackPressed')->assertSet('creationVisible', true)->assertSet('creationName', 'مسودة')
        ->call('requestCloseCreation')->call('closeCreation')->assertSet('creationName', '')->assertSet('creationVisible', false);
    expect(Creditor::query()->count())->toBe(0);
});

it('separates currencies and excludes cancelled movements from charts', function () {
    $this->travelTo(now()->setDate(2026, 10, 21)->startOfDay());
    $creditor = Creditor::factory()->create();
    recordFinanceTestMovement($creditor, 'debt', '3000');
    recordFinanceTestMovement($creditor, 'payment', '500');
    recordFinanceTestMovement($creditor, 'debt', '700', '2026-05-10');
    recordFinanceTestMovement($creditor, 'debt', '100', '2026-04-30');
    recordFinanceTestMovement($creditor, 'debt', '100', '2026-10-21', 'SAR');
    $cancelled = recordFinanceTestMovement($creditor, 'debt', '900');
    app(LedgerService::class)->void($cancelled->id, 'إلغاء عملية تجريبية');
    $snapshot = app(DashboardService::class)->snapshot(Currency::Yer);
    expect($snapshot['monthDebts'])->toBe(3000)->and($snapshot['monthPayments'])->toBe(500)->and($snapshot['monthCount'])->toBe(2)
        ->and($snapshot['totals']['debts'])->toBe(3300)->and($snapshot['chart'])->toHaveCount(6)->and($snapshot['chart'][0]['debts'])->toBe(700);
    expect(app(DashboardService::class)->snapshot(Currency::Sar)['monthDebts'])->toBe(10000);
    Native::test(Home::class)->assertSee('الديون والسداد');
});

it('exports all Arabic statement entries beyond pagination', function () {
    $this->travelTo(now()->setDate(2026, 10, 21)->startOfDay());
    $creditor = Creditor::factory()->create(['name' => 'سليمان أحمد العريقي', 'phone' => '770123456', 'notes' => 'كشف تفصيلي للحساب ومراجعة جميع الحركات']);
    $other = Creditor::factory()->create(['name' => 'جهة أخرى لا تظهر في الكشف']);
    recordFinanceTestMovement($other, 'debt', '999999');
    for ($index = 0; $index < 65; $index++) {
        $entry = recordFinanceTestMovement($creditor, $index % 3 === 0 ? 'payment' : 'debt', '1000', now()->subDays(65 - $index)->toDateString());
        $entry->update(['notes' => 'ملاحظة عربية للمراجعة - عملية رقم '.($index + 1)]);
    }
    $cancelled = recordFinanceTestMovement($creditor, 'debt', '900');
    app(LedgerService::class)->void($cancelled->id, 'إلغاء للمراجعة');
    recordFinanceTestMovement($creditor, 'debt', '12.50', '2026-10-21', 'SAR');
    $service = app(AccountStatementService::class);
    expect($service->totals($creditor)['SAR']['balance'])->toBe(1250)->and($service->totals($creditor)['YER']['debts'])->toBe(43000)
        ->and($service->totals($creditor)['YER']['payments'])->toBe(22000);
    $path = $service->create($creditor);
    expect(str_starts_with(file_get_contents($path), '%PDF-'))->toBeTrue()->and(filesize($path))->toBeGreaterThan(20000);
    File::copy($path, storage_path('app/private/statements/statement-preview.pdf'));
    File::delete($path);
});
