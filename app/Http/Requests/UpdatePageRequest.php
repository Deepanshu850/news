<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'slug' => 'required|unique:pages,slug,'.$this->route('page')->id,
            'name' => 'required|max:190',
            'title' => 'required|max:190',
            'lang_id' => 'required',
            'meta_description' => 'required|max:160',
            'meta_title' => 'required|max:100',
            'content' => 'nullable',
        ];
    }
}
