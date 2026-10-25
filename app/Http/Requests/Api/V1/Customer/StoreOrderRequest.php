<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Services\CartService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
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
        return [];
    }

    /**
     * The order is built from the cart, so there must be something in it.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (app(CartService::class)->items($this->user())->isEmpty()) {
                    $validator->errors()->add('cart', 'Your cart is empty.');
                }
            },
        ];
    }
}
