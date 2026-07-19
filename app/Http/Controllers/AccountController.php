<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Accounts;
use App\Models\Transactions;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AccountRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AmountRequest;
use App\Http\Requests\TransferRequest;
use App\Services\FinancialTransactionService;
use App\Support\Money;
use DomainException;

class AccountController extends Controller
{
    /**
     * Lista todas as contas do sistema para usuários administradores.
     */
    public function listAll()
    {
        try{
            $user = Auth::user();
            if(!$user->isAdmin()) {
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem listar todas as contas.'
                ], 403);
            }

            $accounts = Accounts::with('client.user')->get();

            return response()->json([
                'accounts' => $accounts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar contas. Tente novamente mais tarde.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria conta para o usuário autenticado, associando-a a um cliente.
     */
    public function store(AccountRequest $request)
    {
        try {
            $data = $request->validated();

            $user = Auth::user();

            if ($user->client) {
                return response()->json([
                    'message' => 'Usuário já possui uma conta.'
                ], 400);
            }

            $client = $this->createClient($user, $data);

            if (!$client) {
                return response()->json([
                    'message' => 'Erro ao criar cliente.'
                ], 500);
            }

            $account = $this->createAccount($client);

            if (!$account) {
                return response()->json([
                    'message' => 'Erro ao criar conta. Tente novamente em instantes.'
                ], 500);
            }

            return response()->json([
                'message' => 'Conta criada com sucesso.',
                'account' => $account
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar conta. Tente novamente em instantes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Função responsável por realizar deposito em conta
     */
    public function deposit(AmountRequest $request, FinancialTransactionService $service) 
    {
        $account = $this->authenticatedAccount();

        if (!$account) {
            return response()->json([
                'message' => 'O usuário autenticado não possui conta.',
            ], 404);
        }

        try {
            $result = $service->deposit(
                $account->id,
                Money::toCents($request->validated('amount'))
            );

            return response()->json([
                'message' => 'Depósito realizado com sucesso.',
                'reference_id' => $result['reference_id'],
                'account' => $this->accountPayload(
                    $result['account']
                ),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Função que simula saque em conta
     */
    public function withdrawal(AmountRequest $request, FinancialTransactionService $service) 
    {
        $account = $this->authenticatedAccount();

        if (!$account) {
            return response()->json([
                'message' => 'O usuário autenticado não possui conta.',
            ], 404);
        }

        try {
            $result = $service->withdraw(
                $account->id,
                Money::toCents($request->validated('amount'))
            );

            return response()->json([
                'message' => 'Saque realizado com sucesso.',
                'reference_id' => $result['reference_id'],
                'account' => $this->accountPayload(
                    $result['account']
                ),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Função de transferência de saldo entre contas
     */
    public function transfer(TransferRequest $request, FinancialTransactionService $service) 
    {
        $account = $this->authenticatedAccount();

        if (!$account) {
            return response()->json([
                'message' => 'O usuário autenticado não possui conta.',
            ], 404);
        }

        $data = $request->validated();

        try {
            $result = $service->transfer(
                $account->id,
                $data['destination_account_number'],
                $data['destination_agency'],
                Money::toCents($data['amount'])
            );

            return response()->json([
                'message' => 'Transferência realizada com sucesso.',
                'reference_id' => $result['reference_id'],
                'account' => $this->accountPayload(
                    $result['account']
                ),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Função que retorna a listagem de todas as transações realizadas em uma conta
     */
    public function listAllClientTransaction()
    {
        $account = $this->authenticatedAccount();

        if (!$account) {
            return response()->json([
                'message' => 'O usuário autenticado não possui conta.',
            ], 404);
        }

        $transactions = $account->transactions()
                                ->latest()
                                ->get()
                                ->map(fn ($transaction) => [
                                    'id' => $transaction->id,
                                    'direction' => $transaction->direction,
                                    'operation' => $transaction->operation,
                                    'amount' => Money::format($transaction->amount_cents),
                                    'amount_cents' => $transaction->amount_cents,
                                    'reference_id' => $transaction->reference_id,
                                    'created_at' => $transaction->created_at?->toISOString(),
                                ]);

        return response()->json([
            'account' => $this->accountPayload($account),
            'transactions' => $transactions,
        ]);
    }

    private function authenticatedAccount(): ?Accounts
    {
        $userId = Auth::id();

        return Accounts::whereHas('client', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();
    }

    private function accountPayload(Accounts $account): array
    {
        return [
            'id' => $account->id,
            'number' => $account->number,
            'agency' => $account->agency,
            'balance' => Money::format($account->balance_cents),
            'balance_cents' => $account->balance_cents,
            'status' => $account->status,
        ];
    }

    private function createAccount(Client $client)
    {
        return $client->accounts()->create([
            'number' => (string) random_int(100000, 999999),
            'balance_cents' => 0,
            'status' => 'active',
        ]);
    }

    private function createClient($user, $data)
    {
        $client = Client::create([
            'user_id' => $user->id,
            'phone' => $data['phone'],
            'cpf' => $data['cpf'],
        ]);

        return $client;
    }
}
