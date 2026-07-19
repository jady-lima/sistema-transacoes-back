<?php

namespace App\Services;

use App\Models\Accounts;
use App\Models\Transactions;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialTransactionService
{
    /**
     * Função que realiza o deposito em uma conta
     */
    public function deposit(int $accountId, int $amountCents): array 
    {
        return DB::transaction(function () use (
            $accountId,
            $amountCents
        ) {
            $account = $this->findLockedAccount($accountId);
            $this->ensureActive($account);

            $account->balance_cents += $amountCents;
            $account->save();

            $referenceId = (string) Str::uuid();

            Transactions::create([
                'account_id' => $account->id,
                'direction' => 'credit',
                'operation' => 'deposit',
                'amount_cents' => $amountCents,
                'reference_id' => $referenceId,
            ]);

            return [
                'account' => $account->refresh(),
                'reference_id' => $referenceId,
            ];
        });
    }

    /**
     * Função de saque de um valor em uma conta
     */
    public function withdraw(int $accountId, int $amountCents): array 
    {
        return DB::transaction(function () use (
            $accountId,
            $amountCents
        ) {
            $account = $this->findLockedAccount($accountId);

            $this->ensureActive($account);
            $this->ensureSufficientBalance($account, $amountCents);

            $account->balance_cents -= $amountCents;
            $account->save();

            $referenceId = (string) Str::uuid();

            Transactions::create([
                'account_id' => $account->id,
                'direction' => 'debit',
                'operation' => 'withdrawal',
                'amount_cents' => $amountCents,
                'reference_id' => $referenceId,
            ]);

            return [
                'account' => $account->refresh(),
                'reference_id' => $referenceId,
            ];
        });
    }

    /**
     * Função de transferênca de saldo entre contas
     */
    public function transfer(int $originAccountId, string $destinationNumber, string $destinationAgency, int $amountCents): array 
    {
        return DB::transaction(function () use (
            $originAccountId,
            $destinationNumber,
            $destinationAgency,
            $amountCents
        ) {
            $destinationAccountId = Accounts::where('number', $destinationNumber)
                                            ->where('agency', $destinationAgency)
                                            ->value('id');

            if ($destinationAccountId === null) {
                throw new DomainException(
                    'Conta de destino não encontrada.'
                );
            }

            $destinationAccountId = (int) $destinationAccountId;
            if ($originAccountId === $destinationAccountId) {
                throw new DomainException(
                    'Não é possível transferir para a própria conta.'
                );
            }
            
            $accountIds = [
                $originAccountId,
                $destinationAccountId,
            ];

            sort($accountIds);

            $accounts = Accounts::whereIn('id', $accountIds)
                                ->orderBy('id')
                                ->lockForUpdate()
                                ->get()
                                ->keyBy('id');

            $originAccount = $accounts->get($originAccountId);
            $destinationAccount = $accounts->get(
                $destinationAccountId
            );

            if (!$originAccount || !$destinationAccount) {
                throw new DomainException(
                    'Uma das contas envolvidas não foi encontrada.'
                );
            }

            $this->ensureActive($originAccount);
            $this->ensureActive($destinationAccount);
            $this->ensureSufficientBalance(
                $originAccount,
                $amountCents
            );

            $originAccount->balance_cents -= $amountCents;
            $destinationAccount->balance_cents += $amountCents;

            $originAccount->save();
            $destinationAccount->save();

            $referenceId = (string) Str::uuid();

            Transactions::create([
                'account_id' => $originAccount->id,
                'direction' => 'debit',
                'operation' => 'transfer',
                'amount_cents' => $amountCents,
                'reference_id' => $referenceId,
            ]);

            Transactions::create([
                'account_id' => $destinationAccount->id,
                'direction' => 'credit',
                'operation' => 'transfer',
                'amount_cents' => $amountCents,
                'reference_id' => $referenceId,
            ]);

            return [
                'account' => $originAccount->refresh(),
                'reference_id' => $referenceId,
            ];
        });
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