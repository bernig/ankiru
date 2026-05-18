<?php

return [
    'required' => 'Поле :attribute обязательно для заполнения.',
    'string' => 'Поле :attribute должно быть строкой.',
    'email' => 'Поле :attribute должно быть действительным email-адресом.',
    'boolean' => 'Поле :attribute должно иметь значение true или false.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'current_password' => 'Текущий пароль указан неверно.',
    'file' => 'Поле :attribute должно быть файлом.',
    'mimes' => 'Поле :attribute должно быть файлом одного из типов: :values.',
    'unique' => 'Такое значение поля :attribute уже используется.',

    'min' => [
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
        'file' => 'Размер файла :attribute должен быть не меньше :min килобайт.',
    ],

    'max' => [
        'string' => 'Поле :attribute не должно превышать :max символов.',
        'file' => 'Размер файла :attribute не должен превышать :max килобайт.',
    ],

    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать хотя бы одну строчную и одну заглавную букву.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один специальный символ.',
        'uncompromised' => 'Указанное значение поля :attribute найдено в утечке данных. Пожалуйста, выберите другое значение.',
    ],

    'attributes' => [
        'name' => 'имя',
        'email' => 'email',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'current_password' => 'текущий пароль',
        'remember' => 'параметр запоминания',
        'subject' => 'тема',
        'message' => 'сообщение',
        'uploadedCsvFile' => 'CSV-файл',
        'openai_api_key' => 'API-ключ OpenAI',
        'generateRowsPrompt' => 'подсказка',
        'generateRowsCount' => 'количество строк',
        'learning_context' => 'контекст обучения',
    ],
];
