<?php

namespace App\Services;

use App\Models\Accounts;
use App\Models\Transactions;
use DomainException;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialTransactionService
{
    /**
     * Função que realiza o deposito em uma conta
     * 
     * @return array{
     *     account: Accounts,
     *     transaction: Transactions
     * }
     */
    public function deposit(Accounts $account, int $amountCents): array 
    {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('O valor do depósito deve ser maior que zero.');
        }

        return DB::transaction(function () use ($account, $amountCents) {
            $lockedAccount = Accounts::lockForUpdate()
                                    ->findOrFail($account->id);

            $lockedAccount->increment(
                'balance_cents',
                $amountCents
            );

            $transaction = $lockedAccount->transactions()
                                        ->create([
                                            'type' => 'credit',
                                            'amount_cents' => $amountCents,
                                        ]);

            return [
                'account' => $lockedAccount->fresh(),
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Função de saque de um valor em uma conta
     * @return array{
     *     account: Accounts,
     *     transaction: Transactions
     * }
     *
     * @throws ValidationException
     */
    public function withdraw(Accounts $account, int $amountCents): array 
    {
        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount_cents' => [
                    'O valor do saque deve ser maior que zero.',
                ],
            ]);
        }

        return DB::transaction(function () use ($account, $amountCents) {
            $lockedAccount = Accounts::whereKey($account->getKey())
                                    ->lockForUpdate()
                                    ->firstOrFail();

            if ($lockedAccount->balance_cents < $amountCents) {
                throw ValidationException::withMessages([
                    'amount_cents' => [
                        'Saldo insuficiente para realizar o saque.',
                    ],
                ]);
            }

            $lockedAccount->decrement(
                'balance_cents',
                $amountCents
            );

            $transaction = $lockedAccount->transactions()
                                        ->create([
                                            'type' => 'debit',
                                            'amount_cents' => $amountCents,
                                        ]);

            return [
                'account' => $lockedAccount->fresh(),
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Função de transferênca de saldo entre contas
     * 
     * @return array{
     *     source_account: Accounts,
     *     destination_account: Accounts,
     *     debit_transaction: Transactions,
     *     credit_transaction: Transactions
     * }
     *
     * @throws ValidationException
     */
    public function transfer(Accounts $sourceAccount, Accounts $destinationAccount, int $amountCents): array 
    {
        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount_cents' => ['O valor da transferência deve ser maior que zero.'],
            ]);
        }

        if ($sourceAccount->is($destinationAccount)) {
            throw ValidationException::withMessages([
                'destination_number' => ['A conta de destino deve ser diferente da conta de origem.'],
            ]);
        }

        return DB::transaction(
            function () use ($sourceAccount, $destinationAccount, $amountCents) {
                $accountIds = [
                    $sourceAccount->getKey(),
                    $destinationAccount->getKey(),
                ];

                sort($accountIds);

                $lockedAccounts = Accounts::whereIn('id', $accountIds)
                                        ->orderBy('id')
                                        ->lockForUpdate()
                                        ->get()
                                        ->keyBy('id');

                $lockedSource = $lockedAccounts->get($sourceAccount->getKey());
                $lockedDestination = $lockedAccounts->get($destinationAccount->getKey());

                if (!$lockedSource || !$lockedDestination) {
                    throw ValidationException::withMessages([
                        'destination_number' => ['Não foi possível localizar as contas da transferência.'],
                    ]);
                }

                if ($lockedSource->balance_cents < $amountCents) {
                    throw ValidationException::withMessages([
                        'amount_cents' => ['Saldo insuficiente para realizar a transferência.'],
                    ]);
                }

                $lockedSource->balance_cents -= $amountCents;
                $lockedSource->save();

                $lockedDestination->balance_cents += $amountCents;
                $lockedDestination->save();

                $debitTransaction = $lockedSource->transactions()
                                                ->create([
                                                    'type' => 'debit',
                                                    'amount_cents' => $amountCents,
                                                ]);

                $creditTransaction = $lockedDestination->transactions()
                                                    ->create([
                                                        'type' => 'credit',
                                                        'amount_cents' => $amountCents,
                                                    ]);

                return [
                    'source_account' => $lockedSource->fresh(),
                    'destination_account' => $lockedDestination->fresh(),
                    'debit_transaction' => $debitTransaction,
                    'credit_transaction' => $creditTransaction,
                ];
            },
            attempts: 3
        );
    }

    private function findLockedAccount(int $accountId): Accounts
    {
        $account = Accounts::whereKey($accountId)
                            ->lockForUpdate()
                            ->first();

        if (!$account) {
            throw new DomainException('Conta não encontrada.');
        }

        return $account;
    }

    private function ensureActive(Accounts $account): void
    {
        if ($account->status !== 'active') {
            throw new DomainException(
                'A conta precisa estar ativa para realizar esta operação.'
            );
        }
    }

    private function ensureSufficientBalance(Accounts $account, int $amountCents): void 
    {
        if ($account->balance_cents < $amountCents) {
            throw new DomainException(
                'Saldo insuficiente para realizar a operação.'
            );
        }
    }
}