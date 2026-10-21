<?php

namespace App\NativeComponents;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Native\Mobile\Edge\NativeComponent;

abstract class FormScreen extends NativeComponent
{
    public array $errors = [];

    public bool $exitConfirmation = false;

    protected array $initial = [];

    abstract protected function draft(): array;

    protected function rememberInitial(): void
    {
        $this->initial = $this->draft();
    }

    protected function attempt(\Closure $callback): void
    {
        $this->errors = [];
        try {
            $callback();
        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (QueryException $e) {
            $this->errors = ['database' => ['تعذر حفظ البيانات. حاول مرة أخرى.']];
        }
    }

    public function onBackPressed(): void
    {
        if ($this->draft() !== $this->initial) {
            $this->exitConfirmation = true;

            return;
        }
        $this->back();
    }

    public function leave(): void
    {
        $this->rememberInitial();
        $this->exitConfirmation = false;
        $this->back();
    }

    public function stay(): void
    {
        $this->exitConfirmation = false;
    }
}
