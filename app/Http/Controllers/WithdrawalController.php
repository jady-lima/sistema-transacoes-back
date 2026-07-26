<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWithdrawalRequest;
use App\Models\Accounts;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class WithdrawalController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $transactionService) {}

    public function __invoke(CreateWithdrawalRequest $request,Accounts $account): JsonResponse 
    {
        $user = $request->user();

        if (!$user->client || $account->client_id !== $user->client->id) {
            abort(403, 'Você não possui permissão para movimentar esta conta.');
        }

        $data = $request->validated();

        $result = $this->transactionService->withdraw(
            $account,
            (int) $data['amount_cents']
        );

        return response()->json([
            'message' => 'Saque realizado com sucesso.',
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