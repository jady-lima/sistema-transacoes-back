<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawalRequest extends FormRequest
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
            'amount_cents.required' => 'O valor do saque é obrigatório.',
            'amount_cents.integer' => 'O valor do saque deve ser informado em centavos.',
            'amount_cents.min' => 'O valor do saque deve ser maior que zero.',
        ];
    }
}