<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_number' => [
                'required',
                'string',
                'max:20',
            ],
            'destination_agency' => [
                'required',
                'string',
                'max:10',
            ],
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
            'destination_number.required' => 'O número da conta de destino é obrigatório.',
            'destination_agency.required' => 'A agência da conta de destino é obrigatória.',
            'amount_cents.required' => 'O valor da transferência é obrigatório.',
            'amount_cents.integer' => 'O valor da transferência deve ser informado em centavos.',
            'amount_cents.min' => 'O valor da transferência deve ser maior que zero.',
        ];
    }
}