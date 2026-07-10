<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste que verifica se o admin está conseguindo listar todas as contas registradas no sistema
     */
    public function test_admin_listar_todas_contas(): void
    {
        User::factory()->create([
            'email' => 'admin.contas@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'admin',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin.contas@gmail.com',
            'password' => 'senha123',
        ]);

        $loginResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'access_token',
                      ]);

        $token = $loginResponse->json('access_token');
        $this->assertNotEmpty($token);

        $response = $this->withToken($token)->getJson('/api/admin/contas');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'accounts',
                 ]);
    }

    /**
     * Teste que verifica se o cliente está conseguindo listar todas as contas registradas no sistema
     */
    public function test_cliente_listar_contas(): void
    {
        User::factory()->create([
            'email' => 'cliente.contas@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'cliente.contas@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withToken($token)
                         ->getJson('/api/admin/contas');

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Acesso não autorizado.',
                 ]);
    }

    /**
     * Teste válido para criar a conta de um usuário
     */
    public function test_cliente_cria_conta(): void
    {
        User::factory()->create([
            'email' => 'cliente.novo@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'cliente.novo@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withToken($token)
                         ->postJson('/api/cliente/contas', [
                            'phone' => '849568746321',
                            'cpf' => '12364597862',
                        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Conta criada com sucesso.',
                ])
                ->assertJsonStructure([
                    'message',
                    'account',
                ]);
    }

    /**
     * Teste para criar a conta de um usuário sem token
     */
    public function test_cliente_cria_conta_sem_token(): void
    {
        $response = $this->postJson('/api/cliente/contas', [
                            'phone' => '849568746321',
                            'cpf' => '12364597862',
                        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
    }

    /**
     * Teste para criar a conta de um usuário com dados inválidos
     */
    public function test_cliente_cria_conta_dados_invalidos(): void
    {
        User::factory()->create([
            'email' => 'cliente.invalido@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'cliente.invalido@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas', [
                            'phone' => 849568746321,
                            'cpf' => '12364597862',
                        ]);

        $response->assertStatus(422);
    }

    /**
     * Teste todas as transações de um cliente registradas
     */
    public function test_cliente_lista_transacoes(): void
    {
        User::factory()->create([
            'email' => 'cliente.lista.transacoes@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'cliente.lista.transacoes@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $createResponse = $this->withToken($token)
                                ->postJson('/api/cliente/contas', [
                                    'phone' => '849568746331',
                                    'cpf' => '12364597852',
                                ]);

        $createResponse->assertCreated();
        $listResponse = $this->withToken($token)->getJson('/api/cliente/contas');

        $listResponse->assertOk()
                    ->assertJsonStructure([
                        'client',
                        'account' => [
                            'id',
                            'number',
                            'agency',
                            'balance',
                            'status',
                        ],
                        'transactions',
                    ])
                    ->assertJsonCount(0, 'transactions');
    }

    /**
     * Teste se cliente sem conta ativa consegue acessar
     */
    public function test_usuario_sem_conta_lista_transacoes(): void
    {
        User::factory()->create([
            'email' => 'user.lista.transacoes@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'user.lista.transacoes@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withToken($token)
                         ->getJson('/api/cliente/contas');
        
        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Este usuario não possui conta como cliente ativa',
                ])
                ->assertJsonStructure([
                    'message',
                    'error',
                ]);
    }

    
}
