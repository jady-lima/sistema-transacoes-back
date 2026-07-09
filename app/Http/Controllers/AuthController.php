<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AuthRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Avalia solicitação de login e retorna dados do usuário autenticado, incluindo token de acesso.
     */
    public function login(AuthRequest $request)
    {
        $credentials = $request->validated();

        if(!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estao incorretas.'],
            ]);
        }

        $user = Auth::user();

        $user->tokens()->delete();

        $token = $user->createToken(
            name: 'auth_token',
            abilities: $user->role === 'admin' ? ['admin'] : ['cliente']
        )->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Função que retorna informações do usuário autenticado.
     */
    public function info()
    {
        $user = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }

    /**
     * Função que realiza o logout do usuário autenticado, revogando o token de acesso.
     */
    public function logout()
    {
        $user = Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}
