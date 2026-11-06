<?php

use Rakit\Validation\Validator;

function validateUser(array $input, bool $isUpdate = false): array
{
    $rules = [
        'user_name' => 'required|min:2|max:100',
        'status'    => 'required|in:0,1',
        'role_id'   => 'required|integer',
    ];

    if ($isUpdate) {
        $rules['user_id'] = 'required|integer';
    }

    $messages = [
        'user_name:required' => "Ism familiyani kiritish majbur",
        'user_name:min'      => "Ism familiya kamida :min ta belgidan iborat bo'lsin",
        'user_name:max'      => "Ism familiya :max ta belgidan oshmasin",
        'status:required'    => "Holatni tanlang",
        'status:in'          => "Holat noto'g'ri tanlangan",
        'role_id:required'   => "Rolni tanlang",
        'role_id:integer'    => "Rol noto'g'ri tanlangan",
        'user_id:required'   => "Foydalanuvchi aniqlanmadi",
        'user_id:integer'    => "Foydalanuvchi id noto'g'ri",
    ];

    $validator  = new Validator();
    $validation = $validator->make($input, $rules, $messages);
    $validation->validate();

    return $validation->fails() ? $validation->errors()->firstOfAll() : [];
}