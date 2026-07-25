<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDepositRequest;
use App\Models\Accounts;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $transactionService) {}

    public function __invoke(CreateDepositRequest $request, Accounts $account): JsonResponse 
    {
        $data = $request->validated();

        $result = $this->transactionService->deposit(
            $account,
            (int) $data['amount_cents']
        );

        return response()->json([
            'message' => 'Depósito realizado com sucesso.',
            'account' => [
                'id' => $result['account']->id,
                'number' => $result['account']->number,
                'agency' => $result['account']->agency,
                'balance_cents' => $result['account']->balance_cents,
            ],
            'transaction' => [
                'id' => $result['transaction']->id,
                'type' => $result['transaction']->type,
                'amount_cents' => $result['transaction']->amount_cents,
                'created_at' => $result['transaction']->created_at,
            ],
        ], 201);
    }
}