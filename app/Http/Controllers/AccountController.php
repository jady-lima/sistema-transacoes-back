<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Accounts;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransactionRequest;

class AccountController extends Controller
{
    /**
     * Lista todas as contas do sistema para usuários administradores.
     */
    public function listAll()
    {
        try{ 
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário nao autenticado.'
                ], 401);
            }

            if(!$user->isAdmin()) {
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem listar todas as contas.'
                ], 403);
            }

            $accounts = Accounts::all();

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
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario nao autenticado. Por favor, realize o login para continuar.'
                ], 500);
            }

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
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario nao autenticado. Por favor, realize o login para continuar.'
                ], 500);
            }

            $account = Accounts::where("number", $data["number"])
                            ->where("agency", $data["agency"])
                            ->first();
            
            if(!$account) {
                return response()->json([
                    'message' => 'Erro ao adicionar credito. A conta nao existe.',
                    'error' => true
                ], 400);
            }

            if ($data['type'] === 'credit') {
                $account->balance += $data['ammount'];
            } else if ($data['type'] === 'debit') {
                if ($data['ammount'] <= $account->balance) {
                    $account->balance -= $data['ammount'];
                } else {
                    return response()->json([
                        'message' => 'Erro ao realizar saque em conta. Valor nao disponivel.',
                        'error' => true
                    ], 400);
                }
            }

            $account->save();

            return response()->json([
                'message' => 'Transação efetuada com sucesso!',
                'saldo' => $account->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao efetuar transacao. Tente novamente em instantes.',
                'error' => $e->getMessage()
            ], 500);
        }
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
