<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransferRequest;
use App\Models\Accounts;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $transactionService) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(CreateTransferRequest $request): JsonResponse 
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            throw ValidationException::withMessages([
                'account' => ['Este usuário não possui cadastro de cliente.'],
            ]);
        }

        $sourceAccount = Accounts::where('client_id', $client->id)
                                ->first();

        if (!$sourceAccount) {
            throw ValidationException::withMessages([
                'account' => ['Este cliente não possui uma conta ativa.'],
            ]);
        }

        $data = $request->validated();

        $destinationAccount = Accounts::where('number', $data['destination_number'])
                                    ->where('agency', $data['destination_agency'])
                                    ->first();

        if (!$destinationAccount) {
            throw ValidationException::withMessages([
                'destination_number' => ['Conta de destino não encontrada.'],
            ]);
        }

        $result = $this->transactionService->transfer(
            $sourceAccount,
            $destinationAccount,
            (int) $data['amount_cents']
        );

        return response()->json([
            'message' => 'Transferência realizada com sucesso.',
            'transfer' => [
                'amount_cents' => $data['amount_cents'],
                'source' => [
                    'account_id' => $result['source_account']->id,
                    'number' => $result['source_account']->number,
                    'agency' => $result['source_account']->agency,
                    'balance_cents' => $result['source_account']->balance_cents,
                    'transaction_id' => $result['debit_transaction']->id,
                ],
                'destination' => [
                    'account_id' => $result['destination_account']->id,
                    'number' => $result['destination_account']->number,
                    'agency' => $result['destination_account']->agency,
                    'transaction_id' => $result['credit_transaction']->id,
                ],
            ],
        ], 201);
    }
}