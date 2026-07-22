<?php

namespace Tests\Feature;

use App\Models\Accounts;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialTransactionTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sequence = 100000;
    }

    public function test_cliente_realiza_deposito(): void
    {
        [$user, $account] = $this->createClientWithAccount();
        Sanctum::actingAs($user);

        $response = $this->postJson( '/api/cliente/contas/depositos',
            [
                'amount' => '10.50',
            ]
        );

        $response->assertOk()
                ->assertJsonPath(
                    'message',
                    'Depósito realizado com sucesso.'
                )
                ->assertJsonPath(
                    'account.balance_cents',
                    1050
                )
                ->assertJsonPath(
                    'account.balance',
                    '10.50'
                )
                ->assertJsonStructure([
                    'message',
                    'reference_id',
                    'account',
                ]);

        $referenceId = $response->json('reference_id');

        $this->assertIsString($referenceId);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 1050,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'direction' => 'credit',
            'operation' => 'deposit',
            'amount_cents' => 1050,
            'reference_id' => $referenceId,
        ]);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_cliente_realiza_saque(): void
    {
        [$user, $account] = $this->createClientWithAccount(balanceCents: 10000);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cliente/contas/saques',
            [
                'amount' => '25.50',
            ]
        );

        $response->assertOk()
                ->assertJsonPath(
                    'message',
                    'Saque realizado com sucesso.'
                )
                ->assertJsonPath(
                    'account.balance_cents',
                    7450
                )
                ->assertJsonPath(
                    'account.balance',
                    '74.50'
                );

        $referenceId = $response->json('reference_id');
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 7450,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'direction' => 'debit',
            'operation' => 'withdrawal',
            'amount_cents' => 2550,
            'reference_id' => $referenceId,
        ]);
    }

    public function test_saque_sem_saldo_nao_altera_a_conta(): void
    {
        [$user, $account] = $this->createClientWithAccount(balanceCents: 1000);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cliente/contas/saques',
            [
                'amount' => '20.00',
            ]
        );

        $response->assertUnprocessable()
                ->assertJsonPath(
                    'message',
                    'Saldo insuficiente para realizar a operação.'
                );

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 1000,
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cliente_realiza_transferencia(): void
    {
        [$originUser, $originAccount] = $this->createClientWithAccount(balanceCents: 10000);
        [, $destinationAccount] = $this->createClientWithAccount(balanceCents: 2500);

        Sanctum::actingAs($originUser);
        $response = $this->postJson('/api/cliente/contas/transferencias',
            [
                'destination_account_number' => $destinationAccount->number,
                'destination_agency' => $destinationAccount->agency,
                'amount' => '30.00',
            ]
        );

        $response->assertOk()
                ->assertJsonPath(
                    'message',
                    'Transferência realizada com sucesso.'
                )
                ->assertJsonPath(
                    'account.balance_cents',
                    7000
                );

        $referenceId = $response->json('reference_id');

        $this->assertIsString($referenceId);
        $this->assertDatabaseHas('accounts', [
            'id' => $originAccount->id,
            'balance_cents' => 7000,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $destinationAccount->id,
            'balance_cents' => 5500,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $originAccount->id,
            'direction' => 'debit',
            'operation' => 'transfer',
            'amount_cents' => 3000,
            'reference_id' => $referenceId,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $destinationAccount->id,
            'direction' => 'credit',
            'operation' => 'transfer',
            'amount_cents' => 3000,
            'reference_id' => $referenceId,
        ]);

        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_cliente_nao_transfere_para_propria_conta(): void
    {
        [$user, $account] = $this->createClientWithAccount(balanceCents: 10000);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/cliente/contas/transferencias',
            [
                'destination_account_number' => $account->number,
                'destination_agency' => $account->agency,
                'amount' => '10.00',
            ]
        );

        $response->assertUnprocessable()
                ->assertJsonPath(
                    'message',
                    'Não é possível transferir para a própria conta.'
                );

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 10000,
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_operacao_rejeita_valor_numerico(): void
    {
        [$user, $account] = $this->createClientWithAccount();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/cliente/contas/depositos',
            [
                'amount' => 10.50,
            ]
        );

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'amount',
                ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 0,
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_conta_bloqueada_nao_realiza_operacao(): void
    {
        [$user, $account] = $this->createClientWithAccount(balanceCents: 10000, status: 'blocked');

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/cliente/contas/saques',
            [
                'amount' => '10.00',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'A conta precisa estar ativa para realizar esta operação.'
            );

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance_cents' => 10000,
            'status' => 'blocked',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cliente_visualiza_extrato_atualizado(): void
    {
        [$user] = $this->createClientWithAccount(balanceCents: 10000);

        Sanctum::actingAs($user);
        $this->postJson('/api/cliente/contas/depositos',
            [
                'amount' => '50.00',
            ]
        )->assertOk();

        $this->postJson('/api/cliente/contas/saques',
            [
                'amount' => '20.00',
            ]
        )->assertOk();

        $response = $this->getJson('/api/cliente/contas');

        $response->assertOk()
                ->assertJsonCount(2, 'transactions')
                ->assertJsonFragment([
                    'direction' => 'credit',
                    'operation' => 'deposit',
                    'amount_cents' => 5000,
                ])
                ->assertJsonFragment([
                    'direction' => 'debit',
                    'operation' => 'withdrawal',
                    'amount_cents' => 2000,
                ]);
    }

    private function createClientWithAccount(int $balanceCents = 0, string $status = 'active'): array 
    {
        $sequence = $this->sequence++;
        $user = User::factory()->create([
            'role' => 'cliente',
        ]);

        $client = Client::query()->forceCreate([
            'user_id' => $user->id,

            'phone' => '84' . str_pad(
                (string) $sequence,
                9,
                '0',
                STR_PAD_LEFT
            ),

            'cpf' => str_pad(
                (string) $sequence,
                11,
                '0',
                STR_PAD_LEFT
            ),
        ]);

        $account = Accounts::query()->forceCreate([
            'client_id' => $client->id,
            'number' => (string) $sequence,
            'agency' => '0001',
            'balance_cents' => $balanceCents,
            'status' => $status,
        ]);

        return [$user, $account];
    }
}