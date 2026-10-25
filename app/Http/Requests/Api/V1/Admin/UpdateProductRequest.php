<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Services\ProductService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.5', 'max:99999999.99', 'decimal:0,2'],
            'file_path' => ['nullable', 'string', 'regex:'.ProductService::IMAGE_PATH_PATTERN],
        ];
    }
}
