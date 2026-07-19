<?php

namespace App\Http\Requests;

use App\Rules\PositiveMoney;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_account_number' => [
                'required',
                'string',
                'max:30',
            ],

            'destination_agency' => [
                'required',
                'string',
                'max:20',
            ],

            'amount' => [
                'required',
                new PositiveMoney(),
            ],
        ];
    }
}