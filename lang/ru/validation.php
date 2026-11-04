<?php

// Частые правила на русском, остальные берутся из английского файла.
return array_replace_recursive(require __DIR__.'/../en/validation.php', [
    'array' => 'Поле :attribute должно быть списком.',
    'between' => [
        'numeric' => 'Поле :attribute должно быть между :min и :max.',
        'string' => 'Поле :attribute должно содержать от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно быть да или нет.',
    'date' => 'Поле :attribute не является датой.',
    'date_format' => 'Поле :attribute должно быть в формате :format.',
    'in' => 'Выбранное значение :attribute неверно.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'max' => [
        'numeric' => 'Поле :attribute не должно быть больше :max.',
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
        'array' => 'Поле :attribute не должно содержать больше :max элементов.',
    ],
    'min' => [
        'numeric' => 'Поле :attribute должно быть не меньше :min.',
        'string' => 'Поле :attribute должно содержать не меньше :min символов.',
    ],
    'numeric' => 'Поле :attribute должно быть числом.',
    'required' => 'Поле :attribute обязательно.',
    'required_unless' => 'Поле :attribute обязательно.',
    'string' => 'Поле :attribute должно быть строкой.',
    'url' => 'Поле :attribute не является ссылкой.',
    'attributes' => [],
]);
