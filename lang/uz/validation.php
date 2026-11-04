<?php

// Ko'p ishlatiladigan qoidalar o'zbekcha, qolganlari inglizcha matndan olinadi.
return array_replace_recursive(require __DIR__.'/../en/validation.php', [
    'array' => ':attribute ro\'yxat bo\'lishi kerak.',
    'between' => [
        'numeric' => ':attribute :min va :max orasida bo\'lishi kerak.',
        'string' => ':attribute :min dan :max gacha belgi bo\'lishi kerak.',
    ],
    'boolean' => ':attribute ha yoki yo\'q bo\'lishi kerak.',
    'date' => ':attribute to\'g\'ri sana emas.',
    'date_format' => ':attribute :format ko\'rinishida bo\'lishi kerak.',
    'in' => 'Tanlangan :attribute noto\'g\'ri.',
    'integer' => ':attribute butun son bo\'lishi kerak.',
    'max' => [
        'numeric' => ':attribute :max dan katta bo\'lmasligi kerak.',
        'string' => ':attribute :max belgidan uzun bo\'lmasligi kerak.',
        'array' => ':attribute :max tadan ko\'p bo\'lmasligi kerak.',
    ],
    'min' => [
        'numeric' => ':attribute kamida :min bo\'lishi kerak.',
        'string' => ':attribute kamida :min belgi bo\'lishi kerak.',
    ],
    'numeric' => ':attribute son bo\'lishi kerak.',
    'required' => ':attribute kiritilishi shart.',
    'required_unless' => ':attribute kiritilishi shart.',
    'string' => ':attribute matn bo\'lishi kerak.',
    'url' => ':attribute to\'g\'ri havola emas.',
    'attributes' => [],
]);
