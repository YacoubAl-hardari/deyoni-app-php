<?php

namespace App\NativeComponents;

use App\Services\CreditorService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Native\Mobile\Facades\Dialog;

trait InteractsWithCreditorSheet
{
    public bool $creationVisible = false;

    public bool $discardCreation = false;

    public string $creationName = '';

    public string $creationPhone = '';

    public string $creationNotes = '';

    public string $creationIcon = 'شخص';

    public array $creationErrors = [];

    public function showDashboard(): void
    {
        $this->replace('/');
    }

    public function showCreditors(): void
    {
        $this->replace('/creditors');
    }

    public function openCreation(): void
    {
        $this->creationVisible = true;
    }

    public function requestCloseCreation(): void
    {
        if ($this->creationName !== '' || $this->creationPhone !== '' || $this->creationNotes !== '' || $this->creationIcon !== 'شخص') {
            $this->discardCreation = true;
            $this->creationVisible = false;

            return;
        }
        $this->closeCreation();
    }

    public function keepCreation(): void
    {
        $this->discardCreation = false;
        $this->creationVisible = true;
    }

    public function closeCreation(): void
    {
        $this->creationVisible = false;
        $this->discardCreation = false;
        $this->creationName = '';
        $this->creationPhone = '';
        $this->creationNotes = '';
        $this->creationIcon = 'شخص';
        $this->creationErrors = [];
    }

    public function saveCreation(): void
    {
        if (! $this->creationVisible) {
            return;
        }
        $this->creationErrors = [];
        try {
            app(CreditorService::class)->save([
                'name' => $this->creationName,
                'phone' => $this->creationPhone,
                'notes' => $this->creationNotes,
                'icon' => CreditorForm::icons()[$this->creationIcon] ?? 'person',
            ]);
            $this->closeCreation();
            Dialog::toast('تمت إضافة الجهة');
        } catch (ValidationException $exception) {
            $this->creationErrors = $exception->errors();
        } catch (QueryException $exception) {
            $this->creationErrors = ['database' => ['تعذر حفظ الجهة. حاول مرة أخرى.']];
        }
    }

    public function onBackPressed(): void
    {
        if ($this->discardCreation) {
            $this->keepCreation();

            return;
        }
        if ($this->creationVisible) {
            $this->requestCloseCreation();

            return;
        }
        $this->back();
    }
}
