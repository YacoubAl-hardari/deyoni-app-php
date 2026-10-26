<?php

namespace App\Services;

use App\Models\Creditor;
use Illuminate\Support\Facades\Validator;

class CreditorService
{
    public function save(array $input, ?Creditor $creditor = null): Creditor
    {
        $input['name'] = trim($input['name'] ?? '');
        $data = Validator::make($input, [
            'name' => 'required|string|max:120', 'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000', 'icon' => 'required|in:store,home,directions_car,directions_bus,person,bolt,shopping_cart,airport_shuttle,category',
        ], ['name.required' => 'أدخل اسم الجهة.', 'name.max' => 'اسم الجهة طويل جدًا.'])->validate();
        $creditor ??= new Creditor;
        $creditor->fill($data)->save();

        return $creditor;
    }

    public function archive(Creditor $creditor, bool $archive): void
    {
        $creditor->update(['archived_at' => $archive ? now() : null]);
    }
}
