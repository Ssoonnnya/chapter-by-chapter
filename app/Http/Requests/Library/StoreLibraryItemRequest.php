<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryItemRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'google_volume_id' => ['required', 'string', 'max:64'],
        ];
    }
}
