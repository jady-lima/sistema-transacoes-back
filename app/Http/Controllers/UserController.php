<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRequest;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Lista todos os usuários do sistema para usuários administradores.
     */
    public function listAll()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }

            if(!$user->isAdmin()) {
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem listar todas as contas.'
                ], 403);
            }

            $users = User::all();

            return response()->json([
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários. Tente novamente mais tarde.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo usuário do tipo cliente no sistema.
     */
    public function store(UserRequest $request)
    {
        try {
            $data = $request->validated();

            $user = User::create($data);
            if (!$user) {
                return response()->json([
                    'message' => 'Erro ao criar usuário.'
                ], 500);
            }

            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar usuário. Tente novamente mais tarde.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
