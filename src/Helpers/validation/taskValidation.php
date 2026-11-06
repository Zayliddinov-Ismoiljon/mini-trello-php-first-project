<?php

use Rakit\Validation\Validator;

function validateTask (array $input, bool $isUpdate = false) : array{
    $rules = [
        'project_id' => 'required|integer',
        'task_name' => 'required|min:2|max:300',
        'user_id' => 'required|integer',
        'deadline' => 'required|date:Y-m-d',
    ];

    if ($isUpdate) {
        $rules['task_id'] = 'required|integer';
    } else {
        $rules['status']    = 'required|in:1,2,3';
        $rules['deadline'] .= '|after:yesterday';
    }

    $message = [
        'project_id:required' => 'Loyiha tanlash majburiy',
        'project_id:integer' => 'Bunday loyiha mavjud emas',
        'task_name:required' => 'Task nomi majburiy',
        'task_name:min' => 'Taask nomi kamida 2 ta belgidan iboraat bolishi kerak',
        'task_name:max' => 'Taask nomi kopida 300 ta belgidan iboraat bolishi kerak',
        'user_id:required' => 'Bajaruvchi tanlash majburiy',
        'user_id:integer' => 'Tizimda mavjud bolmagaan bajaruvchi tanlandi',
        'deadline:required' => 'Task muddati majburiy',
        'deadline:date' => "Sana notogri formatda"
    ];

    $validator = new Validator();
    $validation = $validator->make($input, $rules, $message);
    $validation->validate();

    return $validation->fails() ? $validation->errors()->firstOfAll() : [];
}
