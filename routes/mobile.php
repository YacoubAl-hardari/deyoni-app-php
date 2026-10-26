<?php

use App\NativeComponents\CreditorDetails;
use App\NativeComponents\CreditorForm;
use App\NativeComponents\Creditors;
use App\NativeComponents\EntryForm;
use App\NativeComponents\Home;
use App\NativeComponents\PaymentPromises;
use App\NativeComponents\Settings;
use Illuminate\Support\Facades\Route;

Route::native('/', Home::class)->name('mobile.home');
Route::native('/creditors', Creditors::class)->name('mobile.creditors');
Route::native('/creditors/create', CreditorForm::class);
Route::native('/creditors/{id}/edit', CreditorForm::class)->whereNumber('id');
Route::native('/creditors/{id}', CreditorDetails::class)->whereNumber('id');
Route::native('/entries/create', EntryForm::class);
Route::native('/entries/{id}/edit', EntryForm::class)->whereNumber('id');
Route::native('/settings', Settings::class);
Route::native('/payment-promises', PaymentPromises::class);
