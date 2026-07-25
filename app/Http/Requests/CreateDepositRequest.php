<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_cents' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_cents.required' => 'O valor do depósito é obrigatório.',
            'amount_cents.integer' => 'O valor do depósito deve ser informado em centavos.',
            'amount_cents.min' => 'O valor do depósito deve ser maior que zero.',
        ];
    }
}