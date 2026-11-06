<?php

use Rakit\Validation\Validator;

function validateProject(array $input, bool $isUpdate = false) : array {
    $rules = [
        'project_name' => 'required',
        'user_id' => 'required|integer',
        'status' => 'required|in:0,1',
    ];

    if($isUpdate){
        $rules['project_id'] = 'required|integer';
    }

    $message = [
        'project_name:required' => 'Loyiha nomi majburiy',
        'user_id:integer' => 'Nomalum foydalanuvchi',
        'user_id:required' => 'Masul tanlash majburiy',
        'status:integer' => "Holat notogri tanlangan",
    ];

    $validator = new Validator();
    $validtion = $validator->make($input, $rules, $message);
    $validtion->validate();

    return $validtion->fails() ? $validtion->errors()->firstOfAll() : [];
}
