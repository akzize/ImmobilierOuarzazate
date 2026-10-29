<?php

/*
|--------------------------------------------------------------------------
| Arabic validation messages for the admin panel
|--------------------------------------------------------------------------
|
| Rules missing here fall back to Laravel's English messages.
| Field names come from the form labels, already translated.
|
*/

return [
    'accepted' => 'يجب قبول :attribute.',
    'active_url' => 'يجب أن يكون :attribute رابطاً صالحاً.',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخاً بعد :date أو يساويه.',
    'alpha' => 'يجب أن يحتوي :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخاً قبل :date أو يساويه.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عدد من العناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفاً.',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute نعم أو لا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'يجب أن يكون :attribute تاريخاً صالحاً.',
    'date_equals' => 'يجب أن يكون :attribute تاريخاً يساوي :date.',
    'date_format' => 'يجب أن يطابق :attribute الصيغة :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal أرقام عشرية.',
    'different' => 'يجب أن يكون :attribute مختلفاً عن :other.',
    'digits' => 'يجب أن يتكون :attribute من :digits أرقام.',
    'digits_between' => 'يجب أن يتكون :attribute من عدد أرقام بين :min و :max.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صالحاً.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => 'القيمة المختارة في :attribute غير صالحة.',
    'exists' => 'القيمة المختارة في :attribute غير صالحة.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'filled' => 'يجب ملء :attribute.',
    'gt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفاً.',
    ],
    'gte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value أو تساويها.',
        'string' => 'يجب أن يكون طول :attribute :value حرفاً على الأقل.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المختارة في :attribute غير صالحة.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'json' => 'يجب أن يكون :attribute نص JSON صالحاً.',
    'lt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أصغر من :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفاً.',
    ],
    'lte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أصغر من :value أو تساويها.',
        'string' => 'يجب ألا يتجاوز طول :attribute :value حرفاً.',
    ],
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تتجاوز قيمة :attribute :max.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب أن تكون قيمة :attribute :min على الأقل.',
        'string' => 'يجب أن يكون طول :attribute :min حرفاً على الأقل.',
    ],
    'not_in' => 'القيمة المختارة في :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. المرجو اختيار :attribute أخرى.',
    ],
    'present' => 'يجب أن يكون :attribute موجوداً.',
    'prohibited' => ':attribute غير مسموح به.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute إلزامي.',
    'required_if' => 'حقل :attribute إلزامي عندما يكون :other هو :value.',
    'required_unless' => 'حقل :attribute إلزامي ما لم يكن :other ضمن :values.',
    'required_with' => 'حقل :attribute إلزامي عند وجود :values.',
    'required_without' => 'حقل :attribute إلزامي عند غياب :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفاً.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => ':attribute مستعمل من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'يجب أن يكون :attribute رابطاً صالحاً.',
    'uuid' => 'يجب أن يكون :attribute معرّفاً UUID صالحاً.',

    'custom' => [],

    'attributes' => [],
];
