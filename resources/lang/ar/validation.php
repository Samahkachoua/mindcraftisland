<?php

return [

    'required'        => 'حقل :attribute مطلوب.',
    'string'          => 'يجب أن يكون :attribute نصاً.',
    'numeric'         => 'يجب أن يكون :attribute رقماً.',
    'digits_between'  => 'يجب أن يكون :attribute بين :min و :max رقماً.',
    'max'             => [
        'string' => 'يجب ألا يتجاوز :attribute :max حرفاً.',
    ],
    'date'            => 'يجب أن يكون :attribute تاريخاً صحيحاً.',
    'before'          => 'يجب أن يكون :attribute تاريخاً قبل :date.',

    'attributes'      => [
        'full_name'                  => 'الاسم الكامل',
        'phone_number'               => 'رقم الهاتف',
        'emergency_contact_number'   => 'رقم جهة اتصال الطوارئ',
        'mother_name'                => 'اسم الأم',
        'date_of_birth'              => 'تاريخ الميلاد',
        'medical_conditions'         => 'المشاكل الصحية',
        'field_of_interests'         => 'الحقول المهمة',
        'photo_video_consent'        => 'الموافقة على النشر',
    ],

];
