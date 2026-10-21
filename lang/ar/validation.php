<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'present' => 'حقل :attribute مفقود.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'array' => 'صيغة :attribute غير صحيحة.',
    'in' => 'القيمة المحددة في :attribute غير مدعومة.',
    'exists' => 'الجهة المحددة غير موجودة.',
    'date' => 'أدخل تاريخًا صحيحًا في :attribute.',
    'date_format' => 'صيغة :attribute غير صحيحة.',
    'uuid' => 'معرّف العملية غير صالح. أعد فتح النموذج.',
    'json' => 'صيغة بيانات :attribute غير صحيحة.',
    'distinct' => 'توجد قيمة مكررة في :attribute.',
    'max' => ['string' => 'الحد الأقصى لحقل :attribute هو :max حرفًا.', 'numeric' => 'قيمة :attribute أكبر من الحد المسموح.', 'array' => 'عدد عناصر :attribute أكبر من الحد المسموح.'],
    'min' => ['string' => 'يجب ألا يقل :attribute عن :min أحرف.', 'numeric' => 'قيمة :attribute أقل من الحد المسموح.'],
    'attributes' => ['name' => 'اسم الجهة', 'phone' => 'رقم الهاتف', 'notes' => 'الملاحظة', 'reason' => 'السبب',
        'amount' => 'المبلغ', 'currency' => 'العملة', 'kind' => 'نوع العملية', 'occurred_at' => 'التاريخ',
        'creditor_id' => 'الجهة', 'request_id' => 'معرّف العملية', 'password' => 'كلمة المرور'],
];
