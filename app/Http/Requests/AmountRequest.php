<?php

namespace App\Http\Requests;

use App\Rules\PositiveMoney;
use Illuminate\Foundation\Http\FormRequest;

class AmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                new PositiveMoney(),
            ],
        ];
    }
}