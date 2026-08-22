<?php

namespace App\Http\Requests\Library;

use App\Domain\Books\Enums\LibraryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryStatusRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(LibraryStatus::class)],
        ];
    }
}
