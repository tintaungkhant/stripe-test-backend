<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Services\ProductService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductImageUploadRequest extends FormRequest
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
            'content_type' => ['required', 'string', Rule::in(array_keys(ProductService::IMAGE_EXTENSIONS))],
            'size' => ['required', 'integer', 'min:1', 'max:'.ProductService::MAX_IMAGE_BYTES],
        ];
    }
}
