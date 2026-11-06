<?php

use Rakit\Validation\Validator;

function validateRole(array $input, bool $isUpdate = false): array {
    $rules = [
        'role_name' => 'required|min:2|max:100',
        'status' => 'required|in:0,1',
    ];

    if($isUpdate){
        $rules['role_id'] = 'required|integer';
    }

    $message = [
        'role_name:required' => 'Rol nomini kiritish majburiy',
        'role_name:min' => 'Rol nomi kamida 2 ta belgi bolishi kerak',
        'role_name:max' => 'Rol nomi kopi bilan 100 ta belgi bolishi kerak',
        'status:int' => 'Holat notogri tanlangan',
        'status:required' => 'Holat tanlash maajburiy',
        'role_id:integer' => 'Rol tanlash majburiy',
    ];

    $validator = new Validator();
    $validation = $validator->make($input, $rules, $message);
    $validation->validate();

    return $validation->fails() ? $validation->errors()->firstOfAll() : [];
}

