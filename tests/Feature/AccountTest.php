<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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
    public function test_cliente_sem_acesso_para_listar_contas(): void
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
    public function test_cliente_sem_token_tenta_criar_conta(): void
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
    public function test_cliente_dados_invalidos_tenta_criar_conta(): void
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
    public function test_usuario_sem_conta_tenta_listar_transacoes(): void
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

    /**
     * Solicitação de cliente para nova transação de crédito
     */
    public function test_cliente_cria_nova_transacao_credito(): void
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

        $responseConta = $this->withToken($token)
                            ->postJson('/api/cliente/contas', [
                                'phone' => '849568746321',
                                'cpf' => '12364597862',
                            ]);

        $responseConta->assertCreated();

        $number = $responseConta->json('account.number');

        $number = (string) $responseConta->json('account.number');

        $response = $this->withToken($token)
                ->postJson('/api/cliente/contas/transacao', [
                    'number' => $number,
                    'agency' => '0001',
                    'amount' => '2000.00',
                    'type' => 'credit',
                ]);

        $response->assertOk()
                ->assertJson([
                    'message' => 'Crédito efetuado com sucesso!',
                    'saldo' => 2000,
                ]);
    }

    /**
     * Solicitação de cliente para nova transação de crédito com valor inválido
     */
    public function test_cliente_cria_nova_transacao_credito_valor_negativo(): void
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

        $responseConta = $this->withToken($token)
                            ->postJson('/api/cliente/contas', [
                                'phone' => '849568746321',
                                'cpf' => '12364597862',
                            ]);

        $responseConta->assertCreated();

        $number = $responseConta->json('account.number');
        $number = (string) $responseConta->json('account.number');

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas/transacao', [
                            'number' => $number,
                            'agency' => '0001',
                            'amount' => -1,
                            'type' => 'credit',
                        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Valor de transação precisa ser maior que 0.',
                ]);
    }

    /**
     * Solicitação de cliente para nova transação de débito para própria conta(saque)
     */
    public function test_cliente_cria_nova_transacao_debito_como_saque(): void
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

        $responseConta = $this->withToken($token)
                            ->postJson('/api/cliente/contas', [
                                'phone' => '849568746321',
                                'cpf' => '12364597862',
                            ]);

        $responseConta->assertCreated();

        $number = $responseConta->json('account.number');
        $number = (string) $responseConta->json('account.number');

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas/transacao', [
                            'number' => $number,
                            'agency' => '0001',
                            'amount' => '2000.00',
                            'type' => 'credit',
                        ]);

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas/transacao', [
                            'number' => $number,
                            'agency' => '0001',
                            'amount' => '1000.00',
                            'type' => 'debit',
                        ]);

        $response->assertOk()
                ->assertJson([
                    'message' => 'Saque efetuado com sucesso!',
                    'saldo' => 1000,
                ]);
    }

    /**
     * Solicitação de cliente para nova transação de débito para própria conta(saque) com valor negativo
     */
    public function test_cliente_cria_nova_transacao_debito_como_saque_valor_negativo(): void
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

        $responseConta = $this->withToken($token)
                            ->postJson('/api/cliente/contas', [
                                'phone' => '849568746321',
                                'cpf' => '12364597862',
                            ]);

        $responseConta->assertCreated();

        $number = $responseConta->json('account.number');
        $number = (string) $responseConta->json('account.number');

        $response = $this->withToken($token)
            ->postJson('/api/cliente/contas/transacao', [
                'number' => $number,
                'agency' => '0001',
                'amount' => 200.00,
                'type' => 'credit',
            ]);

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas/transacao', [
                            'number' => $number,
                            'agency' => '0001',
                            'amount' => -200,
                            'type' => 'debit',
                        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Valor de transação precisa ser maior que 0.',
                ]);
    }

    /**
     * Solicitação de cliente para nova transação de débito para outra conta.
     */
    public function test_cliente_cria_nova_transacao_debito_como_transferencia(): void
    {
        User::factory()->create([
            'name' => 'Cliente origem',
            'email' => 'cliente.origem@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginOrigem = $this->postJson('/api/login', [
            'email' => 'cliente.origem@gmail.com',
            'password' => 'senha123',
        ]);

        $loginOrigem->assertOk()
                    ->assertJsonStructure([
                        'access_token',
                    ]);

        $tokenOrigem = $loginOrigem->json('access_token');
        $responseContaOrigem = $this->withToken($tokenOrigem)
                                    ->postJson('/api/cliente/contas', [
                                        'phone' => '849568746321',
                                        'cpf' => '12364597862',
                                    ]);

        $responseContaOrigem->assertCreated();

        $contaOrigemId = $responseContaOrigem->json('account.id');
        $numeroContaOrigem = (string) $responseContaOrigem->json('account.number');

        User::factory()->create([
            'name' => 'Cliente destino',
            'email' => 'cliente.destino@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $this->withoutToken();
        Auth::shouldUse('web');

        $loginDestino = $this->postJson('/api/login', [
            'email' => 'cliente.destino@gmail.com',
            'password' => 'senha123',
        ]);

        $loginDestino->assertOk()
                    ->assertJsonStructure([
                        'access_token',
                    ]);
        $tokenDestino = $loginDestino->json('access_token');
        $responseContaDestino = $this->withToken($tokenDestino)
                                    ->postJson('/api/cliente/contas', [
                                        'phone' => '849568746322',
                                        'cpf' => '12364597863',
                                    ]);

        $responseContaDestino->assertCreated();

        $contaDestinoId = $responseContaDestino->json('account.id');
        $numeroContaDestino = (string) $responseContaDestino->json('account.number');

        $responseCredito = $this->withToken($tokenOrigem)
                                ->postJson('/api/cliente/contas/transacao', [
                                    'number' => $numeroContaOrigem,
                                    'agency' => '0001',
                                    'amount' => 2000.00,
                                    'type' => 'credit',
                                ]);

        $responseCredito->assertOk()
                        ->assertJson([
                            'message' => 'Crédito efetuado com sucesso!',
                            'saldo' => 2000,
                        ]);

        $responseTransferencia = $this->withToken($tokenOrigem)
                                    ->postJson('/api/cliente/contas/transacao', [
                                        'number' => $numeroContaDestino,
                                        'agency' => '0001',
                                        'amount' => 1000.00,
                                        'type' => 'debit',
                                    ]);

        $responseTransferencia->assertOk()
                            ->assertJson([
                                'message' => 'Transferencia efetuada com sucesso!',
                                'saldo' => 1000,
                            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $contaOrigemId,
            'balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $contaDestinoId,
            'balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $contaOrigemId,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $contaDestinoId,
            'type' => 'credit',
            'amount' => 1000.00,
        ]);
    }

    /**
     * Solicitação de cliente para nova transação de débito para própria conta(saque) com valor superior ao em conta
     */
    public function test_cliente_cria_nova_transacao_debito_como_saque_valor_maior_que_saldo(): void
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

        $responseConta = $this->withToken($token)
                            ->postJson('/api/cliente/contas', [
                                'phone' => '849568746321',
                                'cpf' => '12364597862',
                            ]);

        $responseConta->assertCreated();

        $number = $responseConta->json('account.number');
        $number = (string) $responseConta->json('account.number');

        $response = $this->withToken($token)
            ->postJson('/api/cliente/contas/transacao', [
                'number' => $number,
                'agency' => '0001',
                'amount' => 200.00,
                'type' => 'credit',
            ]);

        $response = $this->withToken($token)
                        ->postJson('/api/cliente/contas/transacao', [
                            'number' => $number,
                            'agency' => '0001',
                            'amount' => 2000,
                            'type' => 'debit',
                        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Saldo insuficiente para realizar a transferencia.',
                ]);
    }
}
