<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stream' => 'required|array',
            'stream.*.stream_url' => 'required|string',
            'stream.*.name' => ['required', 'string', function ($attribute, $value, $fail) {
                $allNames = collect($this->input('stream'))->pluck('name')->toArray();
                if (array_count_values($allNames)[$value] > 1) {
                    $fail('The ' . $attribute . ' must be unique.');
                }
            }],
            'stream.*.primary_key' => 'nullable|string',
            'stream.*.record_selector' => 'required|boolean',
            'stream.*.field_path' => 'nullable|string|required_if:stream.*.record_selector,true',
            'stream.*.record_filter' => 'nullable|string|required_if:stream.*.record_selector,true',
            'stream.*.query_parameters' => 'nullable|array',
            'stream.*.query_parameters.*.key' => 'nullable|string',
            'stream.*.query_parameters.*.value' => 'nullable|string',
        ];
    }
}
