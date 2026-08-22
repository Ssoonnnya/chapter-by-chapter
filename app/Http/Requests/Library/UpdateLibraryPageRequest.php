<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLibraryPageRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'current_page' => ['required', 'integer', 'min:0'],
        ];
    }
}
