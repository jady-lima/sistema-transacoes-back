<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Accounts;
use App\Models\Transactions;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransactionRequest;
use Illuminate\Support\Facades\DB;

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
     * Função que realiza nova transação do cliente
     */
    public function newTransaction(TransactionRequest $request)
    {
        try {
            $data = $request->validated();

            $user = Auth::user();

            return DB::transaction(function () use ($data, $user) {
                $client = Client::where('user_id', $user->id)->first();

                if (!$client) {
                    return response()->json([
                        'message' => 'Cliente nao encontrado para o usuario autenticado.',
                        'error' => true
                    ], 404);
                }

                $originAccount = Accounts::where('client_id', $client->id)->first();
                if (!$originAccount) {
                    return response()->json([
                        'message' => 'O usuario autenticado nao possui conta.',
                        'error' => true
                    ], 404);
                }

                $destinationAccount = Accounts::where('number', $data['number'])
                    ->where('agency', $data['agency'])
                    ->first();

                if (!$destinationAccount) {
                    return response()->json([
                        'message' => 'Conta de destino nao encontrada.',
                        'error' => true
                    ], 404);
                }

                if ($data['amount'] <= 0) {
                    return response()->json([
                        'message' => 'Valor de transação precisa ser maior que 0.',
                    ], 400);
                }

                if ($data['type'] === 'credit') {
                    $destinationAccount->balance += $data['amount'];
                    $destinationAccount->save();

                    Transactions::create([
                        'account_id' => $destinationAccount->id,
                        'type' => 'credit',
                        'amount' => $data['amount'],
                    ]);

                    return response()->json([
                        'message' => 'Crédito efetuado com sucesso!',
                        'saldo' => $destinationAccount->balance,
                    ]);
                }

                if ($data['type'] === 'debit') {
                    if ($data['amount'] > $originAccount->balance) {
                        return response()->json([
                            'message' => 'Saldo insuficiente para realizar a transferencia.',
                            'error' => true
                        ], 400);
                    }

                    if ($originAccount->id === $destinationAccount->id) {
                        $originAccount->balance -= $data['amount'];
                        $originAccount->save();

                        return response()->json([
                            'message' => 'Saque efetuado com sucesso!',
                            'saldo' => $originAccount->balance,
                        ]);
                    } else {
                        $originAccount->balance -= $data['amount'];
                        $destinationAccount->balance += $data['amount'];

                        $originAccount->save();
                        $destinationAccount->save();

                        Transactions::create([
                            'account_id' => $originAccount->id,
                            'type' => 'debit',
                            'amount' => $data['amount'],
                        ]);

                        Transactions::create([
                            'account_id' => $destinationAccount->id,
                            'type' => 'credit',
                            'amount' => $data['amount'],
                        ]);

                        return response()->json([
                            'message' => 'Transferencia efetuada com sucesso!',
                            'saldo' => $originAccount->balance,
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'Tipo de transacao invalido.',
                    'error' => true
                ], 400);
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao efetuar transacao. Tente novamente em instantes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listAllClientTransaction()
    {
        $user = Auth::user();

        $client = Client::where('user_id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'message' => 'Este usuario não possui conta como cliente ativa',
                'error' => true
            ], 400);
        }

        $account = Accounts::where('client_id', $client->id)->first();
        if (!$account) {
            return response()->json([
                'message' => 'Este cliente nao possui conta.',
                'error' => true
            ], 400);
        }

        $transactions = Transactions::where('account_id', $account->id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'client' => $client,
            'account' => [
                'id' => $account->id,
                'number' => $account->number,
                'agency' => $account->agency,
                'balance' => $account->balance,
                'status' => $account->status,
            ],
            'transactions' => $transactions
        ], 200);
    }

    private function createAccount(Client $client)
    {
        $account = $client->accounts()->create([
            'number' => rand(100000, 999999)
        ]);

        return $account;
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
